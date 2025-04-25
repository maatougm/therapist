<?php
require '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user;
        switch ($user['role']) {
            case 'admin': header('Location: ../admin_dashboard.php'); break;
            case 'kine': header('Location: ../kine_dashboard.php'); break;
            default: header('Location: ../user_dashboard.php'); break;
        }
    } else {
        header('Location: ../login.php?error=Invalid credentials');
    }
}
