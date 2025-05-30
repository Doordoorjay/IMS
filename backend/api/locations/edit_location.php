<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;
$name = trim($data['name'] ?? '');

if (!$id || $name === '') {
  echo json_encode([
    'success' => false,
    'error' => '参数不完整'
  ]);
  exit;
}

$stmt = $conn->prepare("UPDATE locations SET name = ? WHERE id = ?");
if (!$stmt) {
  echo json_encode(['success' => false, 'error' => '数据库预处理失败']);
  exit;
}

$stmt->bind_param("si", $name, $id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode([
    'success' => false,
    'error' => $conn->error ?: '数据库执行失败'
  ]);
}

$stmt->close();
$conn->close();
