<?php
// CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$path = __DIR__ . '/settings.json';
if (file_exists($path)) {
  echo file_get_contents($path);
} else {
  echo json_encode([]);
}
