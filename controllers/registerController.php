<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'client')");
    $stmt->execute([$name, $email, $password]);
    header('Location: ../login.php?registered=1');
}
if (isset($_POST['register_by_kine'])) {
    require '../config/db.php';
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, 'client')");
    $stmt->execute([$name, $email, $password, $phone, $address]);

    header("Location: ../kine_dashboard.php?user_created=1");
    exit();
}
