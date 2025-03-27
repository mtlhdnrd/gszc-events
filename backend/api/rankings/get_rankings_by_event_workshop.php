<?php
require_once "../../config.php";
require_once "../../api_utils.php";

header('Content-Type: application/json');

// Validate GET parameters
if (!isset($_GET['event_workshop_id'], $_GET['user_type'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required parameters: event_workshop_id and user_type']);
    exit;
}

$event_workshop_id = filter_input(INPUT_GET, 'event_workshop_id', FILTER_VALIDATE_INT);
$user_type = filter_input(INPUT_GET, 'user_type', FILTER_SANITIZE_STRING);

// Further validation
if (!$event_workshop_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid event_workshop_id parameter.']);
    exit;
}
if (!in_array($user_type, ['student', 'teacher'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid user_type parameter. Must be "student" or "teacher".']);
    exit;
}

try {
    // Prepare the main query
    $query = "SELECT
                r.ranking_id,
                r.event_workshop_id,
                r.user_id,
                p.name AS user_name,
                r.ranking_number,
                r.user_type,
                w.name AS workshop_name,
                e.name AS event_name
              FROM rankings r
              JOIN participants p ON r.user_id = p.user_id
              JOIN event_workshop ew ON r.event_workshop_id = ew.event_workshop_id
              JOIN workshops w ON ew.workshop_id = w.workshop_id
              JOIN events e ON ew.event_id = e.event_id
              WHERE r.event_workshop_id = ? AND r.user_type = ?
              ORDER BY r.ranking_number ASC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("is", $event_workshop_id, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();

    $rankings = [];
    while ($row = $result->fetch_assoc()) {
        $rankings[] = $row;
    }

    $stmt->close();

    echo json_encode($rankings);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Error fetching rankings: " . $e->getMessage()));
}
?>