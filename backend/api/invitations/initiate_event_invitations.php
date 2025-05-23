<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/invitations_processor.php"; 

header('Content-Type: application/json');

$validationResult = validate_request("POST", array("eventId"));
if ($validationResult !== true) {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid data format. Expected an array of two objects."));
    exit;
}

$eventId = filter_var($_POST['eventId'], FILTER_VALIDATE_INT);

if ($eventId === false || $eventId <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid or missing eventId."]);
    exit;
}

try {
    // 1. Ellenőrizzük, hogy az event létezik-e
    $sqlCheckEvent = "SELECT event_id FROM events WHERE event_id = ?";
    $stmtCheckEvent = $conn->prepare($sqlCheckEvent);
    if (!$stmtCheckEvent) {
         throw new Exception("Prepare failed (check event): (" . $conn->errno . ") " . $conn->error);
    }
    $stmtCheckEvent->bind_param("i", $eventId);
    $stmtCheckEvent->execute();
    $resultCheckEvent = $stmtCheckEvent->get_result();
    
    if ($resultCheckEvent->num_rows === 0) {
        http_response_code(404); 
        echo json_encode(["error" => "Event with ID {$eventId} not found."]);
        $stmtCheckEvent->close();
        exit;
    }
    $stmtCheckEvent->close();

    // 2. Lekérdezzük az eseményhez tartozó event_workshop_id-kat
    $sqlWorkshops = "SELECT event_workshop_id FROM event_workshop WHERE event_id = ?";
    $stmtWorkshops = $conn->prepare($sqlWorkshops);
     if (!$stmtWorkshops) {
         throw new Exception("Prepare failed (get workshops): (" . $conn->errno . ") " . $conn->error);
    }   
    $stmtWorkshops->bind_param("i", $eventId);
    $stmtWorkshops->execute();
    $resultWorkshops = $stmtWorkshops->get_result();

    $eventWorkshopIds = [];
    // Fetch results and populate the array
    while ($row = $resultWorkshops->fetch_assoc()) {
        $eventWorkshopIds[] = (int)$row['event_workshop_id'];
    }
    $stmtWorkshops->close();

    if (empty($eventWorkshopIds)) {
        http_response_code(200);
        echo json_encode(["message" => "No workshops found for event ID {$eventId}. No invitations initiated."]);
        exit;
    }

    $processedResults = [];
    $errorOccurred = false;
    // 3. Ciklus az event_workshop_id-kon
    foreach ($eventWorkshopIds as $eventWorkshopId) {
        $result = processWorkshopInvitations($eventWorkshopId, $conn);
        $processedResults[$eventWorkshopId] = $result; // Eredmény tárolása

        // Ellenőrizzük, hogy a processWorkshopInvitations jelzett-e hibát
        if (isset($result['error'])) {
            error_log("Error processing workshop {$eventWorkshopId}: " . $result['details']);
            $errorOccurred = true;
        }
    }

    // 4. Sikeres (vagy részlegesen sikeres) válasz küldése
    if ($errorOccurred) {
        // Ha volt hiba, lehet, hogy más HTTP státuszkódot adunk, pl. 207 Multi-Status
        http_response_code(207);
         echo json_encode([
             "message" => "Invitation process completed with errors for event ID {$eventId}. Check logs for details.",
             "event_id" => $eventId,
             "results_per_workshop" => $processedResults // Tartalmazza a sikeres és hibás eredményeket is
         ]);
    } else {
         http_response_code(200);
         echo json_encode([
             "message" => "Invitation process finished for event ID {$eventId}.",
             "event_id" => $eventId,
             "processed_workshop_count" => count($eventWorkshopIds),
             "results_per_workshop" => $processedResults // Részletes eredmények workshoponként
         ]);
    }

}catch (mysqli_sql_exception $e){
    error_log("Database Error in initiate-event-invitations.php: (" . $e->getCode() . ") " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error (DB)"]);
} catch (Exception $e) { 
    error_log("General Error in initiate-event-invitations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "An unexpected error occurred."]);
}