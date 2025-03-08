<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("DELETE", array("teacher_id"))) {
    $teacher_id = $_GET["teacher_id"];

    // Input validation: Ensure teacher_id is an integer.
    if (!is_numeric($teacher_id)) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Érvénytelen tanár azonosító.']);
        exit;
    }

    $query = "DELETE FROM `teachers` WHERE `teacher_id` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacher_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200); // OK
            echo json_encode(['message' => 'Tanár sikeresen törölve.']);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['message' => 'A megadott azonosítóval nem található tanár.']);
        }
    } else {
        // Database error
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Hiba történt a tanár törlése közben: ' . $stmt->error]);
        error_log("Database error in delete_teacher.php: " . $stmt->error);
    }

    $stmt->close();

} else {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Hiányzó kötelező paraméter: teacher_id.']);
}
?>