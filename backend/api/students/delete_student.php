<?php
// delete_student.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("DELETE", array("user_id"))) {

    $user_id = intval($_GET["user_id"]); // Ensure integer

    // --- Check if Student Exists (Important!) ---

    $check_query = "SELECT 1 FROM students WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        http_response_code(404); // 404 Not Found - Student not found
        echo "Student with user_id $user_id not found.";
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();

    // --- Transaction Start ---
    $conn->begin_transaction();

    try {
        $delete_student_query = "DELETE FROM `students` WHERE `user_id` = ?";
        $delete_student_stmt = $conn->prepare($delete_student_query);
        $delete_student_stmt->bind_param("i", $user_id);
        $delete_student_stmt->execute();
        $delete_student_stmt->close();

        $delete_user_query = "DELETE FROM `users` WHERE `user_id` = ?";
        $delete_user_stmt = $conn->prepare($delete_user_query);
        $delete_user_stmt->bind_param("i", $user_id);
        $delete_user_stmt->execute();
        $delete_user_stmt->close();
        $conn->commit();
        http_response_code(204); // 204 No Content - Successful deletion

    } catch (Exception $e) {
        // Rollback Transaction on Error
        $conn->rollback();
        http_response_code(500);
        echo "Error deleting student: " . $e->getMessage();
        echo "<img src='https://http.cat/500'>";
    }

} else {
    http_response_code(400); // 400 Bad Request - Missing parameters
    echo "Missing required parameters (user_id).";
    echo "<img src='https://http.cat/400'>";
}
?>