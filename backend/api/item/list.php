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

$status = $_GET['status'] ?? null;

// 查询物品（根据状态过滤）
if ($status && in_array($status, ['available', 'given', 'used', 'lost'])) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE status = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $status);
} else {
    $stmt = $conn->prepare("SELECT * FROM items ORDER BY created_at DESC");
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// 查每个物品最近一次操作日志
$logStmt = $conn->prepare("
    SELECT l.code, l.action, l.details
    FROM logs l
    INNER JOIN (
        SELECT code, MAX(id) AS max_id
        FROM logs
        GROUP BY code
    ) latest ON l.id = latest.max_id
");
$logStmt->execute();
$logResult = $logStmt->get_result();

$logMap = [];
while ($log = $logResult->fetch_assoc()) {
    $details = json_decode($log['details'], true);
    if (!$details || !isset($details['date'])) continue;

    $logMap[$log['code']] = [
        'action' => $log['action'],
        'date' => $details['date'],
        'info' => $details
    ];
}
$logStmt->close();

// 合并 log 信息到物品
foreach ($items as &$item) {
    $code = $item['code'];
    if (isset($logMap[$code])) {
        $item['last_action_type'] = $logMap[$code]['action'];
        $item['last_action_date'] = $logMap[$code]['date'];

        // 如果是赠送物品，附加赠送信息
        if ($item['status'] === 'given' && $logMap[$code]['action'] === 'given') {
            $info = $logMap[$code]['info'];
            $item['given_info'] = [
                'to' => $info['to'] ?? '',
                'method' => $info['method'] ?? '',
                'type' => $info['giftType'] ?? $info['type'] ?? '',
                'event' => $info['event'] ?? '',
                'date' => $info['date'] ?? $item['last_action_date']
            ];
        }
    }
}

echo json_encode(['success' => true, 'items' => $items]);
$conn->close();
