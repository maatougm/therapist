<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        header('Location: ../user_dashboard.php');
        exit();
    } else {
        header('Location: ../login.php?error=Invalid credentials');
        exit();
    }
}
?>