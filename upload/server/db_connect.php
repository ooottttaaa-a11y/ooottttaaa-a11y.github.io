<?php
// db_connect.php - Centralized database connection

$configPath = __DIR__ . '/db_config.ini';

if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Configuration file not found']);
    exit;
}

$config = parse_ini_file($configPath);

if (!$config) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to parse configuration file']);
    exit;
}

$host = $config['host'];
$db   = $config['dbname'];
$user = $config['user'];
$pass = $config['password'];
$charset = $config['charset'];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    // Log error securely in a real app, strict output here
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}
?>
