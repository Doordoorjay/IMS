<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 防止重复安装
$lockFile = __DIR__ . '/../install.lock';
if (file_exists($lockFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'System already installed.'
    ]);
    exit;
}

// 解析前端发送的数据
$input = json_decode(file_get_contents('php://input'), true);
$host = $input['db_host'] ?? '';
$user = $input['db_user'] ?? '';
$pass = $input['db_pass'] ?? '';
$name = $input['db_name'] ?? '';
$siteName = $input['site_name'] ?? '';
$siteLogo = $input['site_logo'] ?? '';

// 建立数据库连接
$conn = @new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// 创建位置表
$createLocationsTable = "
CREATE TABLE IF NOT EXISTS locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($createLocationsTable)) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create locations table: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

// 创建物品表
$createItemsTable = "
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(32) UNIQUE NOT NULL,
    UPC VARCHAR(32),
    name VARCHAR(255) NOT NULL,
    source VARCHAR(255),            
    venue VARCHAR(255),      
    status VARCHAR(20) DEFAULT 'available',
    photo_url VARCHAR(255),
    received_at DATE,
    location_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
)";
if (!$conn->query($createItemsTable)) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create items table: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

// 创建 logs 表（行为日志）
$createLogsTable = "
CREATE TABLE IF NOT EXISTS logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NOT NULL,
  action VARCHAR(32) NOT NULL,
  details JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($createLogsTable)) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create logs table: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

// 关闭数据库连接
$conn->close();

// 写入配置文件 config.php
$configContent = "<?php\nreturn [\n" .
    "  'host' => '$host',\n" .
    "  'user' => '$user',\n" .
    "  'pass' => '$pass',\n" .
    "  'name' => '$name',\n" .
    "  'site_name' => '$siteName',\n" .
    "  'site_logo' => '$siteLogo'\n" .
    "];\n";
$configPath = __DIR__ . '/../config.php';

if (file_put_contents($configPath, $configContent) === false) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to write config.php'
    ]);
    exit;
}

// 写入安装锁文件
if (file_put_contents($lockFile, 'installed') === false) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to write install.lock'
    ]);
    exit;
}

// settings.json 默认配置
$settings = [
    'darkMode' => false,
    'showBackendStatus' => true
];

// 写入到 backend/api/settings.json
$settingsPath = __DIR__ . '/settings.json'; 
file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

// 安装成功
echo json_encode(['success' => true]);