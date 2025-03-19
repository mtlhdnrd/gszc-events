<?php
        require_once "../../config.php";
        require_once "../../api_utils.php";
    if(validate_request("POST", array("name", "description"))) {
        $name = htmlspecialchars($_POST["name"]);
        $description = htmlspecialchars($_POST["description"]);
        $query = "INSERT INTO `workshops` (`name`, `description`) VALUES (?, ?);";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $name, $description);
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
