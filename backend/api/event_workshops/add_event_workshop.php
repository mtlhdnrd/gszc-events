<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("event_id", "workshop_id", "max_workable_hours","number_of_mentors_required", "busyness"))) {

    $event_id = intval($_POST["event_id"]);          
    $workshop_id = intval($_POST["workshop_id"]);      
    $max_workable_hours = intval($_POST["max_workable_hours"]);
    $number_of_mentors_required = intval($_POST["number_of_mentors_required"]);
    $busyness = htmlspecialchars($_POST["busyness"]);

    
    $check_event_query = "SELECT 1 FROM events WHERE event_id = ?";
    $check_event_stmt = $conn->prepare($check_event_query);
    $check_event_stmt->bind_param("i", $event_id);
    $check_event_stmt->execute();
    $check_event_result = $check_event_stmt->get_result();

    $check_workshop_query = "SELECT 1 FROM workshops WHERE workshop_id = ?";
    $check_workshop_stmt = $conn->prepare($check_workshop_query);
    $check_workshop_stmt->bind_param("i", $workshop_id);
    $check_workshop_stmt->execute();
    $check_workshop_result = $check_workshop_stmt->get_result();


    if ($check_event_result->num_rows === 0) {
        echo "Invalid event_id. Event does not exist.";
        echo "<img src='https://http.cat/400'>";
        http_response_code(400);
        exit; 
    }

    if ($check_workshop_result->num_rows === 0) {
        echo "Invalid workshop_id. Workshop does not exist.";
        echo "<img src='https://http.cat/400'>";
        http_response_code(400);
        exit; 
    }

    $query = "INSERT INTO `event_workshop` (`event_id`, `workshop_id`, `max_workable_hours`, `number_of_mentors_required`, `busyness`) VALUES (?, ?, ?, ?, ?);";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $event_id, $workshop_id, $max_workable_hours, $number_of_mentors_required, $busyness);

    if ($stmt->execute()) {
        echo $stmt->insert_id;  
        http_response_code(201); 
    } else {
        echo $stmt->error;
        echo "<img src='https://http.cat/500'>";
        http_response_code(500); 
    }

    $check_event_stmt->close();
    $check_workshop_stmt->close();

} else {
    echo "Missing required parameters.";
    echo "<img src='https://http.cat/400'>";
    http_response_code(400);
}
?>