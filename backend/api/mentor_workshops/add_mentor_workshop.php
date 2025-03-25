<?php
require_once "../../config.php";
require_once "../../api_utils.php";

// Get and decode JSON data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (empty($data) || !isset($data['user_id'], $data['workshop_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid data.  Need user_id and workshop_id."));
    exit();
}

$user_id = (int)$data['user_id'];
$workshop_id = (int)$data['workshop_id'];
$ranking_number = (int)$data['ranking_number'];

// --- Validation ---
if (!$user_id || !$workshop_id)
{
    http_response_code(400);
    echo json_encode(["message" => "Missing user or workshop id"]);
    exit();
}


// --- Database Operations (within a transaction) ---
$conn->begin_transaction();

try {
    // 1. DELETE existing entries for the user
    $delete_query = "DELETE FROM `mentor_workshop` WHERE `user_id` = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $user_id);

    if (!$delete_stmt->execute()) {
        throw new Exception("Delete failed: " . $delete_stmt->error);
    }
    $delete_stmt->close();

    //Check if IDs are exists.
    $check_query = "SELECT 1 FROM participants WHERE user_id = ? UNION ALL SELECT 1 FROM workshops WHERE workshop_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $user_id, $workshop_id);
    if(!$check_stmt->execute()) {
        throw new Exception("Error while checking the ids: ". $check_stmt->error);
    }
    $check_result = $check_stmt->get_result();
    if($check_result->num_rows < 2) {
        throw new Exception("User or workshop not found");
    }
    $check_stmt->close();

    // 2. INSERT the new entry
    $insert_query = "INSERT INTO `mentor_workshop` (`user_id`, `workshop_id`, `ranking_number`) VALUES (?, ?, ?);";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iii", $user_id, $workshop_id, $ranking_number);

    if (!$insert_stmt->execute()) {
        throw new Exception("Insert failed: " . $insert_stmt->error);
    }
    $insert_stmt->close();

    // Commit the transaction
    $conn->commit();
    http_response_code(201); // Created
    echo json_encode(array("message" => "Mentor-workshop association created/updated."));

} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Error: " . $e->getMessage()));
}
?>