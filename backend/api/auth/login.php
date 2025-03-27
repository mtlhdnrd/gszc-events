<?php

ini_set('display_errors', 1); // Show errors directly
ini_set('display_startup_errors', 1); // Show startup errors
error_reporting(E_ALL); // Report all errors and warnings

require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/api_utils.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/src/JWT.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/gszc-events/backend/src/Key.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
header('Content-Type: application/json');
// Ellenőrizzük, hogy POST kérés érkezett-e
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_data = json_decode(file_get_contents('php://input'), true);


    if (isset($request_data['username'], $request_data['password'])) {
        $username = $request_data['username'];


        // 1. Felhasználó lekérdezése az adatbázisból
        $stmt = $conn->prepare("SELECT users.user_id as `user_id`, username, participants.name as `name`, password FROM users INNER JOIN participants ON users.user_id = participants.user_id WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        error_log("Received password for user " . $username . ": " . $request_data['password']); // Log to PHP error log
        $password_from_request = trim($request_data['password']); // Trim it
        $hash_from_db = $user['password'];

        // 2. Jelszó ellenőrzése (sima szöveges összehasonlítás)
        if ($user && password_verify($password_from_request, $hash_from_db)) {
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
                    'name' =>  $user['name'],
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