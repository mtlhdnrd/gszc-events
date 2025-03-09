<?php
// update_student.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("user_id", "username", "name", "email", "teacher_id"))) {

    $user_id = intval($_POST["user_id"]); // Ensure integer
    $username = htmlspecialchars($_POST["username"]);
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    $teacher_id = intval($_POST["teacher_id"]); // Ensure integer

    // --- Input Validation (Crucial!) ---

    // 1. Basic String Length Checks
    if (strlen($username) < 5 || strlen($username) > 255) {
        http_response_code(400);
        echo "Username must be between 5 and 255 characters.";
        exit;
    }
    if (strlen($name) < 2 || strlen($name) > 255) {
        http_response_code(400);
        echo "Name must be between 2 and 255 characters.";
        exit;
    }

    // 2. Email Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Invalid email format.";
        exit;
    }

    // 3. Check if teacher_id exists (Foreign Key Check)
    $check_teacher_query = "SELECT 1 FROM teachers WHERE teacher_id = ?";
    $check_teacher_stmt = $conn->prepare($check_teacher_query);
    $check_teacher_stmt->bind_param("i", $teacher_id);
    $check_teacher_stmt->execute();
    if ($check_teacher_stmt->get_result()->num_rows === 0) {
        http_response_code(400);
        echo "Invalid teacher_id. Teacher does not exist.";
        $check_teacher_stmt->close();
        exit;
    }
     $check_teacher_stmt->close();

    //4. check if student exists
    $check_student_query = "SELECT 1 FROM students WHERE user_id = ?";
    $check_student_stmt = $conn->prepare($check_student_query);
    $check_student_stmt->bind_param("i", $user_id);
    $check_student_stmt->execute();
    if ($check_student_stmt->get_result()->num_rows === 0) {
        http_response_code(404); // 404 Not Found - Student doesn't exist
        echo "student does not exist.";
        $check_student_stmt->close();
        exit;
    }
    $check_student_stmt->close();

    // 5. Check for username conflicts (excluding the current user)
    $check_username_query = "SELECT 1 FROM users WHERE username = ? AND user_id != ?";
    $check_username_stmt = $conn->prepare($check_username_query);
    $check_username_stmt->bind_param("si", $username, $user_id);
    $check_username_stmt->execute();
    if ($check_username_stmt->get_result()->num_rows > 0) {
        http_response_code(409); // 409 Conflict - Username already taken
        echo "Username already taken.";
        $check_username_stmt->close();
        exit;
    }
     $check_username_stmt->close();


    // 6. Check for email conflicts (excluding the current student)
    $check_email_query = "SELECT 1 FROM students WHERE email = ? AND user_id != ?";
    $check_email_stmt = $conn->prepare($check_email_query);
    $check_email_stmt->bind_param("si", $email, $user_id);
    $check_email_stmt->execute();
    if ($check_email_stmt->get_result()->num_rows > 0) {
        http_response_code(409); // 409 Conflict - Email already in use
        echo "Email already in use.";
        $check_email_stmt->close();
        exit;
    }
    $check_email_stmt->close();


    // --- Transaction Start ---
    $conn->begin_transaction();

    try {
        // 1. Update `users` table
        $update_user_query = "UPDATE `users` SET `username` = ? WHERE `user_id` = ?";
        $update_user_stmt = $conn->prepare($update_user_query);
        $update_user_stmt->bind_param("si", $username, $user_id);
        $update_user_stmt->execute();
        $update_user_stmt->close();

        // 2. Update `students` table
        $update_student_query = "UPDATE `students` SET `name` = ?, `email` = ?, `teacher_id` = ? WHERE `user_id` = ?";
        $update_student_stmt = $conn->prepare($update_student_query);
        $update_student_stmt->bind_param("ssii", $name, $email, $teacher_id, $user_id);
        $update_student_stmt->execute();
        $update_student_stmt->close();

        // Commit Transaction
        $conn->commit();
        http_response_code(204);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo "Error updating student: " . $e->getMessage();
        echo "<img src='https://http.cat/500'>";
    }

} else {
      http_response_code(400);
      echo "Missing required parameters or invalid request method.";
      echo "<img src='https://http.cat/400'>";

}
?>