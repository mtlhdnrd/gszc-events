<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("name", "email", "phone"))) {
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



    $query = "INSERT INTO `teachers` (`name`, `email`, `phone`) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);

    $stmt->bind_param("sss", $name, $email, $phone);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['message' => 'Tanár sikeresen hozzáadva.', 'teacher_id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Hiba történt a tanár hozzáadása közben: ' . $stmt->error]);
        error_log("Database error in add_teacher.php: " . $stmt->error); 
    }

    $stmt->close(); 

} else {
    http_response_code(400);
    echo json_encode(['message' => 'Hiányzó kötelező paraméterek.']);
}
?>