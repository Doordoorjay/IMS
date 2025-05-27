<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';
global $conn;

// 读取 JSON
$input = json_decode(file_get_contents('php://input'), true);
$code = $conn->real_escape_string($input['code'] ?? '');
$action = $conn->real_escape_string($input['action'] ?? '');
$details = $input['details'] ?? null;

if (!$code || !$action) {
  echo json_encode(['success' => false, 'error' => '缺少必要字段']);
  exit;
}

// 更新物品状态
$update = $conn->prepare("UPDATE items SET status = ? WHERE code = ?");
$update->bind_param('ss', $action, $code);
if (!$update->execute()) {
  echo json_encode(['success' => false, 'error' => '更新状态失败: ' . $conn->error]);
  exit;
}

// 写入日志
$log = $conn->prepare("INSERT INTO logs (code, action, details) VALUES (?, ?, ?)");
$jsonDetails = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
$log->bind_param('sss', $code, $action, $jsonDetails);
if (!$log->execute()) {
  echo json_encode(['success' => false, 'error' => '写入日志失败: ' . $conn->error]);
  exit;
}

echo json_encode(['success' => true]);
