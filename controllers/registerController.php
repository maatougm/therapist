<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'client')");
    $stmt->execute([$name, $email, $hashed_password]);
    header('Location: ../login.php?registered=1');
}
if (isset($_POST['register_by_kine'])) {
    require '../config/db.php';
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    // Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, 'client')");
    $stmt->execute([$name, $email, $hashed_password, $phone, $address]);

    header("Location: ../kine_dashboard.php?user_created=1");
    exit();
}
