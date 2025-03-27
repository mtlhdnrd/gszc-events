<?php
require_once "../../config.php";
require_once "../../api_utils.php";

// Get and decode JSON data
$json_data = file_get_contents('php://input');
$swap_data = json_decode($json_data, true);

// --- Validation ---
if (empty($swap_data) || !is_array($swap_data) || count($swap_data) !== 2) {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid data format. Expected an array of two objects."));
    exit();
}

// Validate structure of each object
$valid = true;
$update_items = []; // Store validated items
foreach ($swap_data as $item) {
    if (!isset($item['id'], $item['rank']) || !is_numeric($item['id']) || !is_numeric($item['rank'])) {
        $valid = false;
        break;
    }
    // Sanitize/cast data
    $update_items[] = [
        'id' => (int)$item['id'],
        'rank' => (int)$item['rank']
    ];
}

if (!$valid || count($update_items) !== 2) {
     http_response_code(400);
     echo json_encode(array("message" => "Invalid data structure within the array. Each object needs numeric 'id' and 'rank'."));
     exit();
}
// --- Database Operations (within a transaction) ---
$conn->begin_transaction();

try {
    // Prepare the UPDATE statement ONCE
    $update_query = "UPDATE rankings SET ranking_number = ? WHERE ranking_id = ?";
    $stmt = $conn->prepare($update_query);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    // Execute the update for both items
    foreach ($update_items as $item) {
        $stmt->bind_param("ii", $item['rank'], $item['id']);
        if (!$stmt->execute()) {
            // If one fails, the transaction will rollback
            throw new Exception("Update failed for ranking_id " . $item['id'] . ": " . $stmt->error);
        }
    }

    $stmt->close();

    // Commit the transaction
    $conn->commit();
    http_response_code(200); // OK
    echo json_encode(array("message" => "Ranking order updated successfully."));

} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Error updating ranking order: " . $e->getMessage()));
}
?>