<?php
require_once "../../config.php";
require_once "../../api_utils.php";

$json_data = file_get_contents('php://input');
$occupations_data = json_decode($json_data, true);

// Get event_id *before* the transaction
$first_entry = reset($occupations_data);
$event_id = $first_entry ? (int)$first_entry['event_id'] : null; // Use null coalescing

$conn->begin_transaction();

try {
    // 1. DELETE existing entries for the given event_id
    $delete_query = "DELETE FROM `event_workshop` WHERE `event_id` = ?";
    $delete_stmt = $conn->prepare($delete_query);

    if ($event_id !== null) {
        $delete_stmt->bind_param("i", $event_id);
        if (!$delete_stmt->execute()) {
            throw new Exception("Delete failed: " . $delete_stmt->error);
        }
    } else {
        // No event ID?  Something is very wrong.  Likely a client-side error.
        throw new Exception("No event ID found in request. Cannot delete.");
    }
    $delete_stmt->close();

    // 2. INSERT the new (checked) entries (only if there are any)
    if (!empty($occupations_data)) {
        $insert_query = "INSERT INTO `event_workshop` (`event_id`, `workshop_id`, `max_workable_hours`, `number_of_mentors_required`, `number_of_teachers_required`, `busyness`) VALUES (?, ?, ?, ?, ?, ?);";
        $stmt = $conn->prepare($insert_query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        foreach ($occupations_data as $occupation) {
            // --- IN-LOOP VALIDATION (using a modified validate_request) ---
            if (!isset($occupation['event_id'], $occupation['workshop_id'], $occupation['max_workable_hours'], $occupation['number_of_mentors_required'], $occupation['number_of_teachers_required'], $occupation['busyness']))
            {
               throw new Exception("Invalid data format: " . json_encode($occupation));
            }

            $event_id = (int) $occupation['event_id'];
            $workshop_id = (int) $occupation['workshop_id'];
            $max_workable_hours = (int) $occupation['max_workable_hours'];
            $number_of_mentors_required = (int) $occupation['number_of_mentors_required'];
            $number_of_teachers_required = (int) $occupation['number_of_teachers_required'];
            $busyness = htmlspecialchars($occupation['busyness']);
            if(!in_array($busyness, ['high', 'low'])) {
                throw new Exception("Wrong busyness value");
            }

            $stmt->bind_param("iiiiis", $event_id, $workshop_id, $max_workable_hours, $number_of_mentors_required, $number_of_teachers_required, $busyness);

            if (!$stmt->execute()) {
                throw new Exception("Insert failed: " . $stmt->error);
            }
        }
        $stmt->close();
    } // End if (!empty($occupations_data))


    $conn->commit();
    http_response_code(200);
    echo json_encode(array("message" => "Data saved successfully."));

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(array("message" => "Error: " . $e->getMessage()));
}
?>