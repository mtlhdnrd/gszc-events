<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/lib/PHPMailer/src/Exception.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/lib/PHPMailer/src/PHPMailer.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/lib/PHPMailer/src/SMTP.php";


define('INVITATION_COOLDOWN_DAYS', 30);

function sendFailureNotification($eventWorkshopId, $failureDetails) // Removed $conn as it wasn't used inside
{
    // Check if OPERATOR_EMAIL is defined and not empty
    if (!defined('OPERATOR_EMAIL') || empty(OPERATOR_EMAIL)) {
        error_log("OPERATOR_EMAIL not defined. Cannot send failure notification for event_workshop_id: {$eventWorkshopId}.");
        return false; // Return false indicate failure
    }

    // --- Construct message details ---
    $to = OPERATOR_EMAIL; // Use the defined constant
    $subject = "Figyelmeztetés: Probléma a mentorok toborzásával - Foglalkozás ID: " . $eventWorkshopId;

    $messageBody = "Tisztelt Operátor!\n\n";
    $messageBody .= "A rendszer nem tudott elegendő mentort találni a következő esemény foglalkozásához:\n\n";
    $messageBody .= "Esemény neve: " . ($failureDetails['event_name'] ?? 'N/A') . "\n";
    $messageBody .= "Esemény dátuma: " . ($failureDetails['event_date'] ?? 'N/A') . "\n";
    $messageBody .= "Foglalkozás neve: " . ($failureDetails['workshop_name'] ?? 'N/A') . "\n";
    $messageBody .= "Foglalkozás ID (event_workshop_id): " . $eventWorkshopId . "\n\n";
    $messageBody .= "Részletek:\n";
    if (isset($failureDetails['failed_students'])) {
        $messageBody .= "- Diák mentorok: Szükséges: {$failureDetails['needed_students']}, Elérhető (elfogadott+függő+új): {$failureDetails['potential_students']}\n";
    }
    if (isset($failureDetails['failed_teachers'])) {
        $messageBody .= "- Tanár mentorok: Szükséges: {$failureDetails['needed_teachers']}, Elérhető (elfogadott+függő+új): {$failureDetails['potential_teachers']}\n";
    }
    $messageBody .= "\nKérjük, ellenőrizze a rangsort és a résztvevőket az admin felületen.\n";
    $messageBody .= "\nÜdvözlettel,\nA Foglalkozáskezelő Rendszer";
    // --- End of message construction ---


    // --- PHPMailer Implementation for Gmail ---
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    // Sender Credentials (Replace with your actual details)
    $senderEmail    = 'girmany321@gmail.com';
    $senderName     = 'GSZC'; 
    $appPassword    = 'rnds xett momj lfrd';

    try {
        // Server settings for Gmail
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;   // Enable verbose debug output if needed
        $mail->isSMTP();                           // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';      // Gmail SMTP server
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = $senderEmail;          // Your Gmail address (sending from)
        $mail->Password   = $appPassword;          // Your App Password (NOT your regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL
        $mail->Port       = 465;                   // TCP port for SSL

        // Character set
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom($senderEmail, $senderName); // From address MUST be the same as Username
        $mail->addAddress($to);                    // Add the recipient (OPERATOR_EMAIL)
        $mail->addReplyTo($senderEmail, $senderName); // Optional: Set reply-to address

        // Content
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = $subject;
        $mail->Body    = $messageBody;

        $mail->send();
        error_log("Success: Failure notification sent via PHPMailer for event_workshop_id: {$eventWorkshopId} from {$senderEmail} to " . $to);
        return true; // Indicate success

    } catch (Exception $e) {
        // Log the detailed error from PHPMailer
        error_log("Error: Failed sending email via PHPMailer for event_workshop_id: {$eventWorkshopId}. From: {$senderEmail}, To: {$to}. Mailer Error: {$mail->ErrorInfo}");
        return false; // Indicate failure
    }
    // --- End of PHPMailer Implementation ---
}

function isOnCooldown($userId, $currentEventDate, $cooldownDays, $conn)
{
    // Calculate the start date of the cooldown period
    $cooldownStartDate = date('Y-m-d', strtotime($currentEventDate . ' -' . $cooldownDays . ' days'));

    // SQL query: Check if the user attended any event_workshop
    // within the cooldown period (excluding the current event date itself).
    $sqlCooldown = "SELECT 1
                    FROM attendance_sheets AS att
                    JOIN event_workshop AS ew_att ON att.event_workshop_id = ew_att.event_workshop_id
                    JOIN events AS e_att ON ew_att.event_id = e_att.event_id
                    WHERE att.user_id = ?
                      AND e_att.date >= ?  -- Start of cooldown period
                      AND e_att.date < ?   -- Before the current event
                    LIMIT 1";

    $stmtCooldown = $conn->prepare($sqlCooldown);
    if (!$stmtCooldown) {
        error_log("Cooldown check prepare failed for user {$userId}: " . $conn->error);
        // Fail safe: assume cooldown to prevent potential over-invitation in case of error.
        // Alternatively, could throw an Exception.
        return true;
    }
    $stmtCooldown->bind_param("iss", $userId, $cooldownStartDate, $currentEventDate);
    $stmtCooldown->execute();
    $resultCooldown = $stmtCooldown->get_result();
    $isOnCooldown = $resultCooldown->num_rows > 0;
    $stmtCooldown->close();

    if ($isOnCooldown) {
        error_log("User {$userId} is on cooldown (participated between {$cooldownStartDate} and {$currentEventDate}).");
    }

    return $isOnCooldown;
}

function processWorkshopInvitations($eventWorkshopId, $conn)
{
    $fetchedData = [
        'ew_data' => null,
        'rankings' => [],
        'invitations' => [],
        'needed_students' => 0,
        'needed_teachers' => 0,
        'accepted_students' => 0,
        'pending_students' => 0,
        'accepted_teachers' => 0,
        'pending_teachers' => 0,
        'target_met_by_accepted' => false, // Was target met by *accepted* count initially?
        'newly_invited_students' => 0,
        'newly_invited_teachers' => 0,
        'skipped_cooldown_students' => 0,
        'skipped_cooldown_teachers' => 0,
        'skipped_already_invited_students' => 0,
        'skipped_already_invited_teachers' => 0,
        'final_ew_status' => 'pending', // Will hold the calculated final status -- failed - ready - inviting - pending

        'status_changed' => false,      // Flag if status was updated in DB
        'failure_details' => null       // Holds info for email if failed
    ];

    // Flags to track if loops broke early due to capacity limit
    $student_loop_broke_early = false;
    $teacher_loop_broke_early = false;

    $conn->begin_transaction();

    try {
        // --- Step 1a: Fetch Event Workshop & Event Details (including ew_status) ---
        $sqlEwEvent = "SELECT
                           ew.event_id, ew.workshop_id,
                           ew.number_of_mentors_required, ew.number_of_teachers_required,
                           ew.busyness, ew.ew_status, -- <<< Fetch current status
                           e.date AS event_date, e.name AS event_name, -- <<< Fetch event name
                           w.name AS workshop_name -- <<< Fetch workshop name
                       FROM event_workshop AS ew
                       JOIN events AS e ON ew.event_id = e.event_id
                       JOIN workshops AS w ON ew.workshop_id = w.workshop_id -- <<< Join workshops
                       WHERE ew.event_workshop_id = ?
                       LIMIT 1 FOR UPDATE"; // Lock the ew row as well

        $stmtEwEvent = $conn->prepare($sqlEwEvent);
        // ... (error handling, bind_param, execute, fetch - as before) ...
        if (!$stmtEwEvent)
            throw new Exception("Prepare failed (ew_event): " . $conn->error);
        $stmtEwEvent->bind_param("i", $eventWorkshopId);
        $stmtEwEvent->execute();
        $resultEwEvent = $stmtEwEvent->get_result();
        if ($resultEwEvent->num_rows === 0) { /* ... handle not found, rollback ... */
            return ['error' => "Handle not found"];
        }
        $fetchedData['ew_data'] = $resultEwEvent->fetch_assoc();
        $stmtEwEvent->close();

        // Store initial status
        $initial_ew_status = $fetchedData['ew_data']['ew_status'];
        $fetchedData['final_ew_status'] = $initial_ew_status; // Default final status to initial
        $currentEventDate = $fetchedData['ew_data']['event_date'];

        // If already failed or completed, don't process further
        if ($initial_ew_status === 'failed' || $initial_ew_status === 'completed' || $initial_ew_status === 'ready') {
            error_log("processWorkshopInvitations({$eventWorkshopId}): Skipping processing, status is already '{$initial_ew_status}'.");
            $conn->commit(); // Commit (no changes made)
            return $fetchedData; // Return current state
        }

        // --- Step 1b: Fetch Rankings ---
        // ... (as before) ...
        $sqlRankings = "SELECT r.user_id, r.ranking_number, r.user_type FROM rankings AS r WHERE r.event_workshop_id = ? ORDER BY r.user_type, r.ranking_number ASC";
        $stmtRankings = $conn->prepare($sqlRankings);
        // ... (execute, fetch into $ranked_students, $ranked_teachers - as before) ...
        if (!$stmtRankings)
            throw new Exception("Prepare failed (rankings): " . $conn->error);
        $stmtRankings->bind_param("i", $eventWorkshopId);
        $stmtRankings->execute();
        $resultRankings = $stmtRankings->get_result();
        $ranked_students = [];
        $ranked_teachers = [];
        while ($row = $resultRankings->fetch_assoc()) {
            $fetchedData['rankings'][] = $row;
            if ($row['user_type'] === 'student')
                $ranked_students[] = $row;
            elseif ($row['user_type'] === 'teacher')
                $ranked_teachers[] = $row;
        }
        $stmtRankings->close();

        // --- Step 1c: Fetch Existing Invitations ---
        // ... (using FOR UPDATE - as before) ...
        $sqlInvitations = "SELECT pi.user_id, pi.status, pi.invitation_id FROM participant_invitations AS pi WHERE pi.event_workshop_id = ? FOR UPDATE";
        $stmtInvitations = $conn->prepare($sqlInvitations);
        // ... (execute, fetch into $fetchedData['invitations'] - as before) ...
        if (!$stmtInvitations)
            throw new Exception("Prepare failed (invitations): " . $conn->error);
        $stmtInvitations->bind_param("i", $eventWorkshopId);
        $stmtInvitations->execute();
        $resultInvitations = $stmtInvitations->get_result();
        while ($row = $resultInvitations->fetch_assoc()) {
            $userId = (int) $row['user_id'];
            $fetchedData['invitations'][$userId] = ['status' => $row['status'], 'invitation_id' => (int) $row['invitation_id']];
        }
        $stmtInvitations->close();

        // --- Step 2: Calculate Required Count ---
        // ... (calculating $needed_students, $needed_teachers based on busyness - as before) ...
        $base_students_required = (int) ($fetchedData['ew_data']['number_of_mentors_required'] ?? 0);
        $base_teachers_required = (int) ($fetchedData['ew_data']['number_of_teachers_required'] ?? 0);
        $busyness = $fetchedData['ew_data']['busyness'] ?? 'high';
        $needed_students = (strtolower($busyness) === 'low') ? (int) ceil($base_students_required * 0.5) : $base_students_required;
        $needed_teachers = (strtolower($busyness) === 'low') ? (int) ceil($base_teachers_required * 0.5) : $base_teachers_required;
        if ($needed_students <= 0 && $needed_teachers <= 0) { /* ... handle 0 required, commit, return ... */
            return $fetchedData;
        }
        $fetchedData['needed_students'] = $needed_students;
        $fetchedData['needed_teachers'] = $needed_teachers;

        // --- Step 3: Calculate Current Accepted/Pending Counts ---
        // ... (loop through $fetchedData['invitations'], find user type, count accepted/pending - as before) ...
        foreach ($fetchedData['invitations'] as $userId => $invitationData) {
            $userType = null; /* ... find user type ... */
            foreach ($fetchedData['rankings'] as $rankedUser) {
                if ((int) $rankedUser['user_id'] === $userId) {
                    $userType = $rankedUser['user_type'];
                    break;
                }
            }
            if ($userType === null)
                continue; // Skip if type not found
            if ($invitationData['status'] === 'accepted') {
                if ($userType === 'student')
                    $fetchedData['accepted_students']++;
                elseif ($userType === 'teacher')
                    $fetchedData['accepted_teachers']++;
            } elseif ($invitationData['status'] === 'pending') {
                if ($userType === 'student')
                    $fetchedData['pending_students']++;
                elseif ($userType === 'teacher')
                    $fetchedData['pending_teachers']++;
            }
        }

        // --- Step 4: Check if Target Met by ACCEPTED already ---
        $fetchedData['target_met_by_accepted'] = ($fetchedData['accepted_students'] >= $needed_students && $fetchedData['accepted_teachers'] >= $needed_teachers);
        if ($fetchedData['target_met_by_accepted']) {
            error_log("processWorkshopInvitations({$eventWorkshopId}): Target already met by ACCEPTED participants.");
            // Update status to 'ready' if it's not already
            if ($initial_ew_status !== 'ready') {
                $fetchedData['final_ew_status'] = 'ready';
                // (DB update will happen at the end)
            }
            // No need to send more invites, proceed to status update check at the end.
        } else {
            error_log("processWorkshopInvitations({$eventWorkshopId}): Status before sending - Needed S:{$needed_students}, T:{$needed_teachers} | Accepted S:{$fetchedData['accepted_students']}, Pending S:{$fetchedData['pending_students']} | Accepted T:{$fetchedData['accepted_teachers']}, Pending T:{$fetchedData['pending_teachers']}");

            // --- Step 5: Iterate and Send New Invitations (if target not met by accepted) ---

            // --- Invite Students ---
            if ($fetchedData['accepted_students'] < $needed_students) {
                foreach ($ranked_students as $student) {
                    $userId = (int) $student['user_id'];
                    $rankingNumber = (int) $student['ranking_number'];

                    // a) Check Capacity Limit
                    $potential_total_students = $fetchedData['accepted_students'] + $fetchedData['pending_students'] + $fetchedData['newly_invited_students'];
                    if ($potential_total_students >= $needed_students) {
                        error_log("processWorkshopInvitations({$eventWorkshopId}): Student limit reached ({$potential_total_students} >= {$needed_students}). Stopping student invites.");
                        $student_loop_broke_early = true; // <<< Mark loop break reason
                        break;
                    }

                    // b) Check if Already Invited
                    if (isset($fetchedData['invitations'][$userId])) { /* ... skip, log ... */
                        $fetchedData['skipped_already_invited_students']++;
                        continue;
                    }

                    // c) Check Cooldown
                    if (isOnCooldown($userId, $currentEventDate, INVITATION_COOLDOWN_DAYS, $conn)) { /* ... skip, log ... */
                        $fetchedData['skipped_cooldown_students']++;
                        continue;
                    }

                    // d) Send Invitation
                    // ... (prepare, bind, execute INSERT - as before, handle errors with throw Exception) ...
                    $sqlInsertInvite = "INSERT INTO participant_invitations (event_workshop_id, user_id, ranking_number, status) VALUES (?, ?, ?, 'pending')";
                    $stmtInsert = $conn->prepare($sqlInsertInvite);
                    if (!$stmtInsert)
                        throw new Exception("Prepare failed (insert student invite user {$userId}): " . $conn->error);
                    $stmtInsert->bind_param("iii", $eventWorkshopId, $userId, $rankingNumber);
                    if (!$stmtInsert->execute())
                        throw new Exception("Execute failed (insert student invite user {$userId}): " . $stmtInsert->error);
                    $stmtInsert->close();

                    $fetchedData['newly_invited_students']++;
                    error_log("processWorkshopInvitations({$eventWorkshopId}): Sent PENDING invitation to student {$userId} (rank {$rankingNumber}).");
                } // end foreach student
            } // end if need more students

            // --- Invite Teachers ---
            if ($fetchedData['accepted_teachers'] < $needed_teachers) {
                foreach ($ranked_teachers as $teacher) {
                    $userId = (int) $teacher['user_id'];
                    $rankingNumber = (int) $teacher['ranking_number'];

                    // a) Check Capacity Limit
                    $potential_total_teachers = $fetchedData['accepted_teachers'] + $fetchedData['pending_teachers'] + $fetchedData['newly_invited_teachers'];
                    if ($potential_total_teachers >= $needed_teachers) {
                        error_log("processWorkshopInvitations({$eventWorkshopId}): Teacher limit reached ({$potential_total_teachers} >= {$needed_teachers}). Stopping teacher invites.");
                        $teacher_loop_broke_early = true; // <<< Mark loop break reason
                        break;
                    }
                    // b) Check if Already Invited
                    if (isset($fetchedData['invitations'][$userId])) { /* ... skip, log ... */
                        $fetchedData['skipped_already_invited_teachers']++;
                        continue;
                    }
                    // c) Check Cooldown
                    if (isOnCooldown($userId, $currentEventDate, INVITATION_COOLDOWN_DAYS, $conn)) { /* ... skip, log ... */
                        $fetchedData['skipped_cooldown_teachers']++;
                        continue;
                    }
                    // d) Send Invitation
                    // ... (prepare, bind, execute INSERT - as before, handle errors with throw Exception) ...
                    $sqlInsertInvite = "INSERT INTO participant_invitations (event_workshop_id, user_id, ranking_number, status) VALUES (?, ?, ?, 'pending')";
                    $stmtInsert = $conn->prepare($sqlInsertInvite);
                    if (!$stmtInsert)
                        throw new Exception("Prepare failed (insert teacher invite user {$userId}): " . $conn->error);
                    $stmtInsert->bind_param("iii", $eventWorkshopId, $userId, $rankingNumber);
                    if (!$stmtInsert->execute())
                        throw new Exception("Execute failed (insert teacher invite user {$userId}): " . $stmtInsert->error);
                    $stmtInsert->close();

                    $fetchedData['newly_invited_teachers']++;
                    error_log("processWorkshopInvitations({$eventWorkshopId}): Sent PENDING invitation to teacher {$userId} (rank {$rankingNumber}).");
                } // end foreach teacher
            } // end if need more teachers

        } // end else (target not met by accepted initially)


        // --- Step 6: Determine Final Workshop Status and Check for Failure ---
        $is_failed = false;
        $failure_reason = []; // Collect reasons for email

        // Recalculate accepted count just to be safe (shouldn't change within this func scope unless logic error)
        $final_accepted_students = $fetchedData['accepted_students'];
        $final_accepted_teachers = $fetchedData['accepted_teachers'];

        $is_ready = ($final_accepted_students >= $needed_students && $final_accepted_teachers >= $needed_teachers);

        if ($is_ready) {
            // --- STATE: READY ---
            $fetchedData['final_ew_status'] = 'ready';
            error_log("processWorkshopInvitations({$eventWorkshopId}): Condition met for 'ready'. Accepted S:{$final_accepted_students}/{$needed_students}, T:{$final_accepted_teachers}/{$needed_teachers}.");
            $fetchedData['failure_details'] = null;

        } else {
            // --- STATE: NOT READY - Check for Failed, Inviting, or Pending ---
            error_log("processWorkshopInvitations({$eventWorkshopId}): Workshop is NOT ready based on accepted counts. Needed S:{$needed_students}, T:{$needed_teachers}. Accepted S:{$final_accepted_students}, T:{$final_accepted_teachers}. Checking state...");

            // Check if there are outstanding invitations OR if new ones were just sent
            $has_pending_invites = ($fetchedData['pending_students'] > 0 || $fetchedData['pending_teachers'] > 0);
            $sent_new_invites_this_run = ($fetchedData['newly_invited_students'] > 0 || $fetchedData['newly_invited_teachers'] > 0);

            if ($has_pending_invites || $sent_new_invites_this_run) {
                // --- STATE: INVITING ---
                // If there are pending invites OR we just sent new ones, the process is ongoing.
                $fetchedData['final_ew_status'] = 'inviting';
                $fetchedData['failure_details'] = null;
                error_log("processWorkshopInvitations({$eventWorkshopId}): Status set to 'inviting'. Pending S:{$fetchedData['pending_students']}, T:{$fetchedData['pending_teachers']}. Newly sent S:{$fetchedData['newly_invited_students']}, T:{$fetchedData['newly_invited_teachers']}");

            } else {
                // --- STATE: POTENTIALLY FAILED ---
                // Not ready, no pending invites, and no new invites were sent *in this specific run*.
                // This is the only scenario where it can be considered failed.
                $fetchedData['final_ew_status'] = 'failed';
                $failure_reason = []; // Determine specific reason if needed for email
                if ($needed_students > 0 && $final_accepted_students < $needed_students) $failure_reason['failed_students'] = true;
                if ($needed_teachers > 0 && $final_accepted_teachers < $needed_teachers) $failure_reason['failed_teachers'] = true;

                $fetchedData['failure_details'] = $failure_reason + [
                    'event_name' => $fetchedData['ew_data']['event_name'],
                    'event_date' => $fetchedData['ew_data']['event_date'],
                    'workshop_name' => $fetchedData['ew_data']['workshop_name'],
                    'needed_students' => $needed_students,
                    'needed_teachers' => $needed_teachers,
                    // Potentials are now just the accepted ones as nothing is pending/new
                    'potential_students' => $final_accepted_students,
                    'potential_teachers' => $final_accepted_teachers
                ];
                 error_log("processWorkshopInvitations({$eventWorkshopId}): Final status set to 'failed'. No pending invites and no new invites could be sent.");
            }
        } // End else (not ready)


        // --- Step 7: Update Event Workshop Status in DB if Changed ---
        if ($fetchedData['final_ew_status'] !== $initial_ew_status) {
            error_log("processWorkshopInvitations({$eventWorkshopId}): Updating ew_status from '{$initial_ew_status}' to '{$fetchedData['final_ew_status']}'.");
            $sqlUpdateStatus = "UPDATE event_workshop SET ew_status = ? WHERE event_workshop_id = ?";
            $stmtUpdateStatus = $conn->prepare($sqlUpdateStatus);
            if (!$stmtUpdateStatus)
                throw new Exception("Prepare failed (update ew_status): " . $conn->error);

            $stmtUpdateStatus->bind_param("si", $fetchedData['final_ew_status'], $eventWorkshopId);
            if (!$stmtUpdateStatus->execute())
                throw new Exception("Execute failed (update ew_status): " . $stmtUpdateStatus->error);

            if ($stmtUpdateStatus->affected_rows > 0) {
                $fetchedData['status_changed'] = true;
                error_log("processWorkshopInvitations({$eventWorkshopId}): ew_status updated successfully.");

                // Prepare failure details if status changed TO 'failed'
                if ($fetchedData['final_ew_status'] === 'failed') {
                    $fetchedData['failure_details'] = $failure_reason + [ // Merge reason flags with details
                        'event_name' => $fetchedData['ew_data']['event_name'],
                        'event_date' => $fetchedData['ew_data']['event_date'],
                        'workshop_name' => $fetchedData['ew_data']['workshop_name'],
                        'needed_students' => $needed_students,
                        'needed_teachers' => $needed_teachers,
                        // Calculate potential counts for the email report
                        'potential_students' => $fetchedData['accepted_students'] + $fetchedData['pending_students'] + $fetchedData['newly_invited_students'],
                        'potential_teachers' => $fetchedData['accepted_teachers'] + $fetchedData['pending_teachers'] + $fetchedData['newly_invited_teachers']
                    ];
                }
            } else {
                error_log("processWorkshopInvitations({$eventWorkshopId}): ew_status update affected 0 rows (maybe already set?).");
            }
            $stmtUpdateStatus->close();
        } else {
            error_log("processWorkshopInvitations({$eventWorkshopId}): ew_status ('{$initial_ew_status}') remains unchanged.");
        }

        // --- Transaction Commit ---
        $conn->commit();
        error_log("processWorkshopInvitations({$eventWorkshopId}): Transaction committed.");

        // --- Send Email Notification AND Update Event Status AFTER Commit if Failed ---
        $eventStatusUpdatedToFailed = false; // Track if event status changed
        if ($fetchedData['final_ew_status'] === 'failed') {
            error_log("processWorkshopInvitations({$eventWorkshopId}): Workshop determined as 'failed'. Triggering failure actions.");
   
            // 1. Send Email Notification for the workshop (only if details are available - implies it was processed this run)
            if ($fetchedData['failure_details'] !== null) {
                sendFailureNotification($eventWorkshopId, $fetchedData['failure_details'], $conn);
            } else {
                 error_log("processWorkshopInvitations({$eventWorkshopId}): Workshop is 'failed', but no failure details generated in this run (likely already failed). Skipping email notification.");
            }
   
            // 2. Attempt to set the Parent Event Status to 'failed'
            $eventId = $fetchedData['ew_data']['event_id'] ?? null; // Use null coalescing just in case
            if ($eventId) {
               $eventStatusUpdatedToFailed = setEventStatusToFailed($eventId, $conn);
            } else {
                error_log("Cannot update parent event status: Event ID not found in fetched data for ew_id {$eventWorkshopId}.");
            }
       }

        // Return the full results data structure
        // Optionally add the event status update result if needed by the caller
        $fetchedData['event_status_set_to_failed'] = $eventStatusUpdatedToFailed;
        return $fetchedData;



    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Database Error in processWorkshopInvitations({$eventWorkshopId}): ({$e->getCode()}) {$e->getMessage()} - Transaction rolled back.");
        return ['error' => 'Database error during processing', 'details' => $e->getMessage()];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("General Error in processWorkshopInvitations({$eventWorkshopId}): {$e->getMessage()} - Transaction rolled back.");
        return ['error' => 'General error during processing', 'details' => $e->getMessage()];
    }
}
function checkAndUpdateEventStatus($eventId, $conn)
{

    /**
     * Checks if all workshops associated with a given event have met their
     * required number of accepted participants (students and teachers).
     * If all workshops are ready, it updates the event's status to 'ready'.
     * @return bool True if the event status was successfully updated to 'ready', false otherwise.
     */
    error_log("Checking overall readiness for event ID: {$eventId}");

    // Start transaction for the check-and-update operation
    $conn->begin_transaction();

    try {
        // --- Step 1: Check current event status and lock the row ---
        // Prevents race conditions if multiple processes check simultaneously.
        $sqlGetEventStatus = "SELECT status FROM events WHERE event_id = ? FOR UPDATE";
        $stmtGetStatus = $conn->prepare($sqlGetEventStatus);
        if (!$stmtGetStatus)
            throw new Exception("Prepare failed (get event status): " . $conn->error);

        $stmtGetStatus->bind_param("i", $eventId);
        $stmtGetStatus->execute();
        $resultStatus = $stmtGetStatus->get_result();

        if ($resultStatus->num_rows === 0) {
            $stmtGetStatus->close();
            throw new Exception("Event with ID {$eventId} not found during status check.");
        }
        $eventData = $resultStatus->fetch_assoc();
        $currentEventStatus = $eventData['status'];
        $stmtGetStatus->close();

        // If event is already 'ready' or in another final state, do nothing.
        if ($currentEventStatus === 'ready' || $currentEventStatus === 'completed' || $currentEventStatus === 'cancelled') {
            error_log("Event {$eventId} is already in status '{$currentEventStatus}'. No update needed.");
            $conn->commit(); // Commit transaction (no changes made)
            return false; // Not updated in this run
        }

        // --- Step 2: Get all workshops for the event and their requirements ---
        $sqlGetWorkshops = "SELECT
                                event_workshop_id,
                                number_of_mentors_required,
                                number_of_teachers_required,
                                busyness
                            FROM event_workshop
                            WHERE event_id = ?";
        $stmtGetWorkshops = $conn->prepare($sqlGetWorkshops);
        if (!$stmtGetWorkshops)
            throw new Exception("Prepare failed (get workshops for event): " . $conn->error);

        $stmtGetWorkshops->bind_param("i", $eventId);
        $stmtGetWorkshops->execute();
        $resultWorkshops = $stmtGetWorkshops->get_result();

        $workshops = [];
        while ($row = $resultWorkshops->fetch_assoc()) {
            $workshops[] = $row;
        }
        $stmtGetWorkshops->close();

        // If the event has no workshops, it cannot become 'ready' based on participants.
        if (empty($workshops)) {
            error_log("Event {$eventId} has no associated workshops. Cannot determine participant readiness.");
            $conn->commit(); // Commit transaction (no changes made)
            return false;
        }

        // --- Step 3: Check readiness for EACH workshop ---
        $allWorkshopsReady = true; // Assume readiness initially

        // Prepare statement for counting accepted participants (reused in loop)
        $sqlCountAccepted = "SELECT p.type, COUNT(pi.invitation_id) as accepted_count
                             FROM participant_invitations pi
                             JOIN participants p ON pi.user_id = p.user_id
                             WHERE pi.event_workshop_id = ? AND pi.status = 'accepted'
                             GROUP BY p.type";
        $stmtCountAccepted = $conn->prepare($sqlCountAccepted);
        if (!$stmtCountAccepted)
            throw new Exception("Prepare failed (count accepted): " . $conn->error);


        foreach ($workshops as $workshop) {
            $eventWorkshopId = $workshop['event_workshop_id'];

            // Calculate needed participants for *this* workshop 
            $base_students_required = (int) $workshop['number_of_mentors_required'];
            $base_teachers_required = (int) $workshop['number_of_teachers_required'];
            $busyness = $workshop['busyness'] ?? 'high';

            $needed_students = (strtolower($busyness) === 'low') ? (int) ceil($base_students_required * 0.5) : $base_students_required;
            $needed_teachers = (strtolower($busyness) === 'low') ? (int) ceil($base_teachers_required * 0.5) : $base_teachers_required;

            // If a workshop requires zero participants, consider it 'ready' for this check.
            if ($needed_students <= 0 && $needed_teachers <= 0) {
                error_log("Workshop {$eventWorkshopId} requires 0 participants, considering ready.");
                continue; // Check next workshop
            }

            // Count currently accepted students and teachers for *this* workshop
            $stmtCountAccepted->bind_param("i", $eventWorkshopId);
            $stmtCountAccepted->execute();
            $resultAccepted = $stmtCountAccepted->get_result();

            $accepted_students = 0;
            $accepted_teachers = 0;
            while ($countRow = $resultAccepted->fetch_assoc()) {
                if ($countRow['type'] === 'student') {
                    $accepted_students = (int) $countRow['accepted_count'];
                } elseif ($countRow['type'] === 'teacher') {
                    $accepted_teachers = (int) $countRow['accepted_count'];
                }
            }

            // Check if this workshop meets its requirements
            $workshopIsReady = ($accepted_students >= $needed_students) && ($accepted_teachers >= $needed_teachers);

            if (!$workshopIsReady) {
                error_log("Workshop {$eventWorkshopId} for event {$eventId} is NOT ready. Needed S:{$needed_students}, T:{$needed_teachers}. Accepted S:{$accepted_students}, T:{$accepted_teachers}. Event cannot be set to 'ready'.");
                $allWorkshopsReady = false;
                break; // No need to check further workshops for this event
            } else {
                error_log("Workshop {$eventWorkshopId} for event {$eventId} IS ready. Needed S:{$needed_students}, T:{$needed_teachers}. Accepted S:{$accepted_students}, T:{$accepted_teachers}.");
            }
        }
        $stmtCountAccepted->close(); // Close the prepared statement after the loop


        // --- Step 4: Update event status if all workshops are ready ---
        if ($allWorkshopsReady) {
            error_log("All workshops for event {$eventId} are ready. Updating event status to 'ready'.");
            $sqlUpdateEvent = "UPDATE events SET status = 'ready' WHERE event_id = ? AND status != 'ready'"; // Double check status to be safe
            $stmtUpdateEvent = $conn->prepare($sqlUpdateEvent);
            if (!$stmtUpdateEvent)
                throw new Exception("Prepare failed (update event status): " . $conn->error);

            $stmtUpdateEvent->bind_param("i", $eventId);
            $successUpdate = $stmtUpdateEvent->execute();
            if (!$successUpdate) {
                throw new Exception("Execute failed (update event status): " . $stmtUpdateEvent->error);
            }

            $affectedRows = $stmtUpdateEvent->affected_rows;
            $stmtUpdateEvent->close();

            if ($affectedRows > 0) {
                error_log("Event {$eventId} status successfully updated to 'ready'.");
                $conn->commit(); // Commit the transaction
                return true; // Status was updated
            } else {
                error_log("Event {$eventId} status was not updated (possibly already 'ready' or condition changed).");
                $conn->commit(); // Commit transaction (no status update occurred but check was successful)
                return false; // Not updated in this run
            }
        } else {
            // If not all workshops are ready, just commit the transaction (as only SELECTs and potentially the event lock occurred)
            $conn->commit();
            return false; // Not updated
        }

    } catch (mysqli_sql_exception $e) {
        $conn->rollback(); // Rollback on DB error
        error_log("Database Error in checkAndUpdateEventStatus for event {$eventId}: (" . $e->getCode() . ") " . $e->getMessage());
        return false; // Indicate failure
    } catch (Exception $e) {
        $conn->rollback(); // Rollback on general error
        error_log("General Error in checkAndUpdateEventStatus for event {$eventId}: " . $e->getMessage());
        return false; // Indicate failure
    }
}
function setEventStatusToFailed($eventId, $conn)
{
    error_log("Attempting to set event ID {$eventId} status to 'failed'.");

    $conn->begin_transaction();
    try {
        // Check current status and lock row
        $sqlGetStatus = "SELECT status FROM events WHERE event_id = ? FOR UPDATE";
        $stmtGet = $conn->prepare($sqlGetStatus);
        if (!$stmtGet)
            throw new Exception("Prepare failed (get event status for failure update): " . $conn->error);
        $stmtGet->bind_param("i", $eventId);
        $stmtGet->execute();
        $resultGet = $stmtGet->get_result();
        if ($resultGet->num_rows === 0) {
            throw new Exception("Event {$eventId} not found when trying to set status to failed.");
        }
        $eventData = $resultGet->fetch_assoc();
        $currentStatus = $eventData['status'];
        $stmtGet->close();

        // Only update if not already in a final or failed state
        if ($currentStatus === 'failed' || $currentStatus === 'ready') {
            error_log("Event {$eventId} already in status '{$currentStatus}'. No update to 'failed' performed.");
            $conn->commit(); // Nothing to change
            return false;
        }

        // Update status to 'failed'
        $sqlUpdate = "UPDATE events SET status = 'failed' WHERE event_id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if (!$stmtUpdate)
            throw new Exception("Prepare failed (set event status to failed): " . $conn->error);
        $stmtUpdate->bind_param("i", $eventId);
        if (!$stmtUpdate->execute())
            throw new Exception("Execute failed (set event status to failed): " . $stmtUpdate->error);

        $affectedRows = $stmtUpdate->affected_rows;
        $stmtUpdate->close();

        $conn->commit();

        if ($affectedRows > 0) {
            error_log("Event {$eventId} status successfully updated to 'failed'.");
            return true;
        } else {
            error_log("Event {$eventId} status update to 'failed' affected 0 rows (already failed?).");
            return false;
        }

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Database Error in setEventStatusToFailed for event {$eventId}: ({$e->getCode()}) {$e->getMessage()}");
        return false;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("General Error in setEventStatusToFailed for event {$eventId}: {$e->getMessage()}");
        return false;
    }
}