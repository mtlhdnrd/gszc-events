<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("user_id", "workshop_id"))) {

    $user_id = intval($_POST["user_id"]);      
    $workshop_id = intval($_POST["workshop_id"]); 

    // --- Input Validation ---

    $check_user_query = "SELECT 1 FROM students WHERE user_id = ?";
    $check_user_stmt = $conn->prepare($check_user_query);
    $check_user_stmt->bind_param("i", $user_id);
    $check_user_stmt->execute();
    if ($check_user_stmt->get_result()->num_rows === 0) {
        http_response_code(400);
        echo "Invalid user_id.  User is not a student or does not exist.";
        $check_user_stmt->close();
        exit;
    }
    $check_user_stmt->close();

    $check_workshop_query = "SELECT 1 FROM workshops WHERE workshop_id = ?";
    $check_workshop_stmt = $conn->prepare($check_workshop_query);
    $check_workshop_stmt->bind_param("i", $workshop_id);
    $check_workshop_stmt->execute();
    if ($check_workshop_stmt->get_result()->num_rows === 0) {
        http_response_code(400);
        echo "Invalid workshop_id. Workshop does not exist.";
        $check_workshop_stmt->close();
        exit;
    }
    $check_workshop_stmt->close();

    $check_record_query = "SELECT 1 FROM mentor_workshop WHERE user_id = ? and workshop_id = ?";
    $check_record_stmt = $conn->prepare($check_record_query);
    $check_record_stmt->bind_param("ii", $user_id, $workshop_id);
    $check_record_stmt->execute();
    if ($check_record_stmt->get_result()->num_rows > 0) {
        http_response_code(409); 
        echo "student workshop already exists.";
        $check_record_stmt->close();
        exit;
    }
     $check_record_stmt->close();

    $query = "INSERT INTO `mentor_workshop` (`user_id`, `workshop_id`) VALUES (?, ?);";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $workshop_id);

    if ($stmt->execute()) {
        echo $stmt->insert_id; 
        http_response_code(201); 
    } else {
        http_response_code(500);
        echo "Error adding student to workshop: " . $stmt->error;
        echo "<img src='https://http.cat/500'>";

    }
    $stmt->close();

} else {
    http_response_code(400);
    echo "Missing required parameters.";
    echo "<img src='https://http.cat/400'>";

}
?>