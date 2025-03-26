<?php
require_once "../../config.php";
require_once "../../api_utils.php";

// Get and decode JSON data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Basic validation
if (empty($data) || !isset($data['user_id'], $data['workshop_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid data. Need user_id and workshop_id."));
    exit();
}

$user_id = (int)$data['user_id'];
$workshop_id = (int)$data['workshop_id'];
$pending_status = 'pending'; 

// --- Database Operations (within a transaction) ---
$conn->begin_transaction();

try {
    // --- Get the user's type ---
    $type_query = "SELECT type FROM participants WHERE user_id = ?";
    $type_stmt = $conn->prepare($type_query);
    if (!$type_stmt) {
        throw new Exception("Prepare failed (get type): " . $conn->error);
    }
    $type_stmt->bind_param("i", $user_id);
    $type_stmt->execute();
    $type_result = $type_stmt->get_result();
    $user_data = $type_result->fetch_assoc();
    $type_stmt->close();

    if (!$user_data || !in_array($user_data['type'], ['student', 'teacher'])) {
        throw new Exception("User not found or has invalid type.");
    }
    $user_type = $user_data['type'];
    // --- End Get User Type ---


    // 1. Find relevant event_workshop_ids for pending events
    $find_query = "SELECT ew.event_workshop_id
                   FROM event_workshop ew
                   JOIN events e ON ew.event_id = e.event_id
                   WHERE ew.workshop_id = ? AND e.status = ?";

    $find_stmt = $conn->prepare($find_query);
    if (!$find_stmt) throw new Exception("Prepare failed (find event workshops): " . $conn->error);
    $find_stmt->bind_param("is", $workshop_id, $pending_status);
    $find_stmt->execute();
    $result = $find_stmt->get_result();
    $event_workshop_ids = [];
    while ($row = $result->fetch_assoc()) {
        $event_workshop_ids[] = $row['event_workshop_id'];
    }
    $find_stmt->close();

    if (empty($event_workshop_ids)) {
        $conn->commit();
        http_response_code(200);
        echo json_encode(array("message" => "No pending events found for this workshop. No rankings added."));
        exit();
    }

    // Prepare statements for ranking calculation and insertion outside the loop
    // --- MODIFIED RANK QUERY ---
    $rank_query = "SELECT MAX(ranking_number) as max_rank
                   FROM rankings
                   WHERE event_workshop_id = ? AND user_type = ?";
    $rank_stmt = $conn->prepare($rank_query);

    // --- MODIFIED INSERT QUERY ---
    $insert_query = "INSERT INTO rankings (event_workshop_id, user_id, ranking_number, user_type)
                     VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);

    if (!$rank_stmt || !$insert_stmt) {
        throw new Exception("Prepare statement failed (rank/insert): " . $conn->error);
    }

    // 2. Loop through each relevant event_workshop and add the user to the ranking
    foreach ($event_workshop_ids as $ew_id) {
        // Determine the next ranking number *for the specific user type*
        // --- BIND USER TYPE TO RANK QUERY ---
        $rank_stmt->bind_param("is", $ew_id, $user_type);
        $rank_stmt->execute();
        $rank_result = $rank_stmt->get_result()->fetch_assoc();
        $next_ranking = ($rank_result['max_rank'] ?? 0) + 1;

        // --- BIND USER TYPE TO INSERT QUERY ---
        $insert_stmt->bind_param("iiis", $ew_id, $user_id, $next_ranking, $user_type);
        if (!$insert_stmt->execute()) {
            if ($conn->errno == 1062) { // Handle potential duplicate entry
                error_log("User $user_id already ranked for event_workshop $ew_id with type $user_type. Skipping insert.");
                continue;
            } else {
                throw new Exception("Insert into rankings failed for event_workshop_id $ew_id: " . $insert_stmt->error);
            }
        }
    }

    // Close prepared statements
    $rank_stmt->close();
    $insert_stmt->close();

    // Commit the transaction
    $conn->commit();
    http_response_code(200); // OK
    echo json_encode(array("message" => "Mentor ($user_type) added to rankings for " . count($event_workshop_ids) . " pending events."));

} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Error adding mentor to rankings: " . $e->getMessage()));
}
?>