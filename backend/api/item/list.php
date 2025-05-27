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

$status = $_GET['status'] ?? 'available';  // 默认只查在库物品

$stmt = $conn->prepare("SELECT * FROM items WHERE status = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
  $items[] = $row;
}

echo json_encode(['success' => true, 'items' => $items]);

$stmt->close();
$conn->close();
