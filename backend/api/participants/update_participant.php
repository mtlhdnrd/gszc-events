<?php
require_once "../../config.php";
require_once "../../api_utils.php";

// Define expected fields. Include user_id, as we need it to identify the participant.
$expected_fields = array("user_id", "name", "email", "type", "school_id", "teacher_id");

if (validate_request("POST", $expected_fields)) {
    header("Content-Type: application/json");

    $user_id = (int)$_POST['user_id'];  // Ensure it's an integer.
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    $type = htmlspecialchars($_POST["type"]);
    $school_id = (int)$_POST["school_id"];
    // Handle optional teacher_id:  Allow for 'null' string, and convert to NULL.
    $teacher_id = isset($_POST["teacher_id"]) && $_POST["teacher_id"] !== 'null' ? (int)$_POST["teacher_id"] : null;


    // --- Input Validation ---
    if (empty($name) || empty($email) || empty($type) || empty($school_id)) {
        http_response_code(400);
        echo json_encode(array("message" => "Required fields are missing."));
        exit();
    }
     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid email format."));
        exit();
    }

    if (!in_array($type, ['student', 'teacher'])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid participant type."));
        exit();
    }
    // Check if user ID exists
    $user_check_query = "SELECT user_id FROM users WHERE user_id = ?";
    $user_check_stmt = $conn->prepare($user_check_query);
    $user_check_stmt->bind_param("i", $user_id);
    $user_check_stmt->execute();
    $user_check_result = $user_check_stmt->get_result();
    if ($user_check_result->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(array("message" => "User with the provided user_id does not exist."));
        $user_check_stmt->close();
        exit();
    }
    $user_check_stmt->close();

    // Check if school exists:
    $school_check_query = "SELECT school_id FROM schools WHERE school_id = ?";
    $school_check_stmt = $conn->prepare($school_check_query);
    $school_check_stmt->bind_param("i", $school_id);
    $school_check_stmt->execute();
    $school_check_result = $school_check_stmt->get_result();

    if ($school_check_result->num_rows === 0) {
        http_response_code(400); // Bad Request
        echo json_encode(array("message" => "Invalid school_id."));
        $school_check_stmt->close();
        exit();
    }
    $school_check_stmt->close();
     // If type is student, teacher_id must be provided and valid
    if($type == 'student') {
        if(empty($teacher_id)) { //Checks if empty
            http_response_code(400);
            echo json_encode(["message" => "teacher_id is required, when participant is student"]);
            exit();
        }
         //Check if teacher exists:
        $teacher_check_query = "SELECT teacher_id FROM teachers WHERE teacher_id = ?";
        $teacher_check_stmt = $conn->prepare($teacher_check_query);
        $teacher_check_stmt->bind_param("i", $teacher_id);
        $teacher_check_stmt->execute();
        $teacher_check_result = $teacher_check_stmt->get_result();

        if ($teacher_check_result->num_rows === 0) {
            http_response_code(400); // Bad Request
            echo json_encode(array("message" => "Invalid teacher_id."));
             $teacher_check_stmt->close();
            exit();
        }
         $teacher_check_stmt->close();
    }

    // --- Update Database ---

    $query = "";
    if($type=='student')
    {
        $query = "UPDATE participants SET name = ?, email = ?, school_id = ?, teacher_id = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
    
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(array("message" => "Prepare failed: " . $conn->error));
            exit();
        }
    
        $stmt->bind_param("ssiii", $name, $email, $school_id, $teacher_id, $user_id);
    }else{
        $query = "UPDATE participants SET name = ?, email = ?, school_id = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
    
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(array("message" => "Prepare failed: " . $conn->error));
            exit();
        }
    
        $stmt->bind_param("ssii", $name, $email, $school_id, $user_id);

    }
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Rows were updated
            http_response_code(200); // OK
            echo json_encode(array("message" => "Participant updated successfully."));
        } else {
            // No rows were updated (participant might not exist, or data was the same)
            http_response_code(404); // Not Found - or 200 OK with a different message
            echo json_encode(array("message" => "Participant not found or no changes made."));
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