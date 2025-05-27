<?php
$data = json_decode(file_get_contents("php://input"), true);
$code = $data['code'] ?? '';
$status = $data['status'] ?? '';

require_once '../db.php';

$stmt = $conn->prepare("UPDATE products SET status = ? WHERE code = ?");
$stmt->bind_param("ss", $status, $code);
$stmt->execute();

// 记录日志
$action = "更改状态为 $status";
logAction($conn, $code, $action);

echo json_encode(["success" => true]);
$stmt->close();
$conn->close();
