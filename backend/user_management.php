<?php
    require_once $_SERVER["DOCUMENT_ROOT"]."/bgszc-events/backend/config.php";
    require_once $_SERVER["DOCUMENT_ROOT"]."/bgszc-events/backend/api_utils.php";

    function add_user($username, $hash): int | false {
        global $conn;
        $query = "INSERT INTO `users` (`username`, `password`) VALUES (?, ?);";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $hash);
        if($stmt->execute()) {
            return $stmt->insert_id;
        } else {
            return false;
        }
    }
