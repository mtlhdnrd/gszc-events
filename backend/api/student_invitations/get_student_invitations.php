<?php
// get_student_invitations.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("GET", array())) {

    $query = "SELECT
                si.invitation_id,
                si.event_workshop_id,
                si.user_id,
                si.status,
                e.name AS event_name,
                w.name AS workshop_name,
                s.name AS student_name
              FROM student_invitations si
              INNER JOIN event_workshop ew ON si.event_workshop_id = ew.event_workshop_id
              INNER JOIN events e ON ew.event_id = e.event_id
              INNER JOIN workshops w ON ew.workshop_id = w.workshop_id
              INNER JOIN students s ON si.user_id = s.user_id;";

    $stmt = $conn->prepare($query);

    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $invitations = [];

        while ($row = $result->fetch_assoc()) {
            $invitations[] = $row;
        }

        header('Content-Type: application/json');
        echo json_encode($invitations);
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