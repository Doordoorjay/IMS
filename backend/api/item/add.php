<?php
// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';
global $conn;

// 确保 POST 类型
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'error' => 'Invalid request method']);
  exit;
}

// 获取字段
$name = $_POST['name'] ?? '';
$upc = $_POST['upc'] ?? null;
$source = $_POST['source'] ?? null;
$venue = $_POST['venue'] ?? null;
$code = $_POST['code'] ?? '';
$received_at = $_POST['received_at'] ?? null;

if (!$name || !$code) {
  echo json_encode(['success' => false, 'error' => 'Missing name or code']);
  exit;
}

// 处理图片上传
$photo_url = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
  $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

  $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
  $filename = uniqid('item_', true) . '.' . $ext;
  $target = $uploadDir . $filename;

  if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
    $photo_url = '/uploads/' . $filename;
  }
}

// 插入数据库
$stmt = $conn->prepare("INSERT INTO items (code, UPC, name, source, venue, received_at, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $code, $upc, $name, $source, $venue, $received_at, $photo_url);

if ($stmt->execute()) {
  echo json_encode(['success' => true, 'message' => 'Item added']);
} else {
  echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
