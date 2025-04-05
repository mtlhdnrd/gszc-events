<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";

$userId = $_GET['userId'] ?? null;

if (validate_request("GET", array("userId")) && $userId !== null) { // Nem kell kötelező paraméter, csak a userId

    if (!is_numeric($userId)) {
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Invalid userId format."]);
        exit;
    }

    $query = "SELECT
                si.invitation_id,
                si.event_workshop_id,
                si.user_id,
                si.status,
                e.name AS event_name,
                w.name AS workshop_name,
                s.name AS student_name,
                ew.event_id,
                ew.workshop_id,
                si.ranking_number,
                ew.max_workable_hours,
                ew.number_of_mentors_required,
                DATE_FORMAT(e.date, '%Y-%m-%d %H:%i') AS date
              FROM participant_invitations si
              INNER JOIN event_workshop ew ON si.event_workshop_id = ew.event_workshop_id
              INNER JOIN events e ON ew.event_id = e.event_id
              INNER JOIN workshops w ON ew.workshop_id = w.workshop_id
              INNER JOIN participants s ON si.user_id = s.user_id
              WHERE si.user_id = ?"; 

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId); // "i" - integer

    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $invitations = [];

        while ($row = $result->fetch_assoc()) {
            $invitations[] = $row;
        }

        header('Content-Type: application/json');
        echo json_encode($invitations); // Mindig tömböt adunk vissza
        http_response_code(200);


    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
     http_response_code(400);
    echo json_encode(["error" => "Invalid request method or missing userId."]);
}
?>