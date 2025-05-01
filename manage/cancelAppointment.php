<?php
session_start();
require '../config/db.php';
require '../helpers/errorHandler.php';

// Check if user is logged in and is either admin or kine
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    redirectWithError('/pfaa/login.php', "Accès non autorisé");
}

if (!isset($_POST['appointment_id'])) {
    redirectWithError('/pfaa/kine_dashboard.php', "ID de rendez-vous manquant");
}

$appointment_id = $_POST['appointment_id'];
$therapist_id = $_SESSION['id'];
$is_admin = $_SESSION['role'] === 'admin';

try {
    // Verify the appointment belongs to the therapist
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM appointments a
        JOIN locations l ON a.location_id = l.id
        " . (!$is_admin ? "JOIN therapist_locations tl ON a.location_id = tl.location_id" : "") . "
        WHERE a.id = ? " . (!$is_admin ? "AND tl.therapist_id = ?" : "") . "
    ");

    if ($is_admin) {
        $stmt->execute([$appointment_id]);
    } else {
        $stmt->execute([$appointment_id, $therapist_id]);
    }
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Rendez-vous non trouvé ou non autorisé");
    }
    
    // Update the appointment status
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$appointment_id]);
    
    redirectWithSuccess('/pfaa/kine_dashboard.php', "Le rendez-vous a été annulé avec succès");
    
} catch (Exception $e) {
    handleException($e, '/pfaa/kine_dashboard.php');
} 