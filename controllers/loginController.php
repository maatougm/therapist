<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: ../admin_dashboard.php');
        } else if ($user['role'] === 'therapist') {
            header('Location: ../kine_dashboard.php');
        } else {
            header('Location: ../user_dashboard.php');
        }
        exit();
    } else {
        header('Location: ../login.php?error=Invalid credentials');
        exit();
    }
}
?>