<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// 获取 JSON 输入
$data = json_decode(file_get_contents('php://input'), true);

// 提取字段
$host = $data['db_host'] ?? '';
$user = $data['db_user'] ?? '';
$pass = $data['db_pass'] ?? '';
$name = $data['db_name'] ?? '';

// 测试连接
$conn = @new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => '连接失败: ' . $conn->connect_error,
    ]);
} else {
    echo json_encode(['success' => true]);
    $conn->close();
}
