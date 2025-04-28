<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $rapport = $_POST['rapport'];

    // Update user profile
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $id]);

    // Insert report
    $stmt2 = $pdo->prepare("INSERT INTO reports (patient_id, therapist_id, report_content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt2->execute([$id, $id, $rapport]);

    header("Location: ../kine_dashboard.php?updated=1");
    exit();
}
