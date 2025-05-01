<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kine') {
    die('Unauthorized');
}

if (!isset($_GET['appointment_id'])) {
    die('Appointment ID required');
}

$appointmentId = $_GET['appointment_id'];

try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as therapist_name 
        FROM reports r 
        LEFT JOIN users u ON r.therapist_id = u.id 
        WHERE r.appointment_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$appointmentId]);
    $reports = $stmt->fetchAll();

    if ($reports) {
        echo '<div class="card-body">';
        echo '<h5 class="card-title">Rapports</h5>';
        foreach ($reports as $report) {
            echo '<div class="report-item mb-3">';
            echo '<div class="d-flex justify-content-between align-items-center">';
            echo '<h6 class="mb-1">Rapport du ' . date('d/m/Y H:i', strtotime($report['created_at'])) . '</h6>';
            echo '<small class="text-muted">Par ' . htmlspecialchars($report['therapist_name']) . '</small>';
            echo '</div>';
            echo '<p class="mb-0">' . nl2br(htmlspecialchars($report['content'])) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">Aucun rapport disponible</div>';
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur lors de la récupération des rapports</div>';
}
?> 