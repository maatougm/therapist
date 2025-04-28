<?php
session_start();
require '../config/db.php';

// Check if user is logged in and is a therapist
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'therapist') {
    header('Location: /pfaa/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: /pfaa/kine_dashboard.php');
    exit();
}

$appointment_id = $_GET['id'];
$therapist_id = $_SESSION['user']['id'];

try {
    // Verify the appointment belongs to the therapist
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM appointments a
        JOIN therapist_locations tl ON a.location_id = tl.location_id
        WHERE a.id = ? AND tl.therapist_id = ?
    ");
    $stmt->execute([$appointment_id, $therapist_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Appointment not found or unauthorized");
    }
    
    // Update the appointment status
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?");
    $stmt->execute([$appointment_id]);
    
    // Redirect back with success message
    header('Location: /pfaa/kine_dashboard.php?success=appointment_confirmed');
    exit();
    
} catch (Exception $e) {
    // Redirect back with error message
    header('Location: /pfaa/kine_dashboard.php?error=' . urlencode($e->getMessage()));
    exit();
} 