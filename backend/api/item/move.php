<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';
global $conn;

// 读取 JSON body
$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? null;
$locationId = $input['location_id'] ?? null;

if (!$code || !$locationId) {
  echo json_encode(['success' => false, 'error' => '缺少参数 code 或 location_id']);
  exit;
}

// 准备并执行更新语句
$stmt = $conn->prepare("UPDATE items SET location_id = ? WHERE code = ?");
if (!$stmt) {
  echo json_encode(['success' => false, 'error' => '数据库预处理失败']);
  exit;
}

$stmt->bind_param("is", $locationId, $code);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => '数据库更新失败']);
}

$stmt->close();
$conn->close();
