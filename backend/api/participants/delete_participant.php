<?php
require_once "../../config.php";
require_once "../../api_utils.php";

if (validate_request("DELETE", array("user_id"))) {
    header("Content-Type: application/json");

    $user_id = (int)$_GET['user_id']; // Cast to integer

    // --- Input Validation ---
    if (empty($user_id)) {
        http_response_code(400); // Bad Request
        echo json_encode(array("message" => "user_id is required."));
        exit();
    }
    // Check if participant exists before attempting to delete
    $check_query = "SELECT user_id FROM participants WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(array("message" => "Participant with the specified user_id not found."));
         $check_stmt->close();
        exit();
    }
    $check_stmt->close();


    // --- Delete from Database ---
    $query = "DELETE FROM participants WHERE user_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(array("message" => "Prepare failed: " . $conn->error));
        exit();
    }

    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200); // OK
            echo json_encode(array("message" => "Participant deleted successfully."));
        } else {
            http_response_code(404); // Not Found - Or 500, as it indicates a logic error.
            echo json_encode(array("message" => "Participant not found or already deleted."));
        }
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Database error: " . $stmt->error));
    }

    $stmt->close();

} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid request."));
}
?>