<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/invitation_functions.php";

if (validate_request("POST", array('invitationId', 'status'))) {
    $invitationId = $_POST['invitationId'];
    $status = $_POST['status'];


    if (updateInvitationStatus((int) $invitationId, $status, $conn)) {
        http_response_code(200);
        echo json_encode(['message' => 'Invitation status updated.']);
    } else {
        http_response_code(400); // Vagy 404, ha nincs ilyen invitationId
        echo json_encode(['error' => 'Failed to update invitation status. Invalid invitationId or status.']);
    }

} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request method or missing parameters."]);
}

?>