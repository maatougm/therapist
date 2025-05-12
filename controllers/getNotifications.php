<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

try {
    // Get notifications for the current user
    $stmt = $pdo->prepare("
        SELECT n.*, 
               a.date as appointment_date,
               a.hour as appointment_time,
               l.name as location_name,
               u.name as client_name,
               CASE 
                   WHEN n.is_read = 1 THEN 'read'
                   ELSE 'unread'
               END as status
        FROM notifications n
        LEFT JOIN appointments a ON n.appointment_id = a.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN users u ON a.user_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$_SESSION['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format notifications
    foreach ($notifications as &$notification) {
        if ($notification['type'] === 'appointment_confirmed') {
            $notification['message'] = "Votre rendez-vous du " . date('d/m/Y', strtotime($notification['appointment_date'])) . 
                                     " à " . date('H:i', strtotime($notification['appointment_time'])) . 
                                     " à " . $notification['location_name'] . " a été confirmé";
        } elseif ($notification['type'] === 'appointment_cancelled') {
            $notification['message'] = "Votre rendez-vous du " . date('d/m/Y', strtotime($notification['appointment_date'])) . 
                                     " à " . date('H:i', strtotime($notification['appointment_time'])) . 
                                     " à " . $notification['location_name'] . " a été annulé";
        } elseif ($notification['type'] === 'new_appointment') {
            $notification['message'] = $notification['client_name'] . " a réservé un rendez-vous le " . 
                                     date('d/m/Y', strtotime($notification['appointment_date'])) . 
                                     " à " . date('H:i', strtotime($notification['appointment_time'])) . 
                                     " à " . $notification['location_name'];
        }
    }

    echo json_encode(['notifications' => $notifications]);
} catch (Exception $e) {
    error_log("Error in getNotifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Une erreur est survenue lors du chargement des notifications']);
} 