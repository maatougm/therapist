<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is either admin or therapist
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    http_response_code(401);
    die('Unauthorized');
}

if (!isset($_GET['appointment_id'])) {
    die('Appointment ID required');
}

$appointmentId = $_GET['appointment_id'];

try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               l.name as location_name, 
               u.id as user_id, u.name as client_name
        FROM appointments a 
        LEFT JOIN locations l ON a.location_id = l.id 
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch();

    if ($appointment) {
        echo '<div class="card-body">';
        echo '<h5 class="card-title">Détails du rendez-vous</h5>';
        echo '<p class="card-text">ID Client: ' . htmlspecialchars($appointment['user_id']) . '</p>';
        echo '<p class="card-text">Date: ' . date('d/m/Y', strtotime($appointment['date'])) . '</p>';
        echo '<p class="card-text">Heure: ' . date('H:i', strtotime($appointment['hour'])) . '</p>';
        echo '<p class="card-text">Lieu: ' . htmlspecialchars($appointment['location_name']) . '</p>';
        echo '<p class="card-text">Client: ' . htmlspecialchars($appointment['client_name']) . '</p>';
        echo '<p class="card-text">Statut: ' . htmlspecialchars($appointment['status']) . '</p>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning">Rendez-vous non trouvé</div>';
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur lors de la récupération des détails du rendez-vous</div>';
}
?> 