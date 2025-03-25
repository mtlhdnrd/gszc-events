<?php
require_once "../../config.php";
require_once "../../api_utils.php";

if (validate_request("GET", array())) {
    header("Content-Type: application/json");

    $school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : null;
    $teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;

    if ($teacher_id) {
        // Get a specific teacher by teacher_id
        $query = "SELECT t.`teacher_id`, t.`name`, t.`email`, t.`phone`
                  FROM `teachers` t
                  WHERE t.`teacher_id` = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(array("message" => "Prepare failed: " . $conn->error));
            exit();
        }

        $stmt->bind_param("i", $teacher_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode($result->fetch_assoc()); // Single teacher
                http_response_code(200);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(array("message" => "Teacher not found."));
            }
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Database error: " . $stmt->error));
        }
        $stmt->close();

    } elseif ($school_id) {
        // Get all teachers for a specific school
        $query = "SELECT t.`teacher_id`, t.`name`, t.`email`, t.`phone`
                  FROM `teachers` t
                  WHERE t.`school_id` = ?;";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(array("message" => "Prepare failed: " . $conn->error));
            exit();
        }

        $stmt->bind_param("i", $school_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            http_response_code(200);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Database error: " . $stmt->error));
        }
        $stmt->close();

    }    else {
        // Get all teachers (no school_id or teacher_id specified)
        $query = "SELECT t.`teacher_id`, t.`name`, t.`email`, t.`phone`
                  FROM `teachers` t;";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            echo json_encode(array("message" => "Database error: " . $conn->error));
            exit();
        }
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            http_response_code(200); //OK
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