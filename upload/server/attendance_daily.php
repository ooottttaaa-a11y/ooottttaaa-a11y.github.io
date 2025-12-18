<?php
require_once 'auth_session.php';
check_login();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>ActivityMonitor - 勤怠 - 日次詳細</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Segoe UI', Meiryo, sans-serif; background: #f5f5f5; }
        .header-area { background: #fff; padding: 20px; border-bottom: 2px solid #2196F3; margin-bottom: 20px; }
        .card { border: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="header-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 m-0">🕒 勤怠 - 日次詳細: <span id="machineNameTitle"></span></h1>
            <form class="d-flex align-items-center gap-2" onsubmit="event.preventDefault(); loadData();">
                <input type="date" id="startDate" class="form-control" style="width: auto;">
                <span class="mx-2">～</span>
                <input type="date" id="endDate" class="form-control" style="width: auto;">
                <button type="submit" class="btn btn-primary text-nowrap">表示</button>
                <a href="attendance.php" class="btn btn-secondary text-nowrap">一覧に戻る</a>
            </form>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card p-4">
        <table id="dailyTable" class="table table-striped table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>始業時間</th>
                    <th>就業時間</th>
                    <th>勤務時間</th>
                    <th>ロック時間</th>
                    <th>アイドル時間</th>
                    <th>アクション</th>
                </tr>
            </thead>
            <tbody id="dailyTableBody">
                <!-- Rows loaded via JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
let table = null;
const urlParams = new URLSearchParams(window.location.search);
const machine = urlParams.get('machine');
const initialStart = urlParams.get('start_date') || new Date().toISOString().split('T')[0];
const initialEnd = urlParams.get('end_date') || new Date().toISOString().split('T')[0];

document.getElementById('machineNameTitle').textContent = machine || 'Unknown';
document.getElementById('startDate').value = initialStart;
document.getElementById('endDate').value = initialEnd;

$(document).ready(function() {
    if (!machine) {
        alert('マシン名が指定されていません。');
        return;
    }

    table = $('#dailyTable').DataTable({
        language: {
             url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/ja.json"
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'date' },
            { data: 'start_time' },
            { data: 'end_time' },
            { data: 'work_formatted' },
            { data: 'lock_formatted' },
            { data: 'idle_formatted' },
            {
                data: null,
                render: function(data, type, row) {
                    return `<a href="detail.php?machine=${encodeURIComponent(machine)}&start_date=${row.date}" class="btn btn-sm btn-outline-primary">詳細</a>`;
                }
            }
        ]
    });
    loadData();
});

function loadData() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    
    $.ajax({
        url: `get_attendance_daily.php?machine=${encodeURIComponent(machine)}&start_date=${start}&end_date=${end}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                table.clear();
                table.rows.add(response.data);
                table.draw();
            } else {
                alert('データ取得エラー: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('通信エラー: ' + error);
        }
    });
}
</script>

</body>
</html>
