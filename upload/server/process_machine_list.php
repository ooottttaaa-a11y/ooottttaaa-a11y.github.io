<?php
require_once 'auth_session.php';
check_login();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>ActivityMonitor - プロセス詳細</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Segoe UI', Meiryo, sans-serif; background: #f5f5f5; }
        .header-area { background: #fff; padding: 20px; border-bottom: 2px solid #FF9800; margin-bottom: 20px; }
        .card { border: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="header-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 m-0">🛠️ プロセス詳細: <span id="processNameTitle"></span></h1>
            <form class="d-flex align-items-center gap-2" onsubmit="event.preventDefault(); loadData();">
                <input type="date" id="startDate" class="form-control" style="width: auto;">
                <span class="mx-2">～</span>
                <input type="date" id="endDate" class="form-control" style="width: auto;">
                <button type="submit" class="btn btn-primary text-nowrap">表示</button>
                <a href="process_analysis.php" class="btn btn-secondary text-nowrap">一覧に戻る</a>
            </form>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card p-4">
        <table id="machineTable" class="table table-striped table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>マシン名</th>
                    <th>合計時間</th>
                    <th>閲覧時間</th>
                    <th>編集時間</th>
                    <th>アクション</th>
                </tr>
            </thead>
            <tbody id="machineTableBody">
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
const process = urlParams.get('process');
const initialStart = urlParams.get('start_date') || new Date().toISOString().split('T')[0];
const initialEnd = urlParams.get('end_date') || new Date().toISOString().split('T')[0];

document.getElementById('processNameTitle').textContent = process || 'Unknown';
document.getElementById('startDate').value = initialStart;
document.getElementById('endDate').value = initialEnd;

$(document).ready(function() {
    if (!process) {
        alert('プロセス名が指定されていません。');
        return;
    }

    table = $('#machineTable').DataTable({
        language: {
             url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/ja.json"
        },
        order: [[1, 'desc']], // Total Time desc
        columns: [
            { data: 'machine' },
            { 
                data: 'total_time',
                render: function(data, type, row) {
                    if (type === 'display') return row.total_fmt;
                    return data;
                }
            },
            { 
                data: 'view_time',
                render: function(data, type, row) {
                    if (type === 'display') return row.view_fmt;
                    return data;
                }
            },
            { 
                data: 'edit_time',
                render: function(data, type, row) {
                    if (type === 'display') return row.edit_fmt;
                    return data;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    const start = $('#startDate').val();
                    const end = $('#endDate').val();
                    return `<a href="process_detail.php?machine=${encodeURIComponent(row.machine)}&process=${encodeURIComponent(process)}&start_date=${start}&end_date=${end}" class="btn btn-sm btn-outline-primary">詳細</a>`;
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
        url: `get_process_machine_list.php?process=${encodeURIComponent(process)}&start_date=${start}&end_date=${end}`,
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
