<?php
// get_attendance_list.php - API to fetch aggregated attendance stats per machine

// Database configuration
require_once 'db_connect.php';

// Parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

try {
    // Fetch all data in range
    $stmt = $pdo->prepare("SELECT * FROM activity_records WHERE uptime >= ? AND uptime <= ? ORDER BY uptime ASC");
    $stmt->execute([$startDateTime, $endDateTime]);
    $allRows = $stmt->fetchAll();

    // Group by Machine -> Date
    $machineDateGroups = [];
    foreach ($allRows as $row) {
        $m = $row['machine'];
        $d = substr($row['uptime'], 0, 10);
        if (!isset($machineDateGroups[$m])) {
            $machineDateGroups[$m] = [];
        }
        if (!isset($machineDateGroups[$m][$d])) {
            $machineDateGroups[$m][$d] = [];
        }
        $machineDateGroups[$m][$d][] = $row;
    }

    $result = [];

    foreach ($machineDateGroups as $machine => $dates) {
        $totalWork = 0;
        $totalLock = 0;
        $totalIdle = 0; // "Idle" in spec usually means "At Dollar"? No, probably "At Idle".
        // User said "あとドル時間" which likely means "アイドル時間" (Idle time).
        
        $startSum = 0;
        $endSum = 0;
        $dayCount = 0;

        foreach ($dates as $date => $rows) {
            $startTime = null;
            $endTime = null;
            $dailySec = 0;
            $dailyIdle = 0;
            $dailyLock = 0;

            foreach ($rows as $row) {
                $ts = strtotime($row['uptime']); // unix exp
                if ($startTime === null || $ts < $startTime) $startTime = $ts;
                if ($endTime === null || $ts > $endTime) $endTime = $ts;

                $dailySec += (int)$row['sec'];
                $dailyIdle += isset($row['idle_seconds']) ? (int)$row['idle_seconds'] : 0;
                $dailyLock += isset($row['lock_seconds']) ? (int)$row['lock_seconds'] : 0;
            }

            // Active work for the day
            $dailyWork = $dailySec - $dailyIdle - $dailyLock;
            if ($dailyWork < 0) $dailyWork = 0;

            $totalWork += $dailyWork;
            $totalLock += $dailyLock;
            $totalIdle += $dailyIdle;

            // For avg start/end, we convert time of day to seconds from midnight
            if ($startTime !== null && $endTime !== null) {
                $startSum += ($startTime % 86400); // seconds from midnight UTC... wait, simple modulus might fail with timezone/dates.
                // Better: get H:i:s and parse to seconds
                $startSum += (date('H', $startTime) * 3600 + date('i', $startTime) * 60 + date('s', $startTime));
                $endSum += (date('H', $endTime) * 3600 + date('i', $endTime) * 60 + date('s', $endTime));
                $dayCount++;
            }
        }

        $avgStartStr = '-';
        $avgEndStr = '-';

        if ($dayCount > 0) {
            $avgStartSec = $startSum / $dayCount;
            $avgEndSec = $endSum / $dayCount;
            $avgStartStr = gmdate('H:i', $avgStartSec);
            $avgEndStr = gmdate('H:i', $avgEndSec);
        }

        $result[] = [
            'machine' => $machine,
            'avg_start' => $avgStartStr,
            'avg_end' => $avgEndStr,
            'total_work' => $totalWork,
            'total_work_fmt' => floor($totalWork / 3600) . gmdate(':i', $totalWork % 3600), // Handle > 24h
            'total_lock' => $totalLock,
            'total_lock_fmt' => floor($totalLock / 3600) . gmdate(':i', $totalLock % 3600),
            'total_idle' => $totalIdle,
            'total_idle_fmt' => floor($totalIdle / 3600) . gmdate(':i', $totalIdle % 3600)
        ];
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
