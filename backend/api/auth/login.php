<?php
// /backend/api/auth/login.php

require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
//require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php"; // Ezt most nem használjuk
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/src/JWT.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/src/Key.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Ellenőrizzük, hogy POST kérés érkezett-e
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // JSON adatok fogadása (a $_POST helyett, mert Content-Type: application/json)
    $request_data = json_decode(file_get_contents('php://input'), true);


    // Ellenőrizzük, hogy megkaptuk-e a szükséges adatokat
    if (isset($request_data['username'], $request_data['password'])) {
        $username = $request_data['username'];
        $password = $request_data['password']; // Nincs hashelés!

        // 1. Felhasználó lekérdezése az adatbázisból
        $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // 2. Jelszó ellenőrzése (sima szöveges összehasonlítás)
        if ($user && $password === $user['password']) {  // Sima string összehasonlítás
            // 3. Sikeres bejelentkezés: JWT token generálása
            $key = 'titkos_kulcsod'; // !!! Ezt a kulcsot BIZTONSÁGOS helyen tárold (pl. környezeti változóban)!
            $payload = [
                'iss' => 'bgszc-events',
                'aud' => 'bgszc-events',
                'iat' => time(),
                'exp' => time() + 3600,
                'user_id' => $user['user_id'],
                'username' => $user['username']
            ];

            $jwt = JWT::encode($payload, $key, 'HS256');

            // 4. Válasz küldése (token + user adatok)
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'token' => $jwt,
                'user' => [
                    'userId' => $user['user_id'],
                    'username' => $user['username'],
                ]
            ]);

        } else {
            // 5. Sikertelen bejelentkezés
            header('Content-Type: application/json');
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Invalid username or password.']);
        }

    } else {
        // Hiányzó adatok
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Missing username or password."]);
    }

} else {
    // Nem POST kérés
    http_response_code(405); // Method Not Allowed
     echo json_encode(["error" => "Invalid request method. Use POST."]);
}
?>