<?php
session_start();

// Utility to check if user is logged in
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Utility to check if user is admin
function check_admin() {
    check_login();
    if ($_SESSION['role'] !== 'admin') {
        echo "Access Denied. Admin only.";
        exit();
    }
}

// Get current user info safely
function current_user() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? 'Guest',
        'role' => $_SESSION['role'] ?? 'guest'
    ];
}
?>
