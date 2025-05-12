<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;
$action = $data['action'] ?? null;

if (!$notification_id || !in_array($action, ['confirm', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $isTherapist = isset($_SESSION['role']) && $_SESSION['role'] === 'therapist';
    
    // Get notification data with appointment details
    if ($isAdmin || $isTherapist) {
        $stmt = $pdo->prepare("
            SELECT n.*, a.date, a.hour, l.name as location_name, 
                   u.name as handler_name, u.role as handler_role,
                   c.name as client_name, c.id as client_id
            FROM notifications n
            LEFT JOIN appointments a ON n.appointment_id = a.id
            LEFT JOIN locations l ON a.location_id = l.id
            LEFT JOIN users u ON u.id = ?
            LEFT JOIN users c ON a.user_id = c.id
            WHERE n.id = ?
        ");
        $stmt->execute([$_SESSION['id'], $notification_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT n.*, a.date, a.hour, l.name as location_name, 
                   u.name as handler_name, u.role as handler_role,
                   c.name as client_name, c.id as client_id
            FROM notifications n
            LEFT JOIN appointments a ON n.appointment_id = a.id
            LEFT JOIN locations l ON a.location_id = l.id
            LEFT JOIN users u ON u.id = ?
            LEFT JOIN users c ON a.user_id = c.id
            WHERE n.id = ? AND n.user_id = ?
        ");
        $stmt->execute([$_SESSION['id'], $notification_id, $_SESSION['id']]);
    }
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notification) {
        echo json_encode(['success' => false, 'message' => 'Notification non trouvée']);
        exit;
    }

    // Update notification as read and set handler
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, handled_by = ? WHERE id = ?");
    $stmt->execute([$_SESSION['id'], $notification_id]);

    // If this is an appointment notification, update the appointment status
    if (!empty($notification['appointment_id'])) {
        $appointment_status = $action === 'confirm' ? 'confirmed' : 'cancelled';
        
        // Update appointment status
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$appointment_status, $notification['appointment_id']]);

        // Create notification data
        $notificationData = [
            'appointment_id' => $notification['appointment_id'],
            'status' => $appointment_status,
            'location' => $notification['location_name'],
            'date' => date('d/m/Y', strtotime($notification['date'])),
            'time' => date('H:i', strtotime($notification['hour'])),
            'client_name' => $notification['client_name'],
            'created_at' => date('Y-m-d H:i:s'),
            'type' => $appointment_status === 'confirmed' ? 'appointment_confirmed' : 'appointment_cancelled',
            'appointment_status' => $appointment_status,
            'user_id' => $notification['client_id'],
            'handled_by' => $_SESSION['id']
        ];
        
        // Add a new notification for the client
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, appointment_id, handled_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $notification['client_id'],
            $notificationData['type'],
            $notification['appointment_id'],
            $_SESSION['id']
        ]);

        // Mark original notification as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$notification_id]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error in handle_notification.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors du traitement de la notification: ' . $e->getMessage()]);
} 