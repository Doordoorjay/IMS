<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$lockFile = __DIR__ . '/../install.lock';

if (file_exists($lockFile)) {
    echo json_encode(['installed' => true]);
} else {
    echo json_encode(['installed' => false]);
}
