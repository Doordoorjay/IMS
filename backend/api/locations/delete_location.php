<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => '无效的位置 ID']);
    $conn->close();
    exit;
}

try {
    // 删除位置
    $conn->query("DELETE FROM locations WHERE id = $id");
    // 更新对应 items
    $conn->query("UPDATE items SET location_id = NULL WHERE location_id = $id");

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();