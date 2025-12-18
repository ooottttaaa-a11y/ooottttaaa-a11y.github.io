<?php
require_once 'auth_session.php';
check_login();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>ActivityMonitor - マシン一覧</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Segoe UI', Meiryo, sans-serif; background: #f5f5f5; }
        .header-area { background: #fff; padding: 20px; border-bottom: 2px solid #4CAF50; margin-bottom: 20px; }
        .card { border: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="header-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 m-0">📊 マシン一覧</h1>
            <form class="d-flex align-items-center gap-2" onsubmit="event.preventDefault(); loadData();">
                <div class="d-flex align-items-center">
                    <input type="date" id="startDate" class="form-control" style="width: auto;" value="<?php echo date('Y-m-d'); ?>">
                    <span class="mx-2">～</span>
                    <input type="date" id="endDate" class="form-control" style="width: auto;" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn btn-primary text-nowrap">表示</button>
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
                    <th>平均生産性</th>
                    <th>最大生産性</th>
                    <th>平均集中度</th>
                    <th>最大集中度</th>
                    <th>総作業時間 (操作)</th>
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

$(document).ready(function() {
    table = $('#machineTable').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/ja.json"
        },
        columns: [
            { data: 'machine' },
            { 
                data: 'avg_productivity',
                render: function(data, type, row) {
                    let color = data >= 60 ? '#4CAF50' : (data >= 40 ? '#FF9800' : '#F44336');
                    return `<span style="color:${color}; font-weight:bold;">${data}</span>`;
                }
            },
            { 
                data: 'max_productivity',
                render: function(data, type, row) {
                    let color = data >= 60 ? '#4CAF50' : (data >= 40 ? '#FF9800' : '#F44336');
                    return `<span style="color:${color}; font-weight:bold;">${data}</span>`;
                }
            },
            { 
                data: 'avg_focus',
                render: function(data, type, row) {
                    let color = data >= 60 ? '#4CAF50' : (data >= 40 ? '#FF9800' : '#F44336');
                    return `<span style="color:${color}; font-weight:bold;">${data}</span>`;
                }
            },
            { 
                data: 'max_focus',
                render: function(data, type, row) {
                    let color = data >= 60 ? '#4CAF50' : (data >= 40 ? '#FF9800' : '#F44336');
                    return `<span style="color:${color}; font-weight:bold;">${data}</span>`;
                }
            },
            { 
                data: 'total_work_seconds',
                render: function(data, type, row) {
                    // Convert seconds to H:mm format for sorting? 
                    // Or display formatted string. 
                    if (type === 'display') {
                        let h = (data / 3600).toFixed(1);
                        return h + 'h (' + row.total_work_formatted + ')';
                    }
                    return data; // use seconds for sorting
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    const start = $('#startDate').val();
                    const end = $('#endDate').val();
                    return `<a href="daily_list.php?machine=${encodeURIComponent(row.machine)}&start_date=${start}&end_date=${end}" class="btn btn-sm btn-outline-primary">詳細</a>`;
                }
            }
        ]
    });

    loadData();
});

function loadData() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    
    // Show loading? DataTable has its own processing indicator if configured, 
    // but here we are fetching JSON manually to populate.
    
    $.ajax({
        url: `get_machine_list.php?start_date=${start}&end_date=${end}`,
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
