<?php
// delete_event_workshop.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("DELETE", array("event_workshop_id"))) {

    $event_workshop_id = intval($_GET["event_workshop_id"]); 
    $check_query = "SELECT 1 FROM event_workshop WHERE event_workshop_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $event_workshop_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo "event_workshop_id not found.";
        echo "<img src='https://http.cat/404'>";
        http_response_code(404); 
        exit;
    }


    $query = "DELETE FROM `event_workshop` WHERE `event_workshop_id` = ?;";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_workshop_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
          http_response_code(204);
        } else {
          echo "No rows were deleted, check the id";
          http_response_code(500); 
        }
    } else {
        echo $stmt->error;
        echo "<img src='https://http.cat/500'>";
        http_response_code(500);
    }
    $check_stmt->close();

} else {
    echo "Missing required parameters or invalid request method.";
    echo "<img src='https://http.cat/400'>";
    http_response_code(400); 
}
?>