<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/invitations_processor.php";

header('Content-Type: application/json');

// --- 1. Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Invalid request method. Only POST is allowed."]);
    exit;
}

// Expecting JSON input from the mobile app
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid JSON input."]);
    exit;
}

$invitationId = filter_var($input['invitationId'] ?? null, FILTER_VALIDATE_INT);
$newStatus = $input['status'] ?? null;

// Validate Invitation ID
if ($invitationId === false || $invitationId <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid or missing 'invitationId'."]);
    exit;
}

// Validate Status
if ($newStatus !== 'accepted' && $newStatus !== 'rejected') {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid or missing 'status'. Must be 'accepted' or 'rejected'."]);
    exit;
}

// --- 2. Process the Response ---

$conn->begin_transaction();

try {
    // --- 2a. Find the Invitation and Lock It ---
    // Fetch event_workshop_id and current status, lock the row with FOR UPDATE
    $sqlFindInvite = "SELECT event_workshop_id, user_id, status
                      FROM participant_invitations
                      WHERE invitation_id = ?
                      FOR UPDATE"; // Lock the row for the duration of the transaction
    $stmtFind = $conn->prepare($sqlFindInvite);
    if (!$stmtFind) {
        throw new Exception("Prepare failed (find invite): " . $conn->error);
    }
    $stmtFind->bind_param("i", $invitationId);
    $stmtFind->execute();
    $resultFind = $stmtFind->get_result();

    if ($resultFind->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(["error" => "Invitation with ID {$invitationId} not found."]);
        $stmtFind->close();
        $conn->rollback(); // No changes made, but good practice to rollback
        exit;
    }

    $invitationData = $resultFind->fetch_assoc();
    $eventWorkshopId = (int)$invitationData['event_workshop_id'];
    $currentStatus = $invitationData['status'];
    $userId = (int)$invitationData['user_id']; // Good to have for logging
    $stmtFind->close();

    // --- 2b. Check if the Invitation is Still Pending ---
    if ($currentStatus !== 'pending') {
        http_response_code(409); // Conflict (or 400 Bad Request)
        echo json_encode([
            "error" => "Invitation (ID: {$invitationId}) has already been responded to.",
            "current_status" => $currentStatus
        ]);
        $conn->rollback();
        exit;
    }

    // --- 2c. Update the Invitation Status ---
    $sqlUpdate = "UPDATE participant_invitations SET status = ? WHERE invitation_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        throw new Exception("Prepare failed (update status): " . $conn->error);
    }
    $stmtUpdate->bind_param("si", $newStatus, $invitationId);
    $successUpdate = $stmtUpdate->execute();

    if (!$successUpdate) {
        // Should not happen if SELECT FOR UPDATE worked, but check anyway
        throw new Exception("Execute failed (update status): " . $stmtUpdate->error);
    }
    if ($stmtUpdate->affected_rows === 0) {
         // Also should not happen if SELECT found the row
         throw new Exception("Failed to update status for invitation {$invitationId}. No rows affected.");
    }
    $stmtUpdate->close();

    error_log("Invitation {$invitationId} for user {$userId} on workshop {$eventWorkshopId} updated to '{$newStatus}'.");

    // --- 2d. Re-process Invitations for the Affected Workshop ---
    // Call the modified processor function (which now expects to be inside a transaction)
    $processingResult = processWorkshopInvitations($eventWorkshopId, $conn);

    // Check if the processor itself encountered an error (it should throw an exception)
    // The `processWorkshopInvitations` function (modified version) will throw on error,
    // so execution will jump to the catch block if it fails. We don't need to check its return value for errors here.

    // --- 3. Commit Transaction ---
    // If both the update and the re-processing were successful without exceptions
    $conn->commit();

    http_response_code(200); // OK
    echo json_encode([
        "message" => "Invitation status updated successfully to '{$newStatus}'.",
        "invitation_id" => $invitationId,
        "event_workshop_id_processed" => $eventWorkshopId,
        "reprocessing_details" => $processingResult // Include details from the processor if needed
    ]);

} catch (mysqli_sql_exception $e) {
    // Rollback on database error
    $conn->rollback();
    error_log("Database Error in handle_invitation_response.php: (" . $e->getCode() . ") " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Internal Server Error (DB) while processing invitation response."]);

} catch (Exception $e) {
    // Rollback on general error
    $conn->rollback();
    error_log("General Error in handle_invitation_response.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "An unexpected error occurred while processing invitation response."]);
}
