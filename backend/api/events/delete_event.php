<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("event_id"))) {
    $event_id = $_POST["event_id"];

    if (!is_numeric($event_id)) {
        http_response_code(400);
        echo json_encode(['message' => 'Érvénytelen esemény azonosító.']);
        exit; 
    }

    $query = "DELETE FROM `events` WHERE `event_id` = ?";
    $stmt = $conn->prepare($query);

    $stmt->bind_param("i", $event_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Esemény sikeresen törölve.']);
        } else {
            http_response_code(404); 
            echo json_encode(['message' => 'A megadott azonosítóval nem található esemény.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Hiba történt az esemény törlése közben: ' . $stmt->error]);
        error_log("Database error in delete_event.php: " . $stmt->error);
    }

    $stmt->close();

} else {
    http_response_code(400);
    echo json_encode(['message' => 'Hiányzó kötelező paraméter: event_id.']);
}

?>