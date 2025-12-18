<?php
require_once 'db_connect.php';

try {
    // 1. Create Table
    $sql = file_get_contents('create_users_table.sql');
    $pdo->exec($sql);
    echo "Table 'users' created or already exists.<br>";

    // 2. Check if admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // 3. Create default admin
        $password = 'admin123';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $insert->execute([
            ':username' => 'admin',
            ':password' => $hashed_password,
            ':role' => 'admin'
        ]);
        echo "Default admin user created (admin / admin123).<br>";
    } else {
        echo "Admin user already exists.<br>";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
