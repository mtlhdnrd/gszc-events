<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("teacher_id", "name", "email", "phone"))) {
    $teacher_id = intval($_POST["teacher_id"]);
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    $phone = htmlspecialchars($_POST["phone"]);

    $errors = [];

    if (empty($name)) {
        $errors[] = "A név megadása kötelező.";
    } elseif (strlen($name) > 255) {
        $errors[] = "A név túl hosszú (maximum 255 karakter).";
    }

    if (empty($email)) {
        $errors[] = "Az email cím megadása kötelező.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Érvénytelen email cím formátum.";
    } elseif (strlen($email) > 130) {
        $errors[] = "Az email cím túl hosszú (maximum 130 karakter).";
    }

    if (empty($phone)) {
        $errors[] = "A telefonszám megadása kötelező.";
    } elseif (strlen($phone) > 20) {
        $errors[] = "A telefonszám túl hosszú (maximum 20 karakter).";
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['message' => 'Érvénytelen adatok.', 'errors' => $errors]);
        exit;
    }


    $query = "UPDATE `teachers` SET `name` = ?, `email` = ?, `phone` = ? WHERE `teacher_id` = ?";
    $stmt = $conn->prepare($query);

    $stmt->bind_param("sssi", $name, $email, $phone, $teacher_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200); // OK
            echo json_encode(['message' => 'Tanár sikeresen frissítve.']);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['message' => 'A megadott azonosítóval nem található tanár.']);
        }
    } else {
        http_response_code(500); 
        echo json_encode(['message' => 'Hiba történt a tanár frissítése közben: ' . $stmt->error]);
        error_log("Database error in update_teacher.php: " . $stmt->error); // Log the error

    }
        $stmt->close();

} else {
    http_response_code(400);
    echo json_encode(['message' => 'Hiányzó kötelező paraméterek.']);
}
?>