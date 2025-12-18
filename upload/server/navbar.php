<?php
// navbar.php
// Assumes auth_session.php is already included by the parent page and session is started.
// If not, we could include it here, but best to do it at top of page to handle redirects early.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$user = current_user();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">ActivityMonitor</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">ダッシュボード</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'scores.php' || $current_page == 'daily_list.php' || $current_page == 'detail.php') ? 'active' : ''; ?>" href="scores.php">生産性・集中度スコア</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'attendance.php' || $current_page == 'attendance_daily.php') ? 'active' : ''; ?>" href="attendance.php">勤怠</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'process_analysis.php' || $current_page == 'process_machine_list.php' || $current_page == 'process_detail.php') ? 'active' : ''; ?>" href="process_analysis.php">プロセス分析</a>
        </li>
        <?php if ($user['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" href="users.php">ユーザ管理</a>
            </li>
        <?php endif; ?>
      </ul>
      <span class="navbar-text text-light me-3">
        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username']); ?>
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">ログアウト</a>
    </div>
  </div>
</nav>
