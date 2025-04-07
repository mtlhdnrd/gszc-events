<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/invitations_processor.php";

header('Content-Type: application/json');

// --- 1. Input Validation and Authentication ---

// ---> Itt kellene a token validálás és $userIdFromToken beállítása
// $userIdFromToken = verifyTokenAndGetUserId();
// if (!$userIdFromToken) { http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit; }
// A $recipientUserId ellenőrzéséhez később szükséged lesz rá!

$validationResult = validate_request("POST", array("invitationId", "newStatus")); // Basic check
if ($validationResult !== true) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required fields: invitationId, newStatus."]);
    exit;
}

$invitationId = filter_input(INPUT_POST, 'invitationId', FILTER_VALIDATE_INT);
$newStatus = filter_input(INPUT_POST, 'newStatus', FILTER_SANITIZE_SPECIAL_CHARS); // Sanitize

// Validate input values
if ($invitationId === false || $invitationId <= 0 || !in_array($newStatus, ['accepted', 'rejected'])) {
     http_response_code(400);
     echo json_encode(["error" => "Invalid input."]);
     exit;
}

$conn->begin_transaction();

try {
    // --- 2. Authorization & Fetching IDs ---
    $sqlGetInvitation = "SELECT pi.user_id, pi.event_workshop_id, pi.status, ew.event_id
                         FROM participant_invitations AS pi
                         JOIN event_workshop AS ew ON pi.event_workshop_id = ew.event_workshop_id
                         WHERE pi.invitation_id = ? FOR UPDATE";
    $stmtGet = $conn->prepare($sqlGetInvitation);
    if (!$stmtGet) throw new Exception("Prepare failed (get invitation): " . $conn->error);

    $stmtGet->bind_param("i", $invitationId);
    $stmtGet->execute();
    $resultGet = $stmtGet->get_result();

    if ($resultGet->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(["error" => "Invitation not found."]);
        $stmtGet->close();
        $conn->rollback();
        exit;
    }

    $invitationData = $resultGet->fetch_assoc();
    $stmtGet->close();

    $recipientUserId = (int)$invitationData['user_id'];
    $eventWorkshopId = (int)$invitationData['event_workshop_id'];
    $eventId = (int)$invitationData['event_id'];
    $currentStatus = $invitationData['status'];

    // --- !!! IMPORTANT: Authentication / Authorization Check !!! ---
    // Hasonlítsd össze a tokenből kapott $userIdFromToken-t $recipientUserId-val!
    // if ($userIdFromToken !== $recipientUserId) {
    //     http_response_code(403); // Forbidden
    //     echo json_encode(["error" => "You are not authorized to update this invitation."]);
    //     $conn->rollback();
    //     exit;
    // }
    // --- End Check ---

    // --- 3. Update Invitation Status ---
    $sqlUpdate = "UPDATE participant_invitations SET status = ? WHERE invitation_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) throw new Exception("Prepare failed (update status): " . $conn->error);

    $stmtUpdate->bind_param("si", $newStatus, $invitationId);
    $successUpdate = $stmtUpdate->execute(); // Execute returns true/false

    if (!$successUpdate) {
        // Execute failed, throw exception (rollback will happen in catch block)
        throw new Exception("Execute failed (update status): " . $stmtUpdate->error);
    }
    // Log affected rows, but proceed even if 0 (status might have been the same)
    error_log("Update status for invitation {$invitationId} affected {$stmtUpdate->affected_rows} rows.");
    $stmtUpdate->close();


    // --- 4. Fetch Updated Invitation Details (IF update execute succeeded) ---
    $updatedInvitationData = null;
    if ($successUpdate) { // Check if the UPDATE execute() returned true
         $queryFetchUpdated = "SELECT
                                si.invitation_id, si.event_workshop_id, si.user_id, si.status,
                                e.name AS event_name, w.name AS workshop_name, p.name AS participant_name,
                                ew.event_id, ew.workshop_id, si.ranking_number,
                                ew.max_workable_hours, ew.number_of_mentors_required,
                                e.date AS event_date,
                                e.location AS event_location
                              FROM participant_invitations si
                              JOIN event_workshop ew ON si.event_workshop_id = ew.event_workshop_id
                              JOIN events e ON ew.event_id = e.event_id
                              JOIN workshops w ON ew.workshop_id = w.workshop_id
                              JOIN participants p ON si.user_id = p.user_id
                              WHERE si.invitation_id = ?"; // Fetch by the ID we just updated

        error_log("Attempting to fetch details for updated invitation ID: {$invitationId}");
        $stmtFetch = $conn->prepare($queryFetchUpdated);
        if ($stmtFetch) {
            $stmtFetch->bind_param("i", $invitationId);
            if ($stmtFetch->execute()) {
                $resultFetch = $stmtFetch->get_result();
                $updatedInvitationData = $resultFetch->fetch_assoc(); // Fetch the single updated row
                if ($updatedInvitationData === null) {
                    error_log("CRITICAL: Fetch for updated invitation {$invitationId} returned NULL. Row might be missing or query failed silently?");
                    // Optional extra check
                    $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM participant_invitations WHERE invitation_id = ?");
                    if ($checkStmt) { $checkStmt->bind_param("i", $invitationId); $checkStmt->execute(); $checkResult = $checkStmt->get_result()->fetch_assoc(); error_log("Existence check for invitation {$invitationId}: Count = " . ($checkResult['cnt'] ?? 'Error')); $checkStmt->close(); }
                } else {
                     error_log("Successfully fetched details for updated invitation ID: {$invitationId}");
                }
            } else {
                 error_log("Execute failed fetching updated invitation {$invitationId}: " . $stmtFetch->error);
            }
            $stmtFetch->close();
        } else {
            error_log("Prepare failed fetching updated invitation {$invitationId}: " . $conn->error);
        }
    } else {
         error_log("Skipping fetch because UPDATE execute() failed for invitation ID: {$invitationId}");
    }


    // --- 5. Commit Transaction ---
    // Commit the status update AND the lock from the initial fetch
    $conn->commit();


    // --- 6. Trigger Processors (Run AFTER commit) ---
    error_log("Invitation {$invitationId} status updated to '{$newStatus}'. Triggering processor for event_workshop_id: {$eventWorkshopId}");
    $processingResult = processWorkshopInvitations($eventWorkshopId, $conn); // Pass $conn

    if (isset($processingResult['error'])) {
         error_log("Error during triggered processing for event_workshop_id {$eventWorkshopId} after status update: " . $processingResult['details']);
    } else {
         error_log("Triggered processing completed for event_workshop_id {$eventWorkshopId}. Newly invited S:" . ($processingResult['newly_invited_students'] ?? 'N/A') . ", T:" . ($processingResult['newly_invited_teachers'] ?? 'N/A'));
    }


    // --- 7. Check and Update Overall Event Status (Run AFTER commit) ---
    if ($newStatus === 'accepted') {
        // $eventId was fetched earlier
        $eventStatusUpdated = checkAndUpdateEventStatus($eventId, $conn); // Pass $conn
        if ($eventStatusUpdated) {
            error_log("Overall event status for event ID {$eventId} was updated to 'ready' after invitation acceptance.");
            // TODO: Send notification to admin?
        } else {
             error_log("Overall event status check completed for event ID {$eventId}, no status change to 'ready' occurred.");
        }
    }


    // --- 8. Send Final Response to Mobile App ---
    if ($updatedInvitationData !== null) {
        // Send back the full details of the updated invitation
        http_response_code(200);
        echo json_encode($updatedInvitationData);
    } else {
         // Update execute succeeded, but fetching details failed. This is a server error.
         http_response_code(500); // Internal Server Error
         error_log("ERROR RESPONSE: Sending 500 because failed to retrieve updated data for invitation {$invitationId} after successful update call.");
         echo json_encode(["error" => "Server error: Could not retrieve updated invitation details after status change. Please try refreshing."]);
    }


} catch (mysqli_sql_exception $e) {
    // Attempt to rollback if transaction is still active
    // Note: $conn->rollback() might fail if connection is lost, handle gracefully if needed
    try { $conn->rollback(); } catch (Exception $rollbackEx) { error_log("Rollback failed: " . $rollbackEx->getMessage()); }
    error_log("Database Error in update_invitation_status.php: (" . $e->getCode() . ") " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error (DB) while updating status."]);
} catch (Exception $e) {
    try { $conn->rollback(); } catch (Exception $rollbackEx) { error_log("Rollback failed: " . $rollbackEx->getMessage()); }
    error_log("General Error in update_invitation_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "An unexpected error occurred while updating status."]);
}

?>