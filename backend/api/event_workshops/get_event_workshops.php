<?php
    require_once "../../config.php";
    require_once "../../api_utils.php";

if (validate_request("GET", array())) {

    $query = "SELECT 
                ew.event_workshop_id,
                e.name AS event_name,
                w.name AS workshop_name,
                e.event_id AS event_id,
                w.workshop_id AS workshop_id,
                ew.number_of_mentors_required,
                ew.max_workable_hours,
                ew.busyness
              FROM event_workshop ew
              INNER JOIN events e ON ew.event_id = e.event_id
              INNER JOIN workshops w ON ew.workshop_id = w.workshop_id;";

    $stmt = $conn->prepare($query);

    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $event_workshops = array();

        while ($row = $result->fetch_assoc()) {
            $event_workshops[] = $row;
        }

        header('Content-Type: application/json');
        echo json_encode($event_workshops);
        http_response_code(200);

    } catch (Exception $e) {
        echo $e;
        echo "<img src='https://http.cat/500'>";
        http_response_code(500);
    }
} else {
    echo "Invalid request method.";
    echo "<img src='https://http.cat/400'>";
    http_response_code(400);
}
?>