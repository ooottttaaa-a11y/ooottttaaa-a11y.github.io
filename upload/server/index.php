<?php
require_once 'auth_session.php';
check_login();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>ActivityMonitor - ダッシュボード</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Meiryo, sans-serif; background: #f5f5f5; }
        .header-area { background: #fff; padding: 20px; border-bottom: 2px solid #673AB7; margin-bottom: 20px; }
        .card { border: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .chart-container { position: relative; height: 300px; width: 100%; }
        .stat-card { padding: 20px; border-radius: 10px; color: white; margin-bottom: 20px; }
        .stat-card.prod { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.attend { background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%); }
        .stat-card.alert { background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%); }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="header-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 m-0">📊 ダッシュボード</h1>
            <input type="month" id="targetMonth" class="form-control" style="width: auto;" value="<?php echo date('Y-m'); ?>" onchange="loadDashboard()">
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Monthly Productivity Trend -->
        <div class="col-lg-8">
            <div class="card p-3">
                <h5 class="card-title mb-3">📈 月間スコア推移 (生産性・集中度)</h5>
                <div class="chart-container">
                    <canvas id="prodChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Monthly Process Usage -->
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h5 class="card-title mb-3">🥧 アプリ使用割合 (Top 10)</h5>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="processChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Monthly Attendance Trend -->
        <div class="col-lg-6">
            <div class="card p-3">
                <h5 class="card-title mb-3">🕒 平均勤務時間推移</h5>
                <div class="chart-container">
                    <canvas id="attendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Overtime Alerts -->
        <div class="col-lg-6">
            <div class="card p-3">
                <h5 class="card-title mb-3 text-danger">⚠️ 長時間勤務アラート (>10h)</h5>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>日付</th>
                                <th>マシン/ユーザ</th>
                                <th>勤務時間</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody id="overtimeList">
                            <!-- JS loaded -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let prodChartInst = null;
let attendChartInst = null;
let processChartInst = null;

$(document).ready(function() {
    loadDashboard();
});

function loadDashboard() {
    const month = $('#targetMonth').val();
    
    $.ajax({
        url: `get_dashboard_data.php?month=${month}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            renderProductivityChart(response.productivity_trend);
            renderAttendanceChart(response.attendance_trend);
            renderProcessChart(response.process_ranking);
            renderOvertimeList(response.overtime_alerts);
        },
        error: function(err) {
            console.error(err);
            alert('データ読み込みエラー');
        }
    });
}

function renderProductivityChart(data) {
    const ctx = document.getElementById('prodChart').getContext('2d');
    if (prodChartInst) prodChartInst.destroy();

    prodChartInst = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.date.slice(-2)), // Show only day part 01, 02...
            datasets: [
                {
                    label: '生産性',
                    data: data.map(d => d.p_score),
                    borderColor: '#4CAF50',
                    tension: 0.1,
                    fill: false
                },
                {
                    label: '集中度',
                    data: data.map(d => d.f_score),
                    borderColor: '#FF9800',
                    tension: 0.1,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { min: 0, max: 100 }
            }
        }
    });
}

function renderAttendanceChart(data) {
    const ctx = document.getElementById('attendChart').getContext('2d');
    if (attendChartInst) attendChartInst.destroy();

    attendChartInst = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.date.slice(-2)),
            datasets: [{
                label: '平均勤務時間 (h)',
                data: data.map(d => d.avg_hours),
                backgroundColor: '#2196F3'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function renderProcessChart(data) {
    const ctx = document.getElementById('processChart').getContext('2d');
    if (processChartInst) processChartInst.destroy();

    processChartInst = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.process),
            datasets: [{
                data: data.map(d => (d.total_sec / 3600).toFixed(1)), // Hours
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 10 } }
            }
        }
    });
}

function renderOvertimeList(data) {
    const tbody = $('#overtimeList');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.append('<tr><td colspan="4" class="text-center text-muted">該当者なし</td></tr>');
        return;
    }
    
    data.forEach(row => {
        const html = `
            <tr>
                <td>${row.date}</td>
                <td><span class="fw-bold">${row.machine}</span></td>
                <td class="text-danger fw-bold">${row.work_hours}h</td>
                <td>
                    <a href="detail.php?machine=${encodeURIComponent(row.machine)}&start_date=${row.date}" class="btn btn-sm btn-outline-secondary">詳細</a>
                </td>
            </tr>
        `;
        tbody.append(html);
    });
}
</script>

</body>
</html>
