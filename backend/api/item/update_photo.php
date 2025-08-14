<?php
// 允许跨域
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// 预检请求直接返回
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

$code = $_POST['code'] ?? null;
if (!$code) {
  echo json_encode(['success' => false, 'error' => 'Missing item code']);
  exit;
}

$photo_url = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
  $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
  $filename = uniqid('item_photo_', true) . '.' . $ext;
  $target = $uploadDir . $filename;

  if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
    $photo_url = '/uploads/' . $filename;
  }
} else {
  echo json_encode(['success' => false, 'error' => 'No photo uploaded or upload error occurred']);
  exit;
}

// 更新数据库
$stmt = $conn->prepare("UPDATE items SET photo_url = ? WHERE code = ?");
$stmt->bind_param("ss", $photo_url, $code);

if ($stmt->execute()) {
  echo json_encode(['success' => true, 'message' => '图片更新成功', 'photo_url' => $photo_url]);
} else {
  echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();