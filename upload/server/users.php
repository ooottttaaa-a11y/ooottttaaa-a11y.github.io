<?php
require_once 'auth_session.php';
require_once 'db_connect.php';

// Ensure only admin can access
check_admin();

$error = '';
$success = '';

// Handle Add User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($password)) {
        $error = "ユーザ名とパスワードを入力してください。";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "このユーザ名は既に使用されています。";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
            if ($stmt->execute([':username' => $username, ':password' => $hashed_password, ':role' => $role])) {
                $success = "ユーザを追加しました。";
            } else {
                $error = "ユーザ追加に失敗しました。";
            }
        }
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Prevent deleting self (simple check)
    if ($id == $_SESSION['user_id']) {
        $error = "自分自身を削除することはできません。";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $success = "ユーザを削除しました。";
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
$current_user = current_user();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>ActivityMonitor - ユーザ管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Meiryo, sans-serif; background: #f5f5f5; }
        .header-area { background: #fff; padding: 20px; border-bottom: 2px solid #4CAF50; margin-bottom: 20px; }
        .card { border: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">ActivityMonitor</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">生産性・集中度スコア</a></li>
        <li class="nav-item"><a class="nav-link" href="attendance.php">勤怠</a></li>
        <li class="nav-item"><a class="nav-link" href="process_analysis.php">プロセス分析</a></li>
        <?php if ($current_user['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link active" href="users.php">ユーザ管理</a></li>
        <?php endif; ?>
      </ul>
      <span class="navbar-text text-light me-3">
        <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_user['username']); ?>
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">ログアウト</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <!-- Add User Form -->
        <div class="col-md-4">
            <div class="card p-3 mb-4">
                <h5>新規ユーザ追加</h5>
                <?php if ($error): ?>
                    <div class="alert alert-danger btn-sm p-2"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success btn-sm p-2"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="post" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2">
                        <label class="form-label">ユーザ名</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">パスワード</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">権限</label>
                        <select class="form-select" name="role">
                            <option value="user">一般ユーザ</option>
                            <option value="admin">管理者 (Admin)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">追加</button>
                </form>
            </div>
        </div>

        <!-- User List -->
        <div class="col-md-8">
            <div class="card p-3">
                <h5>登録済みユーザ</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ユーザ名</th>
                            <th>権限</th>
                            <th>作成日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">User</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $u['created_at']; ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('本当に削除しますか？');">削除</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
