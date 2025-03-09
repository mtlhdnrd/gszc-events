<?php
// add_student.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("username", "password", "name", "email", "teacher_id", "school_id"))) {

    $username = htmlspecialchars($_POST["username"]);
    $password = htmlspecialchars($_POST["password"]);
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    $teacher_id = intval($_POST["teacher_id"]);  // Ensure integer
    $school_id = intval($_POST["school_id"]);    // Ensure integer

    // --- Input Validation (Crucial!) ---

    if (strlen($username) < 5 || strlen($username) > 255) {
        http_response_code(400);
        echo "Username must be between 5 and 255 characters.";
        exit;
    }
    if (strlen($password) < 8) { // Enforce a minimum password length
        http_response_code(400);
        echo "Password must be at least 8 characters.";
        exit;
    }
    if (strlen($name) < 2 || strlen($name) > 255) {
        http_response_code(400);
        echo "Name must be between 2 and 255 characters.";
        exit;
    }

    // 2. Email Validation (Use filter_var)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Invalid email format.";
        exit;
    }

    // 3. Check if username already exists (Important for uniqueness)
    $check_username_query = "SELECT 1 FROM users WHERE username = ?";
    $check_username_stmt = $conn->prepare($check_username_query);
    $check_username_stmt->bind_param("s", $username);
    $check_username_stmt->execute();
    $check_username_result = $check_username_stmt->get_result();
    if ($check_username_result->num_rows > 0) {
        http_response_code(409); // 409 Conflict - Username already exists
        echo "Username already exists.";
        $check_username_stmt->close();
        exit;
    }
     $check_username_stmt->close();

    // 4. Check if email already exists (Important for uniqueness constraint)
    $check_email_query = "SELECT 1 FROM students WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_query);
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $check_email_result = $check_email_stmt->get_result();
    if ($check_email_result->num_rows > 0) {
        http_response_code(409); // 409 Conflict - Email already exists
        echo "Email already exists.";
        $check_email_stmt->close();
        exit;
    }
    $check_email_stmt->close();


    // 5. Check if teacher_id and school_id exist (Foreign Key Checks)
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

    $check_school_query = "SELECT 1 FROM schools WHERE school_id = ?";
    $check_school_stmt = $conn->prepare($check_school_query);
    $check_school_stmt->bind_param("i", $school_id);
    $check_school_stmt->execute();
    if ($check_school_stmt->get_result()->num_rows === 0) {
        http_response_code(400);
        echo "Invalid school_id. School does not exist.";
        $check_school_stmt->close();
        exit;
    }
    $check_school_stmt->close();


    // --- Transaction Start ---
    $conn->begin_transaction();

    try {
        // 1. Insert into `users` table
        //$hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password!
        $insert_user_query = "INSERT INTO `users` (`username`, `password`) VALUES (?, ?)";
        $insert_user_stmt = $conn->prepare($insert_user_query);
        $insert_user_stmt->bind_param("ss", $username, $password);
        $insert_user_stmt->execute();
        $user_id = $insert_user_stmt->insert_id;  // Get the newly created user_id
        $insert_user_stmt->close();

        // 2. Insert into `students` table
        $insert_student_query = "INSERT INTO `students` (`user_id`, `name`, `email`, `teacher_id`, `school_id`) VALUES (?, ?, ?, ?, ?)";
        $insert_student_stmt = $conn->prepare($insert_student_query);
        $insert_student_stmt->bind_param("issii", $user_id, $name, $email, $teacher_id, $school_id);
        $insert_student_stmt->execute();
        $insert_student_stmt->close();

        // Commit Transaction
        $conn->commit();
        http_response_code(201); // 201 Created
        echo $user_id; // Return the user_id

    } catch (Exception $e) {
        // Rollback Transaction on Error
        $conn->rollback();
        http_response_code(500);
        echo "Error creating student: " . $e->getMessage();
        echo "<img src='https://http.cat/500'>";
    }

} else {
    http_response_code(400);
    echo "Missing required parameters.";
    echo "<img src='https://http.cat/400'>";
}
?>