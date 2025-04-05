<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";

define('INVITATION_COOLDOWN_DAYS', 30);

function isOnCooldown($userId, $currentEventDate, $cooldownDays, $conn) {
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
     // Structure to hold fetched data and processing results
     $fetchedData = [
        'ew_data' => null,                    // Details of the event_workshop and event
        'rankings' => [],                     // Original ranking list for this workshop (user_id, ranking_number, user_type)
        'invitations' => [],                  // Existing invitations map (user_id => ['status', 'invitation_id'])
        'needed_students' => 0,               // Calculated number of students needed
        'needed_teachers' => 0,               // Calculated number of teachers needed
        'accepted_students' => 0,             // Count of accepted student invitations
        'pending_students' => 0,              // Count of pending student invitations
        'accepted_teachers' => 0,             // Count of accepted teacher invitations
        'pending_teachers' => 0,              // Count of pending teacher invitations
        'target_met' => false,                // Flag indicating if the required number of accepted mentors is met
        'newly_invited_students' => 0,        // Count of new student invitations sent in this run
        'newly_invited_teachers' => 0,        // Count of new teacher invitations sent in this run
        'skipped_cooldown_students' => 0,     // Count of students skipped due to cooldown
        'skipped_cooldown_teachers' => 0,     // Count of teachers skipped due to cooldown
        'skipped_already_invited_students' => 0, // Count of students skipped because they already have an invitation
        'skipped_already_invited_teachers' => 0  // Count of teachers skipped because they already have an invitation
    ];

    // --- Start Database Transaction ---
    // Ensures that sending invitations for this workshop is an atomic operation.
    $conn->begin_transaction();

    try {
        // --- Step 1a: Fetch Event Workshop & Event Details ---
        $sqlEwEvent = "SELECT
                           ew.event_id, ew.workshop_id,
                           ew.number_of_mentors_required, ew.number_of_teachers_required,
                           ew.busyness, e.date AS event_date
                       FROM event_workshop AS ew
                       JOIN events AS e ON ew.event_id = e.event_id
                       WHERE ew.event_workshop_id = ?
                       LIMIT 1";

        $stmtEwEvent = $conn->prepare($sqlEwEvent);
        if (!$stmtEwEvent) throw new Exception("Prepare failed (ew_event): " . $conn->error);

        $stmtEwEvent->bind_param("i", $eventWorkshopId);
        $stmtEwEvent->execute();
        $resultEwEvent = $stmtEwEvent->get_result();

        if ($resultEwEvent->num_rows === 0) {
            error_log("processWorkshopInvitations: Event Workshop ID {$eventWorkshopId} not found or invalid.");
            $stmtEwEvent->close();
            $conn->rollback(); // Rollback the transaction
            return ['error' => "Event Workshop ID {$eventWorkshopId} not found."]; // Return specific error
        }

        $fetchedData['ew_data'] = $resultEwEvent->fetch_assoc();
        $stmtEwEvent->close();

        $currentEventDate = $fetchedData['ew_data']['event_date']; // Event date needed for cooldown check

        // --- Step 1b: Fetch Rankings for this Event Workshop ---
        $sqlRankings = "SELECT
                            r.user_id, r.ranking_number, r.user_type
                        FROM rankings AS r
                        WHERE r.event_workshop_id = ?
                        ORDER BY r.user_type, r.ranking_number ASC"; // Order is crucial!

        $stmtRankings = $conn->prepare($sqlRankings);
        if (!$stmtRankings) throw new Exception("Prepare failed (rankings): " . $conn->error);

        $stmtRankings->bind_param("i", $eventWorkshopId);
        $stmtRankings->execute();
        $resultRankings = $stmtRankings->get_result();

        // Load rankings and separate them by type while preserving order
        $ranked_students = [];
        $ranked_teachers = [];
        while ($row = $resultRankings->fetch_assoc()) {
            $fetchedData['rankings'][] = $row; // Save the original ranking entry
            if ($row['user_type'] === 'student') {
                $ranked_students[] = $row;
            } elseif ($row['user_type'] === 'teacher') {
                $ranked_teachers[] = $row;
            }
        }
        $stmtRankings->close();

        // --- Step 1c: Fetch Existing Invitations for this Event Workshop ---
        // Lock the rows using `FOR UPDATE` to prevent race conditions if multiple processes run concurrently.
        // The lock is held until the transaction is committed or rolled back.
        $sqlInvitations = "SELECT
                               pi.user_id, pi.status, pi.invitation_id
                           FROM participant_invitations AS pi
                           WHERE pi.event_workshop_id = ? FOR UPDATE"; // Locking!

        $stmtInvitations = $conn->prepare($sqlInvitations);
        if (!$stmtInvitations) throw new Exception("Prepare failed (invitations): " . $conn->error);

        $stmtInvitations->bind_param("i", $eventWorkshopId);
        $stmtInvitations->execute();
        $resultInvitations = $stmtInvitations->get_result();

        // Store existing invitations, keyed by user_id for easy lookup
        while ($row = $resultInvitations->fetch_assoc()) {
            $userId = (int)$row['user_id'];
            $fetchedData['invitations'][$userId] = [
                'status' => $row['status'],
                'invitation_id' => (int)$row['invitation_id']
            ];
        }
        $stmtInvitations->close();


        // --- Step 2: Calculate Required Participant Count ---
        $base_students_required = (int)($fetchedData['ew_data']['number_of_mentors_required'] ?? 0);
        $base_teachers_required = (int)($fetchedData['ew_data']['number_of_teachers_required'] ?? 0);
        $busyness = $fetchedData['ew_data']['busyness'] ?? 'high';

        // Adjust required numbers based on busyness level
        if (strtolower($busyness) === 'low') {
            $needed_students = (int)ceil($base_students_required * 0.5);
            $needed_teachers = (int)ceil($base_teachers_required * 0.5);
        } else {
            $needed_students = $base_students_required;
            $needed_teachers = $base_teachers_required;
        }
        // If no participants are needed, no point in continuing
         if ($needed_students <= 0 && $needed_teachers <= 0) {
             error_log("processWorkshopInvitations({$eventWorkshopId}): No participants required (S:0, T:0).");
             $conn->commit(); // Commit the transaction (nothing changed)
             return $fetchedData; // Return the current state
         }

        $fetchedData['needed_students'] = $needed_students;
        $fetchedData['needed_teachers'] = $needed_teachers;

        // --- Step 3: Calculate Current State of Invitations ---
        // Count accepted and pending invitations based on the fetched data
        // Note: Uses the $fetchedData['invitations'] array populated in Step 1c
        foreach ($fetchedData['invitations'] as $userId => $invitationData) {
            // Find the user's type from the ranking list (or participants table if needed)
            $userType = null;
            foreach ($fetchedData['rankings'] as $rankedUser) {
                if ((int)$rankedUser['user_id'] === $userId) {
                    $userType = $rankedUser['user_type'];
                    break;
                }
            }
            // Log and skip if user has an invitation but isn't in the ranking (should not happen)
            if ($userType === null) {
                error_log("processWorkshopInvitations({$eventWorkshopId}): User {$userId} has invitation but not found in rankings. Skipping count.");
                continue;
            }

            // Increment counts based on status and type
            if ($invitationData['status'] === 'accepted') {
                if ($userType === 'student') $fetchedData['accepted_students']++;
                elseif ($userType === 'teacher') $fetchedData['accepted_teachers']++;
            } elseif ($invitationData['status'] === 'pending') {
                if ($userType === 'student') $fetchedData['pending_students']++;
                elseif ($userType === 'teacher') $fetchedData['pending_teachers']++;
            }
            // 'rejected' status is ignored for counting purposes here.
        }

        // --- Step 4: Check if Target is Met (based on ACCEPTED only) ---
        // If the number of ACCEPTED mentors already meets the requirement, no more invites needed.
        if ($fetchedData['accepted_students'] >= $needed_students && $fetchedData['accepted_teachers'] >= $needed_teachers) {
            $fetchedData['target_met'] = true;
            error_log("processWorkshopInvitations({$eventWorkshopId}): Target met by ACCEPTED participants. Accepted S:{$fetchedData['accepted_students']}/{$needed_students}, T:{$fetchedData['accepted_teachers']}/{$needed_teachers}");
            $conn->commit(); // Commit the transaction (no new invites were sent)
            return $fetchedData; // Return the current state
        }

        error_log("processWorkshopInvitations({$eventWorkshopId}): Status before sending - Needed S:{$needed_students}, T:{$needed_teachers} | Accepted S:{$fetchedData['accepted_students']}, Pending S:{$fetchedData['pending_students']} | Accepted T:{$fetchedData['accepted_teachers']}, Pending T:{$fetchedData['pending_teachers']}");


        // --- Step 5: Iterate Through Rankings and Send New Invitations ---

        // --- Invite Students ---
        if ($fetchedData['accepted_students'] < $needed_students) { // Only invite if more students are needed
            foreach ($ranked_students as $student) {
                $userId = (int)$student['user_id'];
                $rankingNumber = (int)$student['ranking_number'];

                // a) Check Capacity Limit: (accepted + pending + newly_invited_in_this_run)
                //    IMPORTANT: Only send if the potential total (if this one is accepted) doesn't exceed the need.
                //    Prevents over-inviting.
                $potential_total_students = $fetchedData['accepted_students'] + $fetchedData['pending_students'] + $fetchedData['newly_invited_students'];
                if ($potential_total_students >= $needed_students) {
                    error_log("processWorkshopInvitations({$eventWorkshopId}): Student limit reached ({$potential_total_students} >= {$needed_students}). Stopping student invites.");
                    break; // No more student invites for this workshop
                }

                // b) Check if Already Invited: Does this user already have an invitation (any status)?
                if (isset($fetchedData['invitations'][$userId])) {
                    $fetchedData['skipped_already_invited_students']++;
                    error_log("processWorkshopInvitations({$eventWorkshopId}): Skipping student {$userId} (rank {$rankingNumber}), already has invitation status: " . $fetchedData['invitations'][$userId]['status']);
                    continue; // Move to the next student in rank
                }

                // c) Check Cooldown: Has the user participated recently?
                if (isOnCooldown($userId, $currentEventDate, INVITATION_COOLDOWN_DAYS, $conn)) {
                    $fetchedData['skipped_cooldown_students']++;
                     error_log("processWorkshopInvitations({$eventWorkshopId}): Skipping student {$userId} (rank {$rankingNumber}) due to cooldown.");
                    continue; // Move to the next student in rank
                }

                // d) Send Invitation: If all checks pass, insert a new 'pending' invitation.
                $sqlInsertInvite = "INSERT INTO participant_invitations (event_workshop_id, user_id, ranking_number, status) VALUES (?, ?, ?, 'pending')";
                $stmtInsert = $conn->prepare($sqlInsertInvite);
                if (!$stmtInsert) {
                    // On prepare failure, better to rollback the whole transaction for consistency.
                    throw new Exception("Prepare failed (insert student invite user {$userId}): " . $conn->error);
                }
                $stmtInsert->bind_param("iii", $eventWorkshopId, $userId, $rankingNumber);
                $success = $stmtInsert->execute();
                if (!$success) {
                     // On execute failure, rollback.
                     throw new Exception("Execute failed (insert student invite user {$userId}): " . $stmtInsert->error);
                }
                $stmtInsert->close();

                // Increment the count of newly invited students for this run
                $fetchedData['newly_invited_students']++;
                error_log("processWorkshopInvitations({$eventWorkshopId}): Sent PENDING invitation to student {$userId} (rank {$rankingNumber}).");

            } // end foreach student
        } else {
             error_log("processWorkshopInvitations({$eventWorkshopId}): No new student invitations needed (accepted >= needed).");
        }


        // --- Invite Teachers ---
        // Apply the same logic as for students, using teacher data.
        if ($fetchedData['accepted_teachers'] < $needed_teachers) { // Only invite if more teachers are needed
            foreach ($ranked_teachers as $teacher) {
                $userId = (int)$teacher['user_id'];
                $rankingNumber = (int)$teacher['ranking_number'];

                 // a) Check Capacity Limit: (accepted + pending + newly_invited_in_this_run)
                $potential_total_teachers = $fetchedData['accepted_teachers'] + $fetchedData['pending_teachers'] + $fetchedData['newly_invited_teachers'];
                 if ($potential_total_teachers >= $needed_teachers) {
                    error_log("processWorkshopInvitations({$eventWorkshopId}): Teacher limit reached ({$potential_total_teachers} >= {$needed_teachers}). Stopping teacher invites.");
                    break; // No more teacher invites for this workshop
                }

                // b) Check if Already Invited:
                if (isset($fetchedData['invitations'][$userId])) {
                    $fetchedData['skipped_already_invited_teachers']++;
                     error_log("processWorkshopInvitations({$eventWorkshopId}): Skipping teacher {$userId} (rank {$rankingNumber}), already has invitation status: " . $fetchedData['invitations'][$userId]['status']);
                    continue; // Move to the next teacher
                }

                // c) Check Cooldown:
                if (isOnCooldown($userId, $currentEventDate, INVITATION_COOLDOWN_DAYS, $conn)) {
                    $fetchedData['skipped_cooldown_teachers']++;
                    error_log("processWorkshopInvitations({$eventWorkshopId}): Skipping teacher {$userId} (rank {$rankingNumber}) due to cooldown.");
                    continue; // Move to the next teacher
                }

                // d) Send Invitation:
                 $sqlInsertInvite = "INSERT INTO participant_invitations (event_workshop_id, user_id, ranking_number, status) VALUES (?, ?, ?, 'pending')";
                $stmtInsert = $conn->prepare($sqlInsertInvite);
                 if (!$stmtInsert) {
                     throw new Exception("Prepare failed (insert teacher invite user {$userId}): " . $conn->error);
                 }
                 $stmtInsert->bind_param("iii", $eventWorkshopId, $userId, $rankingNumber);
                 $success = $stmtInsert->execute();
                 if (!$success) {
                      throw new Exception("Execute failed (insert teacher invite user {$userId}): " . $stmtInsert->error);
                 }
                 $stmtInsert->close();

                // Increment the count of newly invited teachers
                $fetchedData['newly_invited_teachers']++;
                error_log("processWorkshopInvitations({$eventWorkshopId}): Sent PENDING invitation to teacher {$userId} (rank {$rankingNumber}).");

            } // end foreach teacher
        } else {
             error_log("processWorkshopInvitations({$eventWorkshopId}): No new teacher invitations needed (accepted >= needed).");
        }

        // --- Commit Transaction ---
        // If we reached this point without errors, finalize the changes (new invitations).
        $conn->commit();
        error_log("processWorkshopInvitations({$eventWorkshopId}): Successfully processed. Newly invited S:{$fetchedData['newly_invited_students']}, T:{$fetchedData['newly_invited_teachers']}");

        // Return the full results data structure
        return $fetchedData;

    } catch (mysqli_sql_exception $e) {
        // Rollback transaction on database errors
        $conn->rollback();
        error_log("Database Error in processWorkshopInvitations({$eventWorkshopId}): (" . $e->getCode() . ") " . $e->getMessage() . " - Transaction rolled back.");
        // Return an error object/array
        return ['error' => 'Database error during processing', 'details' => $e->getMessage()];
    } catch (Exception $e) {
        // Rollback transaction on general errors
        $conn->rollback();
        error_log("General Error in processWorkshopInvitations({$eventWorkshopId}): " . $e->getMessage() . " - Transaction rolled back.");
         // Return an error object/array
         return ['error' => 'General error during processing', 'details' => $e->getMessage()];
    }
}
