<?php
require_once "../../config.php";
require_once "../../api_utils.php";

if (validate_request("GET", array("type"))) {
    header("Content-Type: application/json");

    $type = $_GET['type'];

    $query = "SELECT 
                p.user_id, 
                p.name, 
                p.email, 
                p.school_id,
                s.name AS school_name,
                p.teacher_id,
                t.name AS teacher_name,
                u.username
              FROM participants p
              INNER JOIN schools s ON p.school_id = s.school_id
              LEFT JOIN teachers t ON p.teacher_id = t.teacher_id
              INNER JOIN users u ON p.user_id = u.user_id
              WHERE p.type = ?;";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(array("message" => "Prepare failed: " . $conn->error));
        exit();
    }

    $stmt->bind_param("s", $type);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        http_response_code(200);
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Database error: " . $stmt->error));
    }

    $stmt->close();

} else {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid request. 'type' parameter must be 'student' or 'teacher'."));
}
?>