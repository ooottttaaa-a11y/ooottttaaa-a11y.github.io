<?php
// get_daily_list.php - API to fetch aggregated stats per day for a specific machine

require_once 'utils/ScoreCalculator.php';

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
    $stmt = $pdo->prepare("SELECT * FROM activity_records WHERE machine = ? AND uptime >= ? AND uptime <= ?");
    $stmt->execute([$machine, $startDateTime, $endDateTime]);
    $allRows = $stmt->fetchAll();

    // Group by Date
    $dateGroups = [];
    foreach ($allRows as $row) {
        // uptime format: YYYY-MM-DD HH:MM:SS
        $date = substr($row['uptime'], 0, 10);
        if (!isset($dateGroups[$date])) {
            $dateGroups[$date] = [];
        }
        $dateGroups[$date][] = $row;
    }

    $result = [];

    foreach ($dateGroups as $date => $rows) {
        // Calculate Total Active Time
        $totalActiveSec = 0;
        foreach ($rows as $row) {
             $sec = (int)$row['sec'];
             $idle = isset($row['idle_seconds']) ? (int)$row['idle_seconds'] : 0;
             $lock = isset($row['lock_seconds']) ? (int)$row['lock_seconds'] : 0;
             $active = $sec - $idle - $lock;
             if ($active < 0) $active = 0;
             $totalActiveSec += $active;
        }

        // Calculate Scores using shared logic
        $blockScores = ScoreCalculator::calculate($rows);
        
        $totalP = 0;
        $totalF = 0;
        $maxP = 0;
        $maxF = 0;
        $count = count($blockScores);

        if ($count > 0) {
            foreach ($blockScores as $bs) {
                $totalP += $bs['p'];
                $totalF += $bs['f'];
                if ($bs['p'] > $maxP) $maxP = $bs['p'];
                if ($bs['f'] > $maxF) $maxF = $bs['f'];
            }
            $avgP = round($totalP / $count, 1);
            $avgF = round($totalF / $count, 1);
        } else {
            $avgP = 0;
            $avgF = 0;
        }

        $result[] = [
            'date' => $date,
            'machine' => $machine,
            'avg_productivity' => $avgP,
            'avg_focus' => $avgF,
            'max_productivity' => $maxP,
            'max_focus' => $maxF,
            'total_work_seconds' => $totalActiveSec,
            'total_work_formatted' => gmdate("H:i:s", $totalActiveSec)
        ];
    }
    
    // Sort logic handled by frontend, but default by date desc
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
