<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";

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
        'target_met' => false
    ];
    try {
        // --- 1a: Fetch Event Workshop & Event Details ---
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
            return false;
        }

        $fetchedData['ew_data'] = $resultEwEvent->fetch_assoc();
        $stmtEwEvent->close();

        // --- 1b: Fetch Rankings for this Event Workshop ---
        $sqlRankings = "SELECT 
                            r.user_id, r.ranking_number, r.user_type
                        FROM rankings AS r
                        WHERE r.event_workshop_id = ? 
                        ORDER BY r.user_type, r.ranking_number ASC";

        $stmtRankings = $conn->prepare($sqlRankings);
        if (!$stmtRankings) throw new Exception("Prepare failed (rankings): " . $conn->error);

        $stmtRankings->bind_param("i", $eventWorkshopId);
        $stmtRankings->execute();
        $resultRankings = $stmtRankings->get_result();

        // Fetch all rankings into the array
        while ($row = $resultRankings->fetch_assoc()) {
            // Store the whole row;
            $fetchedData['rankings'][] = $row;
        }
        $stmtRankings->close();


        // --- 1c: Fetch Existing Invitations for this Event Workshop ---
        $sqlInvitations = "SELECT 
                               pi.user_id, pi.status, pi.invitation_id 
                           FROM participant_invitations AS pi
                           WHERE pi.event_workshop_id = ?";

        $stmtInvitations = $conn->prepare($sqlInvitations);
        if (!$stmtInvitations) throw new Exception("Prepare failed (invitations): " . $conn->error);

        $stmtInvitations->bind_param("i", $eventWorkshopId);
        $stmtInvitations->execute();
        $resultInvitations = $stmtInvitations->get_result();

        // Fetch all invitations, keying the array by user_id for easy lookup later
        while ($row = $resultInvitations->fetch_assoc()) {
            $userId = (int)$row['user_id'];
            $fetchedData['invitations'][$userId] = [
                'status' => $row['status'],
                'invitation_id' => (int)$row['invitation_id']
            ];
        }
        $stmtInvitations->close();

        /*
            $fetchedData['ew_data']
            $fetchedData['rankings']
            $fetchedData['invitations']
        */

        // --- Step 2: Calculate Required Participant Count ---

        // Get the base required numbers from the fetched data
        $base_students_required = (int)($fetchedData['ew_data']['number_of_mentors_required'] ?? 0);
        $base_teachers_required = (int)($fetchedData['ew_data']['number_of_teachers_required'] ?? 0);
        $busyness = $ewData['busyness'] ?? 'high';

        if (strtolower($busyness) === 'low') {
            $needed_students = (int)ceil($base_students_required * 0.5);
            $needed_teachers = (int)ceil($base_teachers_required * 0.5);
        } else {
            $needed_students = $base_students_required;
            $needed_teachers = $base_teachers_required;
        }

        $fetchedData['needed_students'] = $needed_students;
        $fetchedData['needed_teachers'] = $needed_teachers;


        // --- Step 3: Calculating current state of invitations

        foreach ($fetchedData['rankings'] as $rankedUser) {
            $userId = (int)$rankedUser['user_id'];
            $userType = $rankedUser['user_type'];

            if (isset($fetchedData['invitations'][$userId])) {
                $invitationStatus = $fetchedData['invitations'][$userId]['status'];
                if ($invitationStatus === 'accepted') {

                    if ($userType === 'student') {
                        $fetchedData['accepted_students']++;
                    } elseif ($userType === 'teacher') {
                        $fetchedData['accepted_teachers']++;
                    }
                } else if ($invitationStatus === 'pending') {
                    if ($userType === 'student') {
                        $fetchedData['pending_students']++;
                    } else if ($userType === 'teacher') {
                        $fetchedData['pending_teachers']++;
                    }
                }
            }
        }

        // --- Step 4: Check if Target is Met ---
        if (
            $fetchedData['accepted_students'] >= $fetchedData['needed_students'] &&
            $fetchedData['accepted_teachers'] >= $fetchedData['needed_teachers']
        ) {
            $fetchedData['target_met'] = true;
            // Log that the target is met and potentially return early
            error_log("processWorkshopInvitations({$eventWorkshopId}): Target met. Accepted S:{$fetchedData['accepted_students']}/{$fetchedData['needed_students']}, T:{$fetchedData['accepted_teachers']}/{$fetchedData['needed_teachers']}");

            return $fetchedData;
        }
        error_log("processWorkshopInvitations({$eventWorkshopId}): Status - Accepted S:{$fetchedData['accepted_students']}, Pending S:{$fetchedData['pending_students']}, Accepted T:{$fetchedData['accepted_teachers']}, Pending T:{$fetchedData['pending_teachers']}");

        // Return the complete data structure including calculated needs
        return $fetchedData;
    } catch (mysqli_sql_exception $e) {
        error_log("Database Error in processWorkshopInvitations({$eventWorkshopId}): (" . $e->getCode() . ") " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        error_log("General Error in processWorkshopInvitations({$eventWorkshopId}): " . $e->getMessage());
        throw $e; // Re-throw
    }
}
