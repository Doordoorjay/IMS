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
$name = trim($data['name'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'error' => '位置名称不能为空']);
    $conn->close();
    exit;
}

try {
    // 检查重复
    $stmt = $conn->prepare("SELECT id FROM locations WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => '该位置已存在']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // 插入
    $stmt = $conn->prepare("INSERT INTO locations (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
