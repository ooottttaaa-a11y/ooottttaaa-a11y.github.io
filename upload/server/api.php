<?php
// api.php - Receive ActivityMonitor data and insert into MySQL

// Database configuration and connection
require_once 'db_connect.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// Check for duplicates based on machine and uptime
$stmt = $pdo->prepare("
    INSERT INTO activity_records (uptime, username, machine, task, process, sec, mouse, keyboard, session, memory, isRemote, switch_count, idle_seconds, lock_seconds) 
    SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? 
    FROM DUAL 
    WHERE NOT EXISTS (
        SELECT 1 FROM activity_records WHERE machine = ? AND uptime = ?
    )
");

$count = 0;
$errors = 0;

foreach ($data as $row) {
    try {
        $stmt->execute([
            $row['uptime'],
            $row['username'],
            $row['machine'],
            $row['task'],
            $row['process'],
            $row['sec'],
            $row['mouse'],
            $row['keyboard'],
            $row['session'],
            $row['memory'],
            $row['isRemote'],
            $row['switch_count'] ?? 0,
            $row['idle_seconds'] ?? 0,
            $row['lock_seconds'] ?? 0,
            // Parameters for WHERE NOT EXISTS check
            $row['machine'],
            $row['uptime']
        ]);
        
        if ($stmt->rowCount() > 0) {
            $count++;
        }
    } catch (Exception $e) {
        $errors++;
        // Continue inserting other records even if one fails
    }
}

echo json_encode(['status' => 'success', 'inserted' => $count, 'errors' => $errors]);
?>
