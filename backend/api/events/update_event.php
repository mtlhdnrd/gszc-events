<?php
    require_once $_SERVER["DOCUMENT_ROOT"]."/bgszc-events/backend/config.php";
    require_once $_SERVER["DOCUMENT_ROOT"]."/bgszc-events/backend/api_utils.php";
    require_once $_SERVER["DOCUMENT_ROOT"]."/bgszc-events/backend/api/events/event.php";
    if(validate_request("POST", array("event_id", "name", "date", "location", "status", "busyness"))) {
        $event_id = $_POST["event_id"];
        $name = $_POST["name"];
        $date = $_POST["date"];
        $location = $_POST["location"];
        $status = $_POST["status"];
        $busyness = $_POST["busyness"];
        $query = "UPDATE `events` SET `name` = ?, `date` = ?, `location` = ?, `status` = ?, `busyness` = ? WHERE `event_id` = ?;";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $name, $date, $location, $status, $busyness, $event_id);
        if($stmt->execute()) {
            http_response_code(200);
        } else {
            echo $stmt->error;
            echo "<img src='https://http.cat/500'>";
            http_response_code(500);
        }
    } else {
        echo "<img src='https://http.cat/400'>";
        http_response_code(400);
    }
