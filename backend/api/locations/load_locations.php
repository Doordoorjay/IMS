<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';


$locations = [];

try {
    $result = $conn->query("SELECT id, name FROM locations ORDER BY id ASC");
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }

    echo json_encode(['success' => true, 'locations' => $locations]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
