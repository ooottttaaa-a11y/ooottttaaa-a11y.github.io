<?php
// get_data.php - API to fetch activity data and server-calculated scores

require_once 'utils/ScoreCalculator.php';

// Database configuration
require_once 'db_connect.php';

// Parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Append times to make full datetime range
$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

try {
    // Optional machine filter
    $machine = isset($_GET['machine']) ? $_GET['machine'] : null;

    // Fetch data
    $sql = "SELECT * FROM activity_records WHERE uptime >= ? AND uptime <= ?";
    $params = [$startDateTime, $endDateTime];

    if ($machine) {
        $sql .= " AND machine = ?";
        $params[] = $machine;
    }

    $sql .= " ORDER BY uptime DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // 1. Build rawData (for charts/timeline)
    $rawData = [];
    foreach ($rows as $row) {
        $u = $row['uptime'];
        $t = $row['task'];
        $p = $row['process'];
        $s = (int)$row['sec'];
        $m = (int)$row['mouse'];
        $k = (int)$row['keyboard'];
        $sess = $row['session'];
        $rem = isset($row['isRemote']) ? (int)$row['isRemote'] : 0;
        
        $rawData[] = [
            'u' => $u,
            't' => $t,
            'p' => $p,
            's' => $s,
            'm' => $m,
            'k' => $k,
            'sw' => isset($row['switch_count']) ? (int)$row['switch_count'] : 0,
            'i' => isset($row['idle_seconds']) ? (int)$row['idle_seconds'] : 0,
            'sess' => $sess,
            'rem' => $rem
        ];
    }

    // 2. Calculate Scores using PHP logic (ported from C#)
    $blockScores = ScoreCalculator::calculate($rows);

    // 3. Transform blockScores to optimization format expected by frontend
    // blockScoreData: formatted per date
    /*
      Frontend expects:
      const blockScoreData = {
         '2025-12-16': [
             { t: '10:00-10:30', p: 80.5, f: 90.0, proc: 'Code', c: 'Doing great' },
             ...
         ]
      };
    */
    $blockScoreData = [];
    
    // Also calculate daily scores for dailyScoresData
    // dailyScoresData: { '2025-12-16': { p: 85.0, f: 88.0 } }
    $dailyScoreSums = [];

    foreach ($blockScores as $bs) {
        $dateKey = $bs['date'];
        
        // Prepare block entry
        $entry = [
            't' => $bs['time_range'],
            'p' => $bs['p'],
            'f' => $bs['f'],
            'proc' => $bs['proc'],
            'c' => $bs['c']
        ];

        if (!isset($blockScoreData[$dateKey])) $blockScoreData[$dateKey] = [];
        $blockScoreData[$dateKey][] = $entry;

        // Daily aggregation
        if (!isset($dailyScoreSums[$dateKey])) {
            $dailyScoreSums[$dateKey] = ['pSpl' => 0, 'fSum' => 0, 'count' => 0];
        }
        $dailyScoreSums[$dateKey]['pSum'] = isset($dailyScoreSums[$dateKey]['pSum']) ? $dailyScoreSums[$dateKey]['pSum'] + $bs['p'] : $bs['p'];
        $dailyScoreSums[$dateKey]['fSum'] = isset($dailyScoreSums[$dateKey]['fSum']) ? $dailyScoreSums[$dateKey]['fSum'] + $bs['f'] : $bs['f'];
        $dailyScoreSums[$dateKey]['count']++;
    }

    $dailyScoresData = [];
    foreach ($dailyScoreSums as $date => $sum) {
        if ($sum['count'] > 0) {
            $dailyScoresData[$date] = [
                'p' => round($sum['pSum'] / $sum['count'], 1),
                'f' => round($sum['fSum'] / $sum['count'], 1)
            ];
        } else {
            $dailyScoresData[$date] = ['p' => 0, 'f' => 0];
        }
    }

    // Return JSON
    header('Content-Type: application/json');
    echo json_encode([
        'rawData' => $rawData,
        'blockScoreData' => (object)$blockScoreData, // Ensure object for empty arrays
        'dailyScoresData' => (object)$dailyScoresData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
