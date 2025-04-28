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
        SELECT a.*, l.name as location_name,
               (SELECT COUNT(*) FROM reports WHERE appointment_id = a.id) as has_report
        FROM appointments a
        JOIN locations l ON a.location_id = l.id
        WHERE a.user_id = ? AND a.therapist_id = ?
        ORDER BY a.date DESC, a.hour DESC
        LIMIT 10
    ");
    $stmt->execute([$_GET['client_id'], $_SESSION['user']['id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($appointments);
} catch (PDOException $e) {
    error_log("Error fetching client appointments: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur lors de la récupération des rendez-vous']);
} 