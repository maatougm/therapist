<?php
require '../config/db.php';

if (isset($_POST['update_profile'])) {
    $id = $_POST['id'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    if (!empty($password)) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$password, $phone, $address, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$phone, $address, $id]);
    }

    header("Location: ../profile.php?updated=1");
    exit();
}
