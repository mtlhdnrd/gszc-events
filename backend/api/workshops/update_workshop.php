<?php
require_once "../../config.php";
require_once "../../api_utils.php";
if (validate_request("POST", array("workshop_id", "name", "description"))) {
    $workshop_id = htmlspecialchars($_POST["workshop_id"]);
    $name = htmlspecialchars($_POST["name"]);
    $description = htmlspecialchars($_POST["description"]);
    $query = "UPDATE `workshops` SET `name` = ?, `description` = ? WHERE `workshop_id` = ?;";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $name, $description, $workshop_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200);
        } else {
            http_response_code(404);
            echo "Workshop not found or no changes made." . $workshop_id;
        }
    } else {
        echo $stmt->error;
        echo "<img src='https://http.cat/500'>";
        http_response_code(500);
    }
} else {
    echo "<img src='https://http.cat/400'>";
    http_response_code(400);
}
