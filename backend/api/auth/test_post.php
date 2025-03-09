<?php
  header('Content-Type: application/json');
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      echo json_encode(['message' => 'POST request received']);
  } else {
      echo json_encode(['message' => 'Not a POST request', 'method' => $_SERVER['REQUEST_METHOD']]);
  }
?>