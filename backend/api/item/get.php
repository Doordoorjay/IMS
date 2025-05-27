<?php
// 允许跨域
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// 预检请求直接返回
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';

if (!isset($_GET['code'])) {
    echo json_encode(['success' => false, 'error' => 'Missing code']);
    exit;
}

$code = $_GET['code'];
$stmt = $conn->prepare("SELECT * FROM items WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Item not found']);
} else {
    $item = $result->fetch_assoc();
    echo json_encode(['success' => true, 'item' => $item]);
}

$stmt->close();
$conn->close();
