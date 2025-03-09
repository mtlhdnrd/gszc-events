<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/invitation_functions.php";

if (validate_request("POST", array('event_workshop_id'))) {
  $eventWorkshopId = $_POST['event_workshop_id'];

    try{
        sendInvitations((int)$eventWorkshopId, $conn);
        http_response_code(200);
        echo json_encode(['message' => 'Invitations sent.']);
    } catch (Exception $e){
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
      http_response_code(400);
    echo json_encode(["error" => "Invalid request method or missing parameters."]);
}

?>