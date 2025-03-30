<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";

function processWorkshopInvitations($eventWorkshopId, $conn){
    $fetchedData = [
        'ew_data' => null,
        'rankings' => [],
        'invitations' => []
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

        // --- Data Fetching Complete ---
        
        // For now, just log the fetched data (for debugging)
        error_log("processWorkshopInvitations({$eventWorkshopId}): Data fetched successfully.");
        error_log("EW Data: " . print_r($fetchedData['ew_data'], true));
        error_log("Rankings: " . print_r($fetchedData['rankings'], true));
        error_log("Invitations: " . print_r($fetchedData['invitations'], true));

        return $fetchedData;

    } catch (mysqli_sql_exception $e) {
        error_log("Database Error in processWorkshopInvitations({$eventWorkshopId}): (" . $e->getCode() . ") " . $e->getMessage());
        throw $e; 
    } catch (Exception $e) {
        error_log("General Error in processWorkshopInvitations({$eventWorkshopId}): " . $e->getMessage());
        throw $e; // Re-throw
    }
}
