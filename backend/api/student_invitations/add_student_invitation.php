<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("event_workshop_id", "user_id", "status"))) {

    $event_workshop_id = intval($_POST["event_workshop_id"]);
    $user_id = intval($_POST["user_id"]);
    $status = $_POST["status"];

    // --- Input Validation ---

    // 1. Check if event_workshop_id exists
    $check_ew_query = "SELECT 1 FROM event_workshop WHERE event_workshop_id = ?";
    $check_ew_stmt = $conn->prepare($check_ew_query);
    $check_ew_stmt->bind_param("i", $event_workshop_id);
    $check_ew_stmt->execute();
    if ($check_ew_stmt->get_result()->num_rows === 0) {
        http_response_code(400);
        echo "Invalid event_workshop_id. Event workshop does not exist.";
         $check_ew_stmt->close();
        exit;
    }
     $check_ew_stmt->close();

    // 2. Check if user_id exists (and is a student)
    $check_user_query = "SELECT 1 FROM students WHERE user_id = ?";
    $check_user_stmt = $conn->prepare($check_user_query);
    $check_user_stmt->bind_param("i", $user_id);
    $check_user_stmt->execute();
    if ($check_user_stmt->get_result()->num_rows === 0) {
        http_response_code(400);
        echo "Invalid user_id. User is not a student or does not exist.";
        $check_user_stmt->close();
        exit;
    }
     $check_user_stmt->close();

    // 3. Validate status (optional, but good practice)
    $valid_statuses = ["pending", "accepted", "refused", "re-accepted"];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo "Invalid status.  Must be one of: " . implode(", ", $valid_statuses);
        exit;
    }

    // 4. Check for duplicate invitation (event_workshop_id, user_id)
    $check_duplicate_query = "SELECT 1 FROM student_invitations WHERE event_workshop_id = ? AND user_id = ?";
    $check_duplicate_stmt = $conn->prepare($check_duplicate_query);
    $check_duplicate_stmt->bind_param("ii", $event_workshop_id, $user_id);
    $check_duplicate_stmt->execute();
    if ($check_duplicate_stmt->get_result()->num_rows > 0) {
        http_response_code(409); // 409 Conflict
        echo "An invitation for this student and event workshop already exists.";
        $check_duplicate_stmt->close();
        exit;
    }
    $check_duplicate_stmt->close();

    // --- Insert into `student_invitations` ---
    $query = "INSERT INTO `student_invitations` (`event_workshop_id`, `user_id`, `status`) VALUES (?, ?, ?);";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $event_workshop_id, $user_id, $status);

    if ($stmt->execute()) {
        echo $stmt->insert_id; // Return the ID of the newly created row
        http_response_code(201); // 201 Created
    } else {
        http_response_code(500);
        echo "Error adding invitation: " . $stmt->error;
        echo "<img src='https://http.cat/500'>";

    }
     $stmt->close();

} else {
    http_response_code(400);
    echo "Missing required parameters.";
    echo "<img src='https://http.cat/400'>";

}
?>