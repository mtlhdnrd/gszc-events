<?php
// get_ranking.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("GET", array("event_workshop_id"))) { 

    $event_workshop_id = intval($_GET["event_workshop_id"]); 

    $check_ew_query = "SELECT 1 FROM event_workshop WHERE event_workshop_id = ?";
    $check_ew_stmt = $conn->prepare($check_ew_query);
    $check_ew_stmt->bind_param("i", $event_workshop_id);
    $check_ew_stmt->execute();
    if ($check_ew_stmt->get_result()->num_rows === 0) {
        http_response_code(404); // 404 Not Found
        echo json_encode(["error" => "event_workshop_id not found."]);
        $check_ew_stmt->close();
        exit;
    }
    $check_ew_stmt->close();

    $query = "SELECT
                r.ranking_id,
                r.event_workshop_id,
                r.user_id,
                u.username,
                r.ranking_number
              FROM rankings r
              INNER JOIN users u ON r.user_id = u.user_id
              WHERE r.event_workshop_id = ?;"; 

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_workshop_id); 

    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $rankings = [];

        while ($row = $result->fetch_assoc()) {
            $rankings[] = $row;
        }

        header('Content-Type: application/json');
        echo json_encode($rankings);
        http_response_code(200);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        echo "<img src='https://http.cat/500'>";
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request method or missing event_workshop_id."]);
     echo "<img src='https://http.cat/400'>";

}
?>