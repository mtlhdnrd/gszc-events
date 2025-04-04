<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/invitations_processor.php";

header('Content-Type: application/json');

// --- 1. Input Validation and Authentication ---

$validationResult = validate_request("POST", ["invitationId", "newStatus"]); // Basic check
if ($validationResult !== true) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required fields: invitationId, newStatus."]);
    exit;
}

$invitationId = filter_input(INPUT_POST, 'invitationId', FILTER_VALIDATE_INT);
$newStatus = filter_input(INPUT_POST, 'newStatus', FILTER_SANITIZE_SPECIAL_CHARS); // Sanitize

// Validate input values
if ($invitationId === false || $invitationId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid invitationId."]);
    exit;
}

if ($newStatus !== 'accepted' && $newStatus !== 'rejected') {
    http_response_code(400);
    echo json_encode(["error" => "Invalid newStatus. Must be 'accepted' or 'rejected'."]);
    exit;
}
$conn->begin_transaction();

try {
    // --- 2. Authorization & Fetching Event Workshop ID ---

    $sqlGetInvitation = "SELECT user_id, event_workshop_id, status FROM participant_invitations WHERE invitation_id = ? FOR UPDATE";
    $stmtGet = $conn->prepare($sqlGetInvitation);
    if (!$stmtGet) throw new Exception("Prepare failed (get invitation): " . $conn->error);

    $stmtGet->bind_param("i", $invitationId);
    $stmtGet->execute();
    $resultGet = $stmtGet->get_result();

    if ($resultGet->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(["error" => "Invitation not found."]);
        $stmtGet->close();
        $conn->rollback(); // Nothing to rollback, but good practice
        exit;
    }

    $invitationData = $resultGet->fetch_assoc();
    $stmtGet->close();

    $recipientUserId = (int)$invitationData['user_id'];
    $eventWorkshopId = (int)$invitationData['event_workshop_id'];
    $currentStatus = $invitationData['status'];

    // --- !!! IMPORTANT: Add Authentication Check Here !!! ---
    // Make sure $loggedInUserId matches $recipientUserId
    // if ($loggedInUserId !== $recipientUserId) {
    //     http_response_code(403); // Forbidden
    //     echo json_encode(["error" => "You are not authorized to update this invitation."]);
    //     $conn->rollback();
    //     exit;
    // }

    // --- 3. Update Invitation Status ---
    $sqlUpdate = "UPDATE participant_invitations SET status = ? WHERE invitation_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) throw new Exception("Prepare failed (update status): " . $conn->error);

    $stmtUpdate->bind_param("si", $newStatus, $invitationId);
    $successUpdate = $stmtUpdate->execute();

    if (!$successUpdate) {
        throw new Exception("Execute failed (update status): " . $stmtUpdate->error);
    }

    if ($stmtUpdate->affected_rows === 0) {
        // This might happen if the ID was valid but somehow didn't update (e.g., already had the target status)
        // Or potentially due to the FOR UPDATE lock if another process was involved (though less likely here)
        error_log("Update status for invitation {$invitationId} affected 0 rows. Current status might already be '{$newStatus}'.");
    }
    $stmtUpdate->close();


    // --- 4. Trigger Invitation Processor ---
    // Call the processing function for the specific event_workshop this invitation belongs to.
    error_log("Invitation {$invitationId} status updated to '{$newStatus}'. Triggering processor for event_workshop_id: {$eventWorkshopId}");

    // Commit the status update transaction *before* calling the processor
    $conn->commit();

    // Now, call the processor. It will run with the *latest* data
    $processingResult = processWorkshopInvitations($eventWorkshopId, $conn);

    // Log the outcome of the processing trigger
    if (isset($processingResult['error'])) {
         error_log("Error during triggered processing for event_workshop_id {$eventWorkshopId} after status update: " . $processingResult['details']);
         // The API call should still likely return success for the status update itself.
    } else {
         error_log("Triggered processing completed for event_workshop_id {$eventWorkshopId}. Newly invited S:{$processingResult['newly_invited_students']}, T:{$processingResult['newly_invited_teachers']}");
    }


    // --- 5. Send Response to Mobile App ---
    http_response_code(200); // OK
    echo json_encode([
        "message" => "Invitation status updated successfully to '{$newStatus}'.",
        "invitationId" => $invitationId,
        "newStatus" => $newStatus
    ]);


} catch (mysqli_sql_exception $e) {
    $conn->rollback(); // Rollback on any DB error during status update phase
    error_log("Database Error in update_invitation_status.php: (" . $e->getCode() . ") " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error (DB) while updating status."]);
} catch (Exception $e) {
    $conn->rollback(); // Rollback on any general error during status update phase
    error_log("General Error in update_invitation_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "An unexpected error occurred while updating status."]);
}