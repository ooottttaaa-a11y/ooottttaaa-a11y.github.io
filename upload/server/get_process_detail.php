<?php
// get_process_detail.php - API to fetch raw records for a specific process and machine

// Database configuration
require_once 'db_connect.php';

// Parameters
$machine = isset($_GET['machine']) ? $_GET['machine'] : '';
$process = isset($_GET['process']) ? $_GET['process'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

if (empty($machine) || empty($process)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Machine and Process names are required']);
    exit;
}

$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

try {
    // Fetch raw records
    $stmt = $pdo->prepare("SELECT uptime, task, sec, mouse, keyboard FROM activity_records WHERE machine = ? AND process = ? AND uptime >= ? AND uptime <= ? ORDER BY uptime DESC");
    $stmt->execute([$machine, $process, $startDateTime, $endDateTime]);
    $rows = $stmt->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $result[] = [
            'uptime' => $row['uptime'],
            'task' => $row['task'],
            'sec' => (int)$row['sec'],
            'mouse' => (int)$row['mouse'],
            'keyboard' => (int)$row['keyboard']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
