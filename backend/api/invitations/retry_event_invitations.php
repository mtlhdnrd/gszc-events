<?php
// retry_event_invitations.php

require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/notifications_handler.php";

header('Content-Type: application/json');

// --- 1. Input Validation and Authorization ---
$validationResult = validate_request("POST", ["eventId"]);
if ($validationResult !== true) { /* ... handle bad request ... */ http_response_code(400); echo json_encode(["error" => "Missing eventId."]); exit; }

$eventId = filter_input(INPUT_POST, 'eventId', FILTER_VALIDATE_INT);
if ($eventId === false || $eventId <= 0) { /* ... handle bad request ... */ http_response_code(400); echo json_encode(["error" => "Invalid eventId."]); exit; }

//TODO: Add admin authorization here


$conn->begin_transaction();
try {
    // --- 2. Verify Event Exists and is 'failed' ---
    $sqlCheckEvent = "SELECT status FROM events WHERE event_id = ? FOR UPDATE"; // Lock event row
    $stmtCheck = $conn->prepare($sqlCheckEvent);
    if (!$stmtCheck) throw new Exception("Prepare failed (check event for retry): " . $conn->error);
    $stmtCheck->bind_param("i", $eventId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    if ($resultCheck->num_rows === 0) {
         http_response_code(404); echo json_encode(["error" => "Event not found."]); $stmtCheck->close(); $conn->rollback(); exit;
    }
    $eventData = $resultCheck->fetch_assoc();
    $currentEventStatus = $eventData['status'];
    $stmtCheck->close();

    if ($currentEventStatus !== 'failed') {
        http_response_code(400); 
        echo json_encode(["error" => "Event status is '{$currentEventStatus}', not 'failed'. Retry not applicable."]);
        $conn->rollback();
        exit;
    }

    // --- 3. Find all Workshops for the Event ---
    $sqlGetWorkshops = "SELECT event_workshop_id FROM event_workshop WHERE event_id = ?";
    $stmtGetW = $conn->prepare($sqlGetWorkshops);
    if (!$stmtGetW) throw new Exception("Prepare failed (get workshops for retry): " . $conn->error);
    $stmtGetW->bind_param("i", $eventId);
    $stmtGetW->execute();
    $resultWorkshops = $stmtGetW->get_result();
    $workshopIds = [];
    while($row = $resultWorkshops->fetch_assoc()) {
        $workshopIds[] = $row['event_workshop_id'];
    }
    $stmtGetW->close();

    if (empty($workshopIds)) {
        error_log("Event {$eventId} has no workshops. Cannot retry invitations.");
         // Still update event status back? Maybe, debatable. Let's update it.
    }

    $totalResetInvitations = 0;
    $totalResetWorkshops = 0;
    $notificationsAttempted = 0;
    $notificationsSent = 0;

        // --- 4. Reset 'rejected' Invitations (Individually) and 'failed' Workshop Statuses ---
        if (!empty($workshopIds)) {
            // Prepare statement for resetting workshop status (still done per workshop)
            $sqlUpdateWorkshops = "UPDATE event_workshop SET ew_status = 'inviting'
                                   WHERE event_workshop_id = ? AND ew_status = 'failed'";
            $stmtUpdateW = $conn->prepare($sqlUpdateWorkshops);
             if (!$stmtUpdateW) throw new Exception("Prepare failed (reset failed workshops): " . $conn->error);
    
            // Prepare statements for finding and updating individual invitations
            $sqlFindRejected = "SELECT invitation_id, user_id FROM participant_invitations
                                WHERE event_workshop_id = ? AND status = 'rejected' FOR UPDATE"; // Lock rows
            $stmtFindRej = $conn->prepare($sqlFindRejected);
            if (!$stmtFindRej) throw new Exception("Prepare failed (find rejected invites): " . $conn->error);
    
            $sqlUpdateSingleInvite = "UPDATE participant_invitations SET status = 'pending' WHERE invitation_id = ?";
            $stmtUpdateInv = $conn->prepare($sqlUpdateSingleInvite);
            if (!$stmtUpdateInv) throw new Exception("Prepare failed (reset single invite): " . $conn->error);
    
    
            foreach($workshopIds as $ewId) {
                // Find rejected invitations for this workshop
                $stmtFindRej->bind_param("i", $ewId);
                if (!$stmtFindRej->execute()) throw new Exception("Execute failed (find rejected ew_id {$ewId}): " . $stmtFindRej->error);
                $resultRejected = $stmtFindRej->get_result();
    
                $rejectedInvitations = [];
                while($rejRow = $resultRejected->fetch_assoc()) {
                    $rejectedInvitations[] = $rejRow; // Store invitation_id and user_id
                }
    
                if (!empty($rejectedInvitations)) {
                    error_log("Found " . count($rejectedInvitations) . " rejected invitations for event_workshop_id: {$ewId}. Attempting reset and notification.");
    
                    // Loop through found rejected invitations
                    foreach($rejectedInvitations as $invitation) {
                        $invIdToReset = $invitation['invitation_id'];
                        $userIdToNotify = $invitation['user_id'];
    
                        // Reset this specific invitation to pending
                        $stmtUpdateInv->bind_param("i", $invIdToReset);
                        if (!$stmtUpdateInv->execute()) {
                             error_log("Execute failed (resetting invite_id {$invIdToReset}): " . $stmtUpdateInv->error);
                             // Decide whether to throw Exception or just log and continue
                             // Let's log and continue to try others, but transaction will likely rollback later if needed
                             continue; // Skip notification for this one
                        }
    
                        if ($stmtUpdateInv->affected_rows > 0) {
                            $totalResetInvitations++;
                            error_log("Reset invitation_id {$invIdToReset} to pending for user {$userIdToNotify}. Triggering push notification.");
    
                            // *** Call the push notification function ***
                            $notificationsAttempted++;
                            if (sendRetryPushNotification($userIdToNotify, $eventId, $ewId, $conn)) {
                                $notificationsSent++;
                            } else {
                                 error_log("Failed to send push notification for user {$userIdToNotify}, invitation {$invIdToReset}. (Placeholder returned false or actual send failed)");
                                 // Log failure but don't necessarily stop the whole process
                            }
                        } else {
                            // Should not happen if SELECT found it as rejected, but log just in case
                             error_log("Resetting invite_id {$invIdToReset} affected 0 rows (status might have changed?).");
                        }
                    } // end foreach rejectedInvitation
                } // end if !empty rejectedInvitations
    
                // Reset failed workshop status to inviting (after handling its invitations)
                $stmtUpdateW->bind_param("i", $ewId);
                if (!$stmtUpdateW->execute()) throw new Exception("Execute failed (reset workshop ew_id {$ewId}): " . $stmtUpdateW->error);
                if ($stmtUpdateW->affected_rows > 0) {
                    $totalResetWorkshops++;
                    error_log("Reset event_workshop_id {$ewId} status from failed to inviting.");
                }
    
            } // end foreach workshopId
    
            // Close prepared statements after the loop
            $stmtFindRej->close();
            $stmtUpdateInv->close();
            $stmtUpdateW->close();
        } // end if !empty workshopIds


    // --- 5. Reset Event Status back to 'pending' ---
    $sqlUpdateEvent = "UPDATE events SET status = 'pending' WHERE event_id = ? AND status = 'failed'";
    $stmtUpdateE = $conn->prepare($sqlUpdateEvent);
    if (!$stmtUpdateE) throw new Exception("Prepare failed (reset event status): " . $conn->error);
    $stmtUpdateE->bind_param("i", $eventId);
    if (!$stmtUpdateE->execute()) throw new Exception("Execute failed (reset event status): " . $stmtUpdateE->error);
    $resetECount = $stmtUpdateE->affected_rows;
    $stmtUpdateE->close();

    if ($resetECount > 0) error_log("Reset event {$eventId} status from failed to pending.");

    // --- 6. Commit Transaction ---
    $conn->commit();

    // --- 7. Send Response ---
    http_response_code(200);
    echo json_encode([
        "message" => "Retry process initiated for event {$eventId}.",
        "reset_invitations_count" => $totalResetInvitations,
        "reset_workshops_count" => $totalResetWorkshops,
        "event_status_reset" => ($resetECount > 0),
        "notifications_attempted" => $notificationsAttempted,
        "notifications_sent_successfully" => $notificationsSent
    ]);


} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Database Error in retry_event_invitations.php for event {$eventId}: ({$e->getCode()}) {$e->getMessage()}");
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error (DB) during retry process."]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("General Error in retry_event_invitations.php for event {$eventId}: {$e->getMessage()}");
    http_response_code(500);
    echo json_encode(["error" => "An unexpected error occurred during retry process."]);
}

?>