<?php
require_once 'db_connect.php';
require_once 'auth_session.php';
require_once 'utils/ScoreCalculator.php';

check_login();

header('Content-Type: application/json');

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$response = [
    'status' => 'success',
    'productivity_trend' => [],
    'attendance_trend' => [],
    'process_ranking' => [],
    'overtime_alerts' => []
];

try {
    // 1. Productivity & Focus Trend (Daily Average)
    $sql = "SELECT DATE(uptime) as date, 
            AVG(CASE WHEN mouse > 0 OR keyboard > 0 THEN 1 ELSE 0 END) * 100 as p_score,
            AVG(CASE WHEN (mouse > 0 OR keyboard > 0) AND (process NOT LIKE '%YouTube%' AND process NOT LIKE '%Facebook%' AND process NOT LIKE '%Twitter%') THEN 1 ELSE 0 END) * 100 as f_score
            FROM activity_records 
            WHERE uptime BETWEEN :start AND :end 
            GROUP BY DATE(uptime) 
            ORDER BY date";
    
    // Note: The above SQL is a simplified score calculation for trend approximation.
    // For accurate scores matching ScoreCalculator logic, we should fetch raw data and compute in PHP, 
    // but for a dashboard trendline, SQL aggregation is much faster. 
    // *HOWEVER*, to be consistent, let's use a simplified SQL approximation that mimics the logic:
    // Productivity = (Active Sec / Total Sec) * 100
    // Focus = (Productive Active Sec / Total Sec) * 100
    // We'll aggregate seconds.
    
    $trendSql = "
        SELECT 
            DATE(uptime) as date,
            SUM(sec) as total_sec,
            SUM(CASE WHEN mouse > 0 OR keyboard > 0 THEN sec ELSE 0 END) as active_sec,
            SUM(CASE WHEN (mouse > 0 OR keyboard > 0) AND process NOT IN ('YouTube', 'Facebook', 'Twitter', 'Netflix') THEN sec ELSE 0 END) as focus_sec
        FROM activity_records
        WHERE uptime BETWEEN :start AND :end
        GROUP BY DATE(uptime)
        ORDER BY date ASC
    ";
    
    $stmt = $pdo->prepare($trendSql);
    $stmt->execute([':start' => $start_date . ' 00:00:00', ':end' => $end_date . ' 23:59:59']);
    $trendData = $stmt->fetchAll();
    
    foreach ($trendData as $row) {
        $total = $row['total_sec'] > 0 ? $row['total_sec'] : 1;
        $response['productivity_trend'][] = [
            'date' => $row['date'],
            'p_score' => round(($row['active_sec'] / $total) * 100, 1),
            'f_score' => round(($row['focus_sec'] / $total) * 100, 1)
        ];
    }

    // 2. Attendance Trend (Total Working Hours per Day)
    // We need to sum up work hours (start to end) logic.
    // Simple approximation: Sum of 'sec' where session is active? 
    // Or better: Distinct machine days? 
    // Let's use the 'active_sec' from previous query as 'work hours' proxy, 
    // OR roughly: for each machine, min(uptime) and max(uptime).
    // Let's stick to 'Total Active Time' for the organization for simplicity, relative to previous days.
    // Actually, user asked for 'Attendance status'. Let's show avg work hours per employee.
    
    $attSql = "
        SELECT 
            DATE(uptime) as date,
            COUNT(DISTINCT machine) as user_count,
            SUM(sec) as total_sec
        FROM activity_records
        WHERE uptime BETWEEN :start AND :end
        AND (mouse > 0 OR keyboard > 0)
        GROUP BY DATE(uptime)
        ORDER BY date ASC
    ";
    $stmt = $pdo->prepare($attSql);
    $stmt->execute([':start' => $start_date . ' 00:00:00', ':end' => $end_date . ' 23:59:59']);
    $attData = $stmt->fetchAll();
    
    foreach ($attData as $row) {
        $avg_sec = $row['user_count'] > 0 ? $row['total_sec'] / $row['user_count'] : 0;
        $response['attendance_trend'][] = [
            'date' => $row['date'],
            'avg_hours' => round($avg_sec / 3600, 1),
            'total_hours' => round($row['total_sec'] / 3600, 1)
        ];
    }

    // 3. Process Usage (Top 10)
    $procSql = "
        SELECT process, SUM(sec) as total_sec
        FROM activity_records
        WHERE uptime BETWEEN :start AND :end
        GROUP BY process
        ORDER BY total_sec DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($procSql);
    $stmt->execute([':start' => $start_date . ' 00:00:00', ':end' => $end_date . ' 23:59:59']);
    $response['process_ranking'] = $stmt->fetchAll();

    // 4. Overtime Alerts (Machines > 10 hours work in any single day in the month, OR last 7 days? Let's do month)
    // We need to group by machine AND date.
    $overtimeSql = "
        SELECT machine, DATE(uptime) as date, SUM(sec) as work_sec
        FROM activity_records
        WHERE uptime BETWEEN :start AND :end
        AND (mouse > 0 OR keyboard > 0) 
        GROUP BY machine, DATE(uptime)
        HAVING work_sec > 36000 -- 10 hours
        ORDER BY date DESC, work_sec DESC
    ";
    $stmt = $pdo->prepare($overtimeSql);
    $stmt->execute([':start' => $start_date . ' 00:00:00', ':end' => $end_date . ' 23:59:59']);
    
    $overtimeParams = $stmt->fetchAll();
    foreach ($overtimeParams as $row) {
        $response['overtime_alerts'][] = [
            'date' => $row['date'],
            'machine' => $row['machine'],
            'work_hours' => round($row['work_sec'] / 3600, 1)
        ];
    }

} catch (PDOException $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
