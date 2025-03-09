<?php
// /backend/api/auth/login.php

require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/src/JWT.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/src/Key.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (validate_request("POST", ['username', 'password'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Felhasználó lekérdezése az adatbázisból
    $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();


    if ($user && password_verify($password, $user['password'])) {
        // 2. Sikeres bejelentkezés: JWT token generálása

        $key = 'titkos_kulcsod'; // !!! Ezt a kulcsot BIZTONSÁGOS helyen tárold (pl. környezeti változóban)!
        $payload = [
            'iss' => 'bgszc-events', // Kibocsátó (opcionális)
            'aud' => 'bgszc-events', // Címzett (opcionális)
            'iat' => time(),        // Kibocsátás időpontja (timestamp)
            'exp' => time() + 3600, // Lejárat időpontja (timestamp, pl. 1 óra múlva)
            'user_id' => $user['user_id'], // Felhasználó ID-ja (fontos!)
            'username' => $user['username'] // Felhasználónév (opcionális)
            // További adatok, amiket a tokenben szeretnél tárolni (pl. szerepkör, stb.)
        ];

        $jwt = JWT::encode($payload, $key, 'HS256'); // HS256 az algoritmus

        // 3. Válasz küldése (token + user adatok)
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            'token' => $jwt,
            'user' => [  // Visszaadjuk a user adatokat is (kivéve a jelszót!)
                'userId' => $user['user_id'],
                'username' => $user['username'],
                // ... további user adatok ...
            ]
        ]);

    } else {
        // 4. Sikertelen bejelentkezés
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Invalid username or password.']);
    }

} else {
    http_response_code(400); // Bad Request
     echo json_encode(["error" => "Invalid request method or missing parameters."]);
}
?>