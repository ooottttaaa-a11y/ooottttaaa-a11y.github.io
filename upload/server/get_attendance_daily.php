<?php
// get_attendance_daily.php - API to fetch daily attendance stats

// Database configuration
require_once 'db_connect.php';

// Parameters
$machine = isset($_GET['machine']) ? $_GET['machine'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

if (empty($machine)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Machine name is required']);
    exit;
}

$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

try {
    // Fetch all data for the machine in range
    $stmt = $pdo->prepare("SELECT * FROM activity_records WHERE machine = ? AND uptime >= ? AND uptime <= ? ORDER BY uptime ASC");
    $stmt->execute([$machine, $startDateTime, $endDateTime]);
    $allRows = $stmt->fetchAll();

    // Group by Date
    $dateGroups = [];
    foreach ($allRows as $row) {
        $date = substr($row['uptime'], 0, 10);
        if (!isset($dateGroups[$date])) {
            $dateGroups[$date] = [];
        }
        $dateGroups[$date][] = $row;
    }

    $result = [];

    foreach ($dateGroups as $date => $rows) {
        $startTime = null;
        $endTime = null;
        
        $totalSec = 0;
        $totalIdle = 0;
        $totalLock = 0;

        foreach ($rows as $row) {
            $timestamp = $row['uptime'];
            if ($startTime === null || $timestamp < $startTime) $startTime = $timestamp;
            if ($endTime === null || $timestamp > $endTime) $endTime = $timestamp;

            $s = (int)$row['sec'];
            $i = isset($row['idle_seconds']) ? (int)$row['idle_seconds'] : 0;
            $l = isset($row['lock_seconds']) ? (int)$row['lock_seconds'] : 0;
            
            $totalSec += $s;
            $totalIdle += $i;
            $totalLock += $l;
        }
        
        // Active Work = Total Sec - Idle - Lock
        // Wait, definition of "Work Time" in attendance usually means "Duration between Start and End" minus "Break"?
        // Or simply "Sum of active log durations"? 
        // User asked for "Work Time", "Lock", "Idle". 
        // Let's align with previous "Work Time" (Active) = Sum(sec) - Sum(idle) - Sum(lock).
        
        $activeWork = $totalSec - $totalIdle - $totalLock;
        if ($activeWork < 0) $activeWork = 0;

        // Start/End Time formatting (Time only)
        $startStr = $startTime ? date('H:i', strtotime($startTime)) : '-';
        $endStr = $endTime ? date('H:i', strtotime($endTime)) : '-';

        $result[] = [
            'date' => $date,
            'machine' => $machine,
            'start_time' => $startStr,
            'end_time' => $endStr,
            'work_seconds' => $activeWork,
            'work_formatted' => gmdate("H:i", $activeWork),
            'lock_seconds' => $totalLock,
            'lock_formatted' => gmdate("H:i", $totalLock),
            'idle_seconds' => $totalIdle,
            'idle_formatted' => gmdate("H:i", $totalIdle)
        ];
    }
    
    // Sort by date desc
    usort($result, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
