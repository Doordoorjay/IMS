<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET，OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db.php';
global $conn;

$status = $_GET['status'] ?? null;
$location_id = $_GET['location_id'] ?? null;

$where = [];
$params = [];
$types = "";

// 处理 status
if ($status && in_array($status, ['available', 'given', 'used', 'lost'])) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

// 处理 location_id
if ($location_id !== null && $location_id !== '') {
    $where[] = "location_id = ?";
    $params[] = $location_id;
    $types .= "i";  // location_id 是 int 类型
}

// 拼接 SQL
$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "SELECT * FROM items $whereSql ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if (count($params)) {
    $stmt->bind_param($types, ...$params);
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
                'giftType' => $info['giftType'] ?? $info['type'] ?? '',
                'event' => $info['event'] ?? '',
                'date' => $info['date'] ?? $item['last_action_date']
            ];
        }
    }
}

echo json_encode(['success' => true, 'items' => $items]);
$conn->close();
