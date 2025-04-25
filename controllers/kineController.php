<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $rapport = $_POST['rapport'];

    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $id]);

    $stmt2 = $pdo->prepare("INSERT INTO reports (user_id, rapport, created_at) VALUES (?, ?, NOW())");
    $stmt2->execute([$id, $rapport]);

    header("Location: ../kine_dashboard.php?updated=1");
    exit();
}
