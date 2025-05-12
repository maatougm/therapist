<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/notificationHelper.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and has proper role
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    http_response_code(401);
    die('Unauthorized');
}

// Check if appointment_id is provided
if (!isset($_POST['appointment_id'])) {
    http_response_code(400);
    die('Appointment ID required');
}

$appointment_id = intval($_POST['appointment_id']);

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get appointment details with all necessary information
    $stmt = $pdo->prepare("
        SELECT a.*, 
               u.name as client_name, 
               l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.id = ? AND a.status = 'pending'
    ");
    
    if (!$stmt->execute([$appointment_id])) {
        throw new Exception("Failed to fetch appointment details: " . implode(" ", $stmt->errorInfo()));
    }
    
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        throw new Exception("Appointment not found or not pending");
    }

    // Update appointment status
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?");
    if (!$stmt->execute([$appointment_id])) {
        throw new Exception("Failed to update appointment status: " . implode(" ", $stmt->errorInfo()));
    }

    // Create notifications with complete data
    $notificationData = [
        'appointment_id' => $appointment_id,
        'status' => 'confirmed',
        'location' => $appt['location_name'],
        'date' => date('d/m/Y', strtotime($appt['date'])),
        'time' => date('H:i', strtotime($appt['hour'])),
        'client_name' => $appt['client_name'],
        'handled_by' => $_SESSION['id'],
        'type' => 'appointment_confirmed',
        'appointment_status' => 'confirmed',
        'user_id' => $appt['user_id']
    ];

    // Notify client
    if (!addNotification($pdo, $appt['user_id'], '', $notificationData)) {
        throw new Exception("Failed to create client notification");
    }

    // Notify admin if not the handler
    if ($_SESSION['role'] !== 'admin') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND id != ? LIMIT 1");
        if (!$stmt->execute([$_SESSION['id']])) {
            throw new Exception("Failed to fetch admin: " . implode(" ", $stmt->errorInfo()));
        }
        
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            if (!addNotification($pdo, $admin['id'], '', $notificationData)) {
                throw new Exception("Failed to create admin notification");
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Le rendez-vous a été confirmé avec succès'
    ]);
    exit;
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in confirmAppointment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la confirmation du rendez-vous: ' . $e->getMessage()
    ]);
    exit;
} 