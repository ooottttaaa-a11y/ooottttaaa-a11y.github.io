<?php
// get_process_list.php - API to fetch aggregated process stats

// Database configuration
require_once 'db_connect.php';

// Parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

try {
    // Fetch all data in range
    // We only need process, sec, mouse, keyboard
    $stmt = $pdo->prepare("SELECT process, sec, mouse, keyboard FROM activity_records WHERE uptime >= ? AND uptime <= ?");
    $stmt->execute([$startDateTime, $endDateTime]);
    $rows = $stmt->fetchAll();

    $processStats = [];

    foreach ($rows as $row) {
        $proc = $row['process'];
        $sec = (int)$row['sec'];
        $mouse = (int)$row['mouse'];
        $keyboard = (int)$row['keyboard'];

        if (!isset($processStats[$proc])) {
            $processStats[$proc] = [
                'total' => 0,
                'view' => 0,
                'edit' => 0
            ];
        }

        $processStats[$proc]['total'] += $sec;

        // Edit Time: Has keyboard input
        if ($keyboard > 0) {
            $processStats[$proc]['edit'] += $sec;
        }
        // View Time: Mouse only (no keyboard)
        // Note: Strict interpretation "mouse > 0 and keyboard == 0"
        elseif ($mouse > 0) {
            $processStats[$proc]['view'] += $sec;
        }
    }

    $result = [];
    foreach ($processStats as $proc => $stats) {
        $result[] = [
            'process' => $proc,
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
