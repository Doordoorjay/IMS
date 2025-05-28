<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !is_array($data)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid settings']);
  exit;
}

file_put_contents(__DIR__ . '/settings.json', json_encode($data, JSON_PRETTY_PRINT));
echo json_encode(['status' => 'ok']);
