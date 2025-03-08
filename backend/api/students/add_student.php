<?php
    require_once $_SERVER["DOCUMENT_ROOT"]."/bgszc-events/backend/config.php";
    require_once $_SERVER["DOCUMENT_ROOT"]."/bgszc-events/backend/api_utils.php";
    if(validate_request("POST", array("name", "date", "location", "status", "busyness"))) {
        $name = $_POST["name"];
        $date = $_POST["date"];
        $location = $_POST["location"];
        $status = $_POST["status"];
        $busyness = $_POST["busyness"];
        $query = "INSERT INTO `events` (`name`, `date`, `location`, `status`, `busyness`) VALUES (?, ?, ?, ?, ?);";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $name, $date, $location, $status, $busyness);
        if($stmt->execute()) {
            echo $stmt->insert_id;
            http_response_code(201);
        } else {
            echo $stmt->error;
            echo "<img src='https://http.cat/500'>";
            http_response_code(500);
        }
    } else {
        echo "<img src='https://http.cat/400'>";
        http_response_code(400);
    }