<?php
  header('Content-Type: application/json');
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $_POST = $data;
    echo json_encode(count($_POST));
      //echo json_encode(['message' => 'POST request received']);
  } else {
      echo json_encode(['message' => 'Not a POST request', 'method' => $_SERVER['REQUEST_METHOD']]);
  }
?>