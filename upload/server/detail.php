<?php
require_once 'auth_session.php';
check_login();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>ActivityMonitor - データ閲覧</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Meiryo, sans-serif; background: #f5f5f5; }
        .header-area { background: #fff; padding: 20px; border-bottom: 2px solid #4CAF50; margin-bottom: 20px; }
        .info { background: #e3f2fd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .score-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; flex: 1; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .score-card.focus { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); color: #333; }
        .score-title { font-size: 16px; margin-bottom: 10px; opacity: 0.9; }
        .score-value { font-size: 42px; font-weight: bold; }
        .chart-container { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-stat { font-size: 18px; font-weight: normal; color: #555; }
        .header-stat span { font-weight: bold; color: #4CAF50; font-size: 24px; margin-left: 5px; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="header-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 m-0">📊 詳細ビュー</h1>
            <form class="d-flex align-items-center gap-2" onsubmit="event.preventDefault(); loadData();">
                <input type="date" id="targetDate" class="form-control" style="width: auto;" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d'); ?>">
                <button type="submit" class="btn btn-primary text-nowrap">表示</button>
                <?php 
                    $machine = isset($_GET['machine']) ? htmlspecialchars($_GET['machine']) : '';
                    $start = isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-d');
                    // Back logic: use the currently viewed date as the range for daily list
                ?>
                <a href="daily_list.php?machine=<?php echo $machine; ?>&start_date=<?php echo $start; ?>&end_date=<?php echo $start; ?>" class="btn btn-secondary ms-2 text-nowrap">戻る</a>
            </form>
        </div>
        <h2 class="h5 text-muted mt-2">Machine: <?php echo isset($_GET['machine']) ? htmlspecialchars($_GET['machine']) : 'All'; ?></h2>
    </div>
</div>


<div class="container-fluid">
    
    <div id="loading" class="text-center my-5" style="display:none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Loading data...</p>
    </div>

    <!-- Content Area (Hidden until loaded) -->
    <div id="contentArea" style="display:none;">

        <!-- スコアカードエリア -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="score-card">
                    <div class="score-title">⚡ 生産性スコア (Productivity)</div>
                    <div class="score-value" id="scoreProd">-</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="score-card focus">
                    <div class="score-title">🔥 集中度スコア (Focus)</div>
                    <div class="score-value" id="scoreFocus">-</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 24時間タイムライン -->
            <div class="col-lg-8 mb-4">
                <div class="chart-container h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 m-0">🕒 24時間アクティビティ (1分単位)</h2>
                        <div class="header-stat">総作業時間: <span id="totalTimeDisplay">-</span></div>
                    </div>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="activityChart"></canvas>
                    </div>
                    <div class="text-center mt-2 small text-muted">
                        <span>🟢 稼働 (Active)</span> <span class="mx-2">|</span> 
                        <span>🟡 リモート (Remote)</span> <span class="mx-2">|</span> 
                        <span>🔘 ロック/アイドル (Locked)</span>
                    </div>
                </div>
            </div>

            <!-- アプリ別円グラフ -->
            <div class="col-lg-4 mb-4">
                <div class="chart-container h-100">
                    <h2 class="h5 mb-3">📊 アプリ別使用比率</h2>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="processChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 30分詳細テーブル -->
        <div class="chart-container mb-4">
            <h3 class="h5 mb-3 border-bottom pb-2">⏳ 30分単位スコア詳細</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>日付</th>
                            <th>時間帯</th>
                            <th>生産性</th>
                            <th>集中度</th>
                            <th>主プロセス</th>
                            <th>状態分析</th>
                        </tr>
                    </thead>
                    <tbody id="blockScoreTableBody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 生データテーブル -->
        <div class="chart-container">
            <h3 class="h5 mb-3 border-bottom pb-2">📋 アクティビティログ詳細</h3>
            <div class="table-responsive">
                <table id="rawDataTable" class="table table-sm table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>日時</th>
                            <th>ウィンドウタイトル</th>
                            <th>プロセス名</th>
                            <th>秒数</th>
                            <th>マウス</th>
                            <th>キー</th>
                            <th>切り替え</th>
                            <th>アイドル</th>
                            <th>セッション</th>
                        </tr>
                    </thead>
                    <tbody id="rawDataTableBody">
                    </tbody>
                </table>
            </div>
        </div>

    </div> <!-- End Content Area -->
</div>

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<!-- JS Logic -->
<script>
let myChart = null;
let pieChart = null;
let rawData = [];
let blockScoreData = {};
let dailyScoresData = {};
let rawDataTable = null;

window.onload = function() {
    loadData();
};

async function loadData() {
    const targetDate = document.getElementById('targetDate').value;
    
    document.getElementById('loading').style.display = 'block';
    document.getElementById('contentArea').style.display = 'none';

    const urlParams = new URLSearchParams(window.location.search);
    const machine = urlParams.get('machine');
    
    // For detail view, start = end = targetDate
    let url = `get_data.php?start_date=${targetDate}&end_date=${targetDate}`;
    if (machine) {
        url += `&machine=${encodeURIComponent(machine)}`;
    }

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.status === 'error') {
            alert('Error: ' + data.message);
            return;
        }

        rawData = data.rawData;
        blockScoreData = data.blockScoreData;
        dailyScoresData = data.dailyScoresData;

        renderAll();

        document.getElementById('contentArea').style.display = 'block';
    } catch (e) {
        alert('Failed to load data: ' + e);
        console.error(e);
    } finally {
        document.getElementById('loading').style.display = 'none';
    }
}

function renderAll() {
    // 1. スコア表示
    let totalP = 0; let totalF = 0; let count = 0;
    const tbody = document.getElementById('blockScoreTableBody');
    tbody.innerHTML = '';

    Object.keys(blockScoreData).sort().forEach(dateKey => {
         blockScoreData[dateKey].forEach(b => {
             totalP += b.p; totalF += b.f; count++;
             
             let pColor = b.p >= 60 ? '#4CAF50' : (b.p >= 40 ? '#FF9800' : '#F44336');
             let fColor = b.f >= 60 ? '#4CAF50' : (b.f >= 40 ? '#FF9800' : '#F44336');
             
             const row = `<tr>
                <td>${dateKey}</td>
                <td>${b.t}</td>
                <td style="color:${pColor}; font-weight:bold;">${b.p.toFixed(1)}</td>
                <td style="color:${fColor}; font-weight:bold;">${b.f.toFixed(1)}</td>
                <td>${b.proc}</td>
                <td class="small text-muted">${b.c}</td>
             </tr>`;
             tbody.innerHTML += row;
         });
    });

    if (count > 0) {
        document.getElementById('scoreProd').textContent = (totalP / count).toFixed(1);
        document.getElementById('scoreFocus').textContent = (totalF / count).toFixed(1);
    } else {
        document.getElementById('scoreProd').textContent = '-';
        document.getElementById('scoreFocus').textContent = '-';
    }

    updateTotalTime();
    updateTimelineChart();
    updateProcessChart();
    updateRawDataTable();
}

function updateRawDataTable() {
    if ($.fn.DataTable.isDataTable('#rawDataTable')) {
        $('#rawDataTable').DataTable().destroy();
    }

    // rawData: {u, t, p, s, m, k, sess, rem}
    // u: uptime, t: task, p: process, s: sec, m: mouse, k: keyboard, sess: session
    
    $('#rawDataTable').DataTable({
        data: rawData,
        language: {
             url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/ja.json"
        },
        pageLength: -1,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "すべて"]],
        order: [[0, 'desc']], 
        columns: [
            { data: 'u' }, // 日時
            { data: 't', width: '50%' }, // ウィンドウタイトル
            { data: 'p' }, // プロセス名
            { data: 's' }, // 秒数
            { data: 'm' }, // マウス
            { data: 'k' }, // キー
            { data: 'sw' }, // 切り替え
            { data: 'i' }, // アイドル
            { data: 'sess' } // セッション
        ],
        deferRender: true
    });
}

function updateTotalTime() {
  let totalSeconds = 0;
  rawData.forEach(d => {
      totalSeconds += d.s; 
  });
  document.getElementById('totalTimeDisplay').textContent = (totalSeconds / 3600).toFixed(1) + 'h';
}

function updateTimelineChart() {
  // Same logic as C# HTML generator to fill 1440 slots
  // If multiple days are loaded, this chart might look weird if we overlay them.
  // The C# logic assumes 'one day' typically, or overlays them.
  // We will assume 'overlay/rewrite' strategy (later data overwrites earlier for same time slot), 
  // essentially showing the "latest day's activity" for each time slot, or accumulation?
  // Let's stick to C# logic: iterate all data and fill slots.
  
  const minuteStatus = new Array(1440).fill(0);
  const minuteLabels = new Array(1440).fill('');

  rawData.forEach(d => {
    // d: {u, t, p, s, m, k, sess, rem}
    const uptimeStr = d.u; // "yyyy-MM-dd HH:mm:ss"
    const durationSec = d.s;
    const session = d.sess;
    const isRemote = (d.rem == 1);
    
    let status = 1;
    if (isRemote) status = 2;
    if (session.includes('Lock') || session.includes('Logoff')) status = 3;
    
    const timePart = uptimeStr.split(' ')[1]; // HH:mm:ss
    const parts = timePart.split(':');
    const startHour = parseInt(parts[0]);
    const startMin = parseInt(parts[1]);
    const startMinuteIndex = startHour * 60 + startMin;
    
    const durationMin = Math.ceil(durationSec / 60);
    
    for (let i = 0; i < durationMin; i++) {
        let idx = startMinuteIndex + i;
        if (idx < 1440) {
            // Overwrite strategy
            minuteStatus[idx] = status;
            minuteLabels[idx] = timePart + ' (' + d.p + ')';
        }
    }
  });

  const bgColors = minuteStatus.map(s => {
    if (s === 1) return '#4CAF50';
    if (s === 2) return '#FFD700';
    if (s === 3) return '#9E9E9E';
    return '#EEEEEE';
  });
  
  const dataValues = minuteStatus.map(s => 1);
  const labels = Array.from({length: 1440}, (_, i) => {
    const h = Math.floor(i / 60);
    const m = i % 60;
    return (m === 0) ? h + ':00' : '';
  });

  const ctx = document.getElementById('activityChart').getContext('2d');
  if (myChart) { myChart.destroy(); }
  
  myChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        data: dataValues,
        backgroundColor: bgColors,
        barPercentage: 1.0,
        categoryPercentage: 1.0,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(context) {
                const idx = context.dataIndex;
                const h = Math.floor(idx / 60);
                const m = ('0' + (idx % 60)).slice(-2);
                const statusMap = ['Inactive', 'Active', 'Remote', 'Locked'];
                const s = minuteStatus[idx];
                let info = minuteLabels[idx] || '';
                if(info.length > 50) info = info.substring(0,50)+'...';
                return h + ':' + m + ' ' + statusMap[s] + ' ' + info;
            }
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 0, callback: function(val, index) { return (index % 60 === 0) ? index/60 + ':00' : null; } } },
        y: { display: false, min: 0, max: 1 }
      }
    }
  });
}

function updateProcessChart() {
  const processes = {};
  rawData.forEach(d => {
    const process = d.p;
    const seconds = d.s;
    if(processes[process]) { processes[process] += seconds; } else { processes[process] = seconds; }
  });
  
  const sorted = Object.entries(processes).sort((a,b) => b[1] - a[1]).slice(0, 10);
  
  const ctx = document.getElementById('processChart').getContext('2d');
  if (pieChart) { pieChart.destroy(); }
  
  pieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: sorted.map(d => d[0]),
      datasets: [{
        data: sorted.map(d => d[1]/60),
        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#76A346', '#0E7569', '#AB4E6B']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right' },
        tooltip: { callbacks: { label: function(c) { return c.label + ': ' + c.raw.toFixed(1) + ' 分'; } } }
      }
    }
  });
}
</script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
</body>
</html>
