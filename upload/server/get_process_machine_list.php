<?php
// get_process_machine_list.php - API to fetch machine stats for a specific process

// Database configuration
require_once 'db_connect.php';

// Parameters
$process = isset($_GET['process']) ? $_GET['process'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

if (empty($process)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Process name is required']);
    exit;
}

$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

try {
    // Fetch data for specific process
    $stmt = $pdo->prepare("SELECT machine, sec, mouse, keyboard FROM activity_records WHERE process = ? AND uptime >= ? AND uptime <= ?");
    $stmt->execute([$process, $startDateTime, $endDateTime]);
    $rows = $stmt->fetchAll();

    $machineStats = [];

    foreach ($rows as $row) {
        $mach = $row['machine'];
        $sec = (int)$row['sec'];
        $mouse = (int)$row['mouse'];
        $keyboard = (int)$row['keyboard'];

        if (!isset($machineStats[$mach])) {
            $machineStats[$mach] = [
                'total' => 0,
                'view' => 0,
                'edit' => 0
            ];
        }

        $machineStats[$mach]['total'] += $sec;

        if ($keyboard > 0) {
            $machineStats[$mach]['edit'] += $sec;
        } elseif ($mouse > 0) {
            $machineStats[$mach]['view'] += $sec;
        }
    }

    $result = [];
    foreach ($machineStats as $mach => $stats) {
        $result[] = [
            'machine' => $mach,
            'total_time' => $stats['total'],
            'total_fmt' => floor($stats['total'] / 3600) . gmdate(':i', $stats['total'] % 3600),
            'view_time' => $stats['view'],
            'view_fmt' => floor($stats['view'] / 3600) . gmdate(':i', $stats['view'] % 3600),
            'edit_time' => $stats['edit'],
            'edit_fmt' => floor($stats['edit'] / 3600) . gmdate(':i', $stats['edit'] % 3600)
        ];
    }

    // Sort by Total Time desc
    usort($result, function($a, $b) {
        return $b['total_time'] - $a['total_time'];
    });

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
