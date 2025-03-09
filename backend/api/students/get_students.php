<?php
// get_students.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("GET", array())) {

    $query = "SELECT
                u.user_id,
                u.username,
                s.name AS student_name,
                s.email,
                t.name AS headTeacherName,
                t.teacher_id AS headTeacherId,
                sch.name AS schoolName,
                sch.school_id,
                s.total_hours_worked
              FROM students s
              INNER JOIN users u ON s.user_id = u.user_id
              INNER JOIN teachers t ON s.teacher_id = t.teacher_id
              INNER JOIN schools sch ON s.school_id = sch.school_id;";

    $stmt = $conn->prepare($query);

    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $students = [];

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        header('Content-Type: application/json');
        echo json_encode($students);
        http_response_code(200);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        echo "<img src='https://http.cat/500'>";

    }
} else {
    http_response_code(400); 
     echo json_encode(["error" => "Invalid request method."]);
     echo "<img src='https://http.cat/400'>";
}
?>