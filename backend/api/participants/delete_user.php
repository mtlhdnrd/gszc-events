<?php
require_once "../../config.php";
require_once "../../api_utils.php";

if (validate_request("DELETE", array()) && isset($_GET['user_id'])) {
    header("Content-Type: application/json");

    $user_id = (int)$_GET['user_id'];  //Cast to integer.

    // Input validation
    if (empty($user_id)) {
        http_response_code(400);
        echo json_encode(array("message" => "user_id parameter is required"));
        exit();
    }
     // Check if user exists before attempting to delete
    $check_query = "SELECT user_id FROM users WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
         http_response_code(404); // Not Found
        echo json_encode(array("message" => "User with the specified user_id not found."));
        $check_stmt->close();
        exit();
    }
     $check_stmt->close();
    // --- Delete from Database ---
    // Start transaction
    $conn->begin_transaction();
    try {
        $delete_query = "DELETE FROM users WHERE user_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $user_id);
        if (!$delete_stmt->execute()) {
           throw new Exception("Delete failed: " . $delete_stmt->error);
        }

        // Commit the transaction
        $conn->commit();
        http_response_code(200); // OK
        echo json_encode(array("message" => "User deleted successfully."));

    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        http_response_code(500); // Internal Server Error
        echo json_encode(array("message" => "Error: " . $e->getMessage()));
    } finally {
        if (isset($delete_stmt)) {
            $delete_stmt->close(); // Close the statement
        }
    }

} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid request."));
}

?>