<?php
// add_ranking.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("event_workshop_id", "user_id", "ranking_number"))) {

    $event_workshop_id = intval($_POST["event_workshop_id"]);
    $user_id = intval($_POST["user_id"]);
    $ranking_number = intval($_POST["ranking_number"]);

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

    // 3. Check if ranking_number is a positive integer
    if ($ranking_number <= 0) {
        http_response_code(400);
        echo "ranking_number must be a positive integer.";
        exit;
    }

    // 4. Check for duplicate ranking (event_workshop_id, user_id) - IMPORTANT!
    $check_duplicate_query = "SELECT 1 FROM rankings WHERE event_workshop_id = ? AND user_id = ?";
    $check_duplicate_stmt = $conn->prepare($check_duplicate_query);
    $check_duplicate_stmt->bind_param("ii", $event_workshop_id, $user_id);
    $check_duplicate_stmt->execute();
    if ($check_duplicate_stmt->get_result()->num_rows > 0) {
        http_response_code(409); 
        echo "A ranking for this user and event workshop already exists.";
        $check_duplicate_stmt->close();
        exit;
    }
     $check_duplicate_stmt->close();


    $query = "INSERT INTO `rankings` (`event_workshop_id`, `user_id`, `ranking_number`) VALUES (?, ?, ?);";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $event_workshop_id, $user_id, $ranking_number);

    if ($stmt->execute()) {
        echo $stmt->insert_id;
        http_response_code(201);
    } else {
        http_response_code(500);
        echo "Error adding ranking: " . $stmt->error;
        echo "<img src='https://http.cat/500'>";

    }
    $stmt->close();

} else {
    http_response_code(400);
    echo "Missing required parameters.";
    echo "<img src='https://http.cat/400'>";

}
?>