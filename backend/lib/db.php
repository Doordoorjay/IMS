<?php
$config = require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);

if ($conn->connect_error) {
    die('数据库连接失败: ' . $conn->connect_error);
}
