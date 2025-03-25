<?php
require_once "../../config.php";
require_once "../../api_utils.php";

if (validate_request("POST", array("username", "password"))) {
    header("Content-Type: application/json");

    $username = htmlspecialchars($_POST["username"]);
    $password = $_POST["password"];
    if (empty($username) || empty($password)) {
        http_response_code(400); // Bad Request
        echo json_encode(array("message" => "Username and password are required."));
        exit();
    }

    // Check username length
    if (strlen($username) < 5 || strlen($username) > 255) {
        http_response_code(400);
        echo json_encode(array("message" => "Username must be between 5 and 255 characters."));
        exit();
    }

    // Check if username already exists
    $check_query = "SELECT user_id FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        http_response_code(409); // Conflict (username already exists)
        echo json_encode(array("message" => "Username already exists."));
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();


    // --- Hash the Password ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // --- Insert into Database ---
    $query = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(array("message" => "Prepare failed: " . $conn->error));
        exit();
    }

    $stmt->bind_param("ss", $username, $hashed_password);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        // Return the user_id
        http_response_code(201); // Created
        echo json_encode(array("message" => "User created successfully.", "user_id" => $user_id));

    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Database error: " . $stmt->error));
    }

    $stmt->close();

} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid request."));
}

?>