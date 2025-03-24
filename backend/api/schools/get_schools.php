<?php
require_once "../../config.php";
require_once "../../api_utils.php";

if (validate_request("GET", array())) {
    header("Content-Type: application/json");

    // Check if a specific school is requested
    if (isset($_GET['school_id'])) {
        $school_id = (int)$_GET['school_id'];  // Cast to integer for security

        $query = "SELECT `school_id`, `name`, `address` FROM `schools` WHERE `school_id` = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $school_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode($result->fetch_assoc());
                http_response_code(200);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(array("message" => "School not found."));
            }
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Database error: " . $stmt->error));
        }
        $stmt->close();


    } else {
        $query = "SELECT `school_id`, `name`, `address` FROM `schools`;";
        $stmt = $conn->prepare($query);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            http_response_code(200);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(array("message" => "Database error: " . $stmt->error));
        }
        $stmt->close();
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid request."));
}

?>