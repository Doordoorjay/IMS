<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';
global $conn;

$query = $_GET['q'] ?? '';
if (!$query) {
  echo json_encode(['success' => false, 'error' => 'Missing query']);
  exit;
}

// 判断是 code 还是 upc（简单规则）
if (preg_match('/^I\d{6}-\d+$/', $query)) {
  // 查唯一 code
  $stmt = $conn->prepare("SELECT * FROM items WHERE code = ?");
  $stmt->bind_param("s", $query);
  $stmt->execute();
  $res = $stmt->get_result();
  $item = $res->fetch_assoc();

  echo json_encode(['success' => true, 'type' => 'code', 'item' => $item ?: null]);
} else {
  // 查 upc 下所有物品
  $stmt = $conn->prepare("SELECT * FROM items WHERE UPC = ? ORDER BY created_at DESC");
  $stmt->bind_param("s", $query);
  $stmt->execute();
  $res = $stmt->get_result();

  $items = [];
  while ($row = $res->fetch_assoc()) $items[] = $row;

  echo json_encode(['success' => true, 'type' => 'upc', 'items' => $items]);
}

$stmt->close();
$conn->close();
