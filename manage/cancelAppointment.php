<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/usercontroller.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
        $appointmentId = intval($_POST['appointment_id']);
        $userId = $_SESSION['id'];
        $userRole = $_SESSION['role'];
        $userName = $_SESSION['name'];
        
        // Get appointment details
        $stmt = $pdo->prepare("
            SELECT a.*, 
                   l.name as location_name,
                   c.name as client_name
            FROM appointments a
            LEFT JOIN locations l ON a.location_id = l.id
            LEFT JOIN users c ON a.user_id = c.id
            WHERE a.id = ?
        ");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new Exception("Rendez-vous non trouvé");
        }

        // Check if user is authorized to cancel this appointment
        if ($appointment['user_id'] != $userId && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'therapist') {
            throw new Exception("Non autorisé à annuler ce rendez-vous");
        }

        // Update appointment status
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$appointmentId]);

        // Determine who cancelled the appointment
        $cancelledBy = '';
        if ($userRole === 'admin') {
            $cancelledBy = "par l'administrateur " . $userName;
        } elseif ($userRole === 'therapist') {
            $cancelledBy = "par le thérapeute " . $userName;
        } else {
            $cancelledBy = "par vous-même";
        }

        // Create notification data
        $notificationData = [
            'appointment_id' => $appointmentId,
            'status' => 'cancelled',
            'location' => $appointment['location_name'],
            'date' => date('d/m/Y', strtotime($appointment['date'])),
            'time' => date('H:i', strtotime($appointment['hour'])),
            'client_name' => $appointment['client_name'],
            'created_at' => date('Y-m-d H:i:s'),
            'type' => 'appointment_cancelled',
            'appointment_status' => 'cancelled',
            'user_id' => $appointment['user_id'],
            'handled_by' => $userId
        ];

        // Notify only the client
        addNotification(
            $pdo,
            $appointment['user_id'],
            '',  // Empty message as we'll format it in the display
            $notificationData
        );

        // Redirect based on user role
        if ($_SESSION['role'] === 'admin') {
            header('Location: ../admin_dashboard.php?cancelled=1');
        } elseif ($_SESSION['role'] === 'therapist') {
            header('Location: ../kine_dashboard.php?cancelled=1');
        } else {
            header('Location: ../user_dashboard.php?cancelled=1');
        }
        exit;
    } else {
        throw new Exception("Requête invalide");
    }
} catch (Exception $e) {
    error_log("Error in cancelAppointment.php: " . $e->getMessage());
    // Redirect based on user role with error message
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: ../admin_dashboard.php?error=cancel_failed');
        } elseif ($_SESSION['role'] === 'therapist') {
            header('Location: ../kine_dashboard.php?error=cancel_failed');
        } else {
            header('Location: ../user_dashboard.php?error=cancel_failed');
        }
    } else {
        header('Location: ../login.php');
    }
    exit;
} 