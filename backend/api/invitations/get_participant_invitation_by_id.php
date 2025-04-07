<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";

$userId = $_GET['userId'] ?? null;

if (validate_request("GET", array("userId")) && $userId !== null) { // Nem kell kötelező paraméter, csak a userId

    if (!is_numeric($userId)) {
        http_response_code(400); // Bad Request (technically shouldn't happen if token verification is okay)
        echo json_encode(["error" => "Invalid user ID derived from token."]);
        exit;
    }
    
    // Function to execute query and fetch a single row
    function fetchSingleInvitation($conn, $sql, $userId) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $invitation = $result->fetch_assoc(); // Fetch only one row
        $stmt->close();
        return $invitation; // Returns null if no row found
    }
    
    try {
        // --- Query 1: Check for PENDING invitation ---
        $queryPending = "SELECT
                            si.invitation_id, si.event_workshop_id, si.user_id, si.status,
                            e.name AS event_name, w.name AS workshop_name, p.name AS participant_name, -- Renamed s.name
                            ew.event_id, ew.workshop_id, si.ranking_number,
                            ew.max_workable_hours, ew.number_of_mentors_required,
                            e.date AS event_date, -- Return full date for ordering
                            e.location AS event_location -- Added location
                          FROM participant_invitations si
                          JOIN event_workshop ew ON si.event_workshop_id = ew.event_workshop_id
                          JOIN events e ON ew.event_id = e.event_id
                          JOIN workshops w ON ew.workshop_id = w.workshop_id
                          JOIN participants p ON si.user_id = p.user_id -- Joined participants instead of student
                          WHERE si.user_id = ? AND si.status = 'pending'
                          LIMIT 1"; // We only need one pending
    
        $invitation = fetchSingleInvitation($conn, $queryPending, $userId);
    
        // --- Query 2: If no pending, check for latest Accepted/Rejected ---
        if ($invitation === null) {
            error_log("No pending invitation found for user {$userId}. Checking for accepted/rejected.");
            $queryLatestNonPending = "SELECT
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
                                      WHERE si.user_id = ? AND si.status IN ('accepted', 'rejected') -- Include reaccepted if used
                                      ORDER BY e.date DESC, si.invitation_id DESC -- Order by event date descending, then ID as fallback
                                      LIMIT 1"; // Get the most recent one
    
            $invitation = fetchSingleInvitation($conn, $queryLatestNonPending, $userId);
        }
    
        // --- Output Result ---
        if ($invitation !== null) {
            // Format the date for consistency if needed, or let Flutter handle it
            // $invitation['event_date_formatted'] = date('Y-m-d H:i', strtotime($invitation['event_date']));
            http_response_code(200);
            echo json_encode($invitation); // Return single object
        } else {
            // No relevant invitation found
            http_response_code(404); // Not Found is appropriate here
            echo json_encode(null); // Return JSON null explicitly
        }
    
    } catch (Exception $e) {
        error_log("Error fetching invitation for user {$userId}: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
}