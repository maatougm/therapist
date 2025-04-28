<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['client_id'])) {
    echo json_encode(['error' => 'ID client manquant']);
    exit();
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT r.*, a.date as appointment_date
        FROM reports r
        JOIN appointments a ON r.appointment_id = a.id
        WHERE a.user_id = ? AND a.therapist_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_GET['client_id'], $_SESSION['user']['id']]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($reports);
} catch (PDOException $e) {
    error_log("Error fetching client reports: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur lors de la récupération des rapports']);
} 