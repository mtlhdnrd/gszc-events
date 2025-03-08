<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

try {
    $query = "SELECT `teacher_id`, `name`, `email`, `phone` FROM `teachers`";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $result = $stmt->get_result();  // Get the result set
    $teachers = $result->fetch_all(MYSQLI_ASSOC); // Fetch all rows as an associative array

    http_response_code(200); // OK
    echo json_encode($teachers); // Send the data as JSON

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Hiba történt a tanárok lekérése közben: ' . $e->getMessage()]);
    error_log("Error in get_teachers.php: " . $e->getMessage()); // Log the error
}

?>