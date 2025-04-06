<?php
    function validate_request($method, $required_fields) {
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            return false;
        }
    
        $method_superglobal = array();
        switch ($method) {
            case "GET":
                $method_superglobal = $_GET;
                break;
            case "POST":
                $method_superglobal = $_POST;
                break;
            case "DELETE":
                $method_superglobal = $_GET;
                break;
            default:
                return false;
        }
    
    
        foreach ($required_fields as $field) {
            if (!isset($method_superglobal[$field])) {
                return false;
            }
        }
    
        return true;
    }
// api_utils.php (példa)
function validate_request_json($method, $required_params) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        return false;
    }

    $request_data = json_decode(file_get_contents('php://input'), true); // JSON dekódolás

    foreach ($required_params as $param) {
        if (!isset($request_data[$param]) || empty($request_data[$param])) {
            return false;
        }
    }

    return true;
}

/**
 * Populates the rankings table with all eligible students for a given event_workshop.
 * Students are considered eligible if they are of type 'student' and are associated
 * with the specific workshop type via the mentor_workshop table.
 * The ranking numbers are assigned sequentially starting from 1.
 * IMPORTANT: This function assumes it's called within an active database transaction.
 */
function populateStudentRankings($eventWorkshopId, $workshopId, $conn) {
    error_log("Attempting to populate rankings for event_workshop_id: {$eventWorkshopId}, workshop_id: {$workshopId}");

    // 1. Find all eligible students for this specific workshop type
    $sqlFindStudents = "SELECT p.user_id
                        FROM participants p
                        JOIN mentor_workshop mw ON p.user_id = mw.user_id
                        WHERE p.type = 'student' AND mw.workshop_id = ?";

    $stmtFind = $conn->prepare($sqlFindStudents);
    if (!$stmtFind) {
        throw new Exception("Prepare failed (find students for ranking): " . $conn->error);
    }
    $stmtFind->bind_param("i", $workshopId);
    if (!$stmtFind->execute()) {
        $stmtFind->close();
        throw new Exception("Execute failed (find students for ranking): " . $stmtFind->error);
    }

    $resultStudents = $stmtFind->get_result();
    $studentIds = [];
    while ($row = $resultStudents->fetch_assoc()) {
        $studentIds[] = (int)$row['user_id'];
    }
    $stmtFind->close();

    if (empty($studentIds)) {
        error_log("No eligible students found for workshop_id {$workshopId}. No rankings added for event_workshop_id {$eventWorkshopId}.");
        return; // Nothing to rank
    }

    error_log("Found " . count($studentIds) . " eligible students for workshop {$workshopId}. Adding rankings for ew_id {$eventWorkshopId}.");

    // 2. Prepare the INSERT statement for rankings
    $sqlInsertRanking = "INSERT INTO rankings (event_workshop_id, user_id, ranking_number, user_type)
                         VALUES (?, ?, ?, 'student')";
    $stmtInsert = $conn->prepare($sqlInsertRanking);
    if (!$stmtInsert) {
        throw new Exception("Prepare failed (insert ranking): " . $conn->error);
    }

    // 3. Insert rankings sequentially
    $rankingNumber = 1;
    foreach ($studentIds as $studentId) {
        $stmtInsert->bind_param("iii", $eventWorkshopId, $studentId, $rankingNumber);
        if (!$stmtInsert->execute()) {
            // Don't close statement here, let the main catch block handle rollback
            throw new Exception("Execute failed (insert ranking for user {$studentId}): " . $stmtInsert->error);
        }
        $rankingNumber++;
    }

    // 4. Close the insert statement
    $stmtInsert->close();
    error_log("Successfully populated rankings for event_workshop_id: {$eventWorkshopId}.");
}