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
* Populates the rankings table with all eligible mentors (students AND teachers)
 * for a given event_workshop.
 * Mentors are considered eligible if they match the type ('student' or 'teacher')
 * and are associated with the specific workshop type via the mentor_workshop table.
 * The ranking numbers are assigned sequentially starting from 1 *separately* for
 * students and teachers within the same event_workshop.
 * IMPORTANT: This function assumes it's called within an active database transaction.
 */
function populateMentorRankings($eventWorkshopId, $workshopId, $conn) {
    error_log("Attempting to populate rankings for event_workshop_id: {$eventWorkshopId}, workshop_id: {$workshopId}");

    // --- Process STUDENTS ---
    error_log("Processing STUDENT rankings for ew_id {$eventWorkshopId}, ws_id {$workshopId}");
    // 1a. Find eligible STUDENTS
    $sqlFindStudents = "SELECT p.user_id
                        FROM participants p
                        JOIN mentor_workshop mw ON p.user_id = mw.user_id
                        WHERE p.type = 'student' AND mw.workshop_id = ?";
    $stmtFindStudents = $conn->prepare($sqlFindStudents);
    if (!$stmtFindStudents) throw new Exception("Prepare failed (find students): " . $conn->error);
    $stmtFindStudents->bind_param("i", $workshopId);
    if (!$stmtFindStudents->execute()) { $stmtFindStudents->close(); throw new Exception("Execute failed (find students): " . $stmtFindStudents->error); }
    $resultStudents = $stmtFindStudents->get_result();
    $studentIds = [];
    while ($row = $resultStudents->fetch_assoc()) { $studentIds[] = (int)$row['user_id']; }
    $stmtFindStudents->close();

    // 2a. Prepare INSERT for student rankings
    $sqlInsertStudentRanking = "INSERT INTO rankings (event_workshop_id, user_id, ranking_number, user_type)
                                VALUES (?, ?, ?, 'student')";
    $stmtInsertStudent = $conn->prepare($sqlInsertStudentRanking);
    if (!$stmtInsertStudent) throw new Exception("Prepare failed (insert student ranking): " . $conn->error);

    // 3a. Insert student rankings sequentially
    if (!empty($studentIds)) {
        error_log("Found " . count($studentIds) . " eligible students. Adding rankings...");
        $rankingNumber = 1;
        foreach ($studentIds as $studentId) {
            $stmtInsertStudent->bind_param("iii", $eventWorkshopId, $studentId, $rankingNumber);
            if (!$stmtInsertStudent->execute()) throw new Exception("Execute failed (insert student ranking for user {$studentId}): " . $stmtInsertStudent->error);
            $rankingNumber++;
        }
    } else {
        error_log("No eligible students found.");
    }
    $stmtInsertStudent->close();

    // --- Process TEACHERS ---
     error_log("Processing TEACHER rankings for ew_id {$eventWorkshopId}, ws_id {$workshopId}");
    // 1b. Find eligible TEACHERS
    $sqlFindTeachers = "SELECT p.user_id
                        FROM participants p
                        JOIN mentor_workshop mw ON p.user_id = mw.user_id
                        WHERE p.type = 'teacher' AND mw.workshop_id = ?"; // Changed type to 'teacher'
    $stmtFindTeachers = $conn->prepare($sqlFindTeachers);
     if (!$stmtFindTeachers) throw new Exception("Prepare failed (find teachers): " . $conn->error);
    $stmtFindTeachers->bind_param("i", $workshopId);
     if (!$stmtFindTeachers->execute()) { $stmtFindTeachers->close(); throw new Exception("Execute failed (find teachers): " . $stmtFindTeachers->error); }
    $resultTeachers = $stmtFindTeachers->get_result();
    $teacherIds = [];
    while ($row = $resultTeachers->fetch_assoc()) { $teacherIds[] = (int)$row['user_id']; }
    $stmtFindTeachers->close();

    // 2b. Prepare INSERT for teacher rankings
    $sqlInsertTeacherRanking = "INSERT INTO rankings (event_workshop_id, user_id, ranking_number, user_type)
                                VALUES (?, ?, ?, 'teacher')"; // Changed type to 'teacher'
    $stmtInsertTeacher = $conn->prepare($sqlInsertTeacherRanking);
     if (!$stmtInsertTeacher) throw new Exception("Prepare failed (insert teacher ranking): " . $conn->error);

    // 3b. Insert teacher rankings sequentially (starting from 1 again)
    if (!empty($teacherIds)) {
        error_log("Found " . count($teacherIds) . " eligible teachers. Adding rankings...");
        $rankingNumber = 1; // Reset ranking number for teachers
        foreach ($teacherIds as $teacherId) {
            $stmtInsertTeacher->bind_param("iii", $eventWorkshopId, $teacherId, $rankingNumber);
            if (!$stmtInsertTeacher->execute()) throw new Exception("Execute failed (insert teacher ranking for user {$teacherId}): " . $stmtInsertTeacher->error);
            $rankingNumber++;
        }
    } else {
        error_log("No eligible teachers found.");
    }
    $stmtInsertTeacher->close();

    error_log("Successfully finished populating mentor rankings for event_workshop_id: {$eventWorkshopId}.");
}