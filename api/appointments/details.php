<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['appointment_id'])) {
    echo json_encode(['error' => 'ID rendez-vous manquant']);
    exit();
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT a.*, l.name as location_name
        FROM appointments a
        JOIN locations l ON a.location_id = l.id
        WHERE a.id = ? AND a.therapist_id = ?
    ");
    $stmt->execute([$_GET['appointment_id'], $_SESSION['user']['id']]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        echo json_encode(['error' => 'Rendez-vous non trouvé']);
        exit();
    }

    echo json_encode($appointment);
} catch (PDOException $e) {
    error_log("Error fetching appointment details: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur lors de la récupération des détails']);
} 