<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/errorHandler.php';

// Check authentication
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de rendez-vous manquant']);
    exit;
}

$appointment_id = intval($_GET['id']);
$user_id = $_SESSION['id'];
$is_admin = $_SESSION['role'] === 'admin';

try {
    // Get appointment details
    $sql = "
        SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone,
               l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
    ";
    
    if (!$is_admin) {
        $sql .= " INNER JOIN therapist_locations tl ON a.location_id = tl.location_id AND tl.therapist_id = ?";
    }
    
    $sql .= " WHERE a.id = ?";
    
    $params = [];
    if (!$is_admin) {
        $params[] = $user_id;
    }
    $params[] = $appointment_id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Rendez-vous non trouvé']);
        exit;
    }
    
    // Get appointment history
    $history_sql = "
        SELECT a.*, l.name as location_name, r.id as report_id
        FROM appointments a
        JOIN locations l ON a.location_id = l.id
        LEFT JOIN reports r ON a.id = r.appointment_id
        WHERE a.user_id = ?
        AND a.id != ?
        ORDER BY a.date DESC, a.hour DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($history_sql);
    $stmt->execute([$appointment['user_id'], $appointment_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'success' => true,
        'client' => [
            'name' => $appointment['client_name'],
            'email' => $appointment['client_email'],
            'phone' => $appointment['client_phone']
        ],
        'appointment' => [
            'date' => date('d/m/Y', strtotime($appointment['date'])),
            'hour' => substr($appointment['hour'], 0, 5),
            'location_name' => $appointment['location_name'],
            'status' => $appointment['status']
        ],
        'history' => array_map(function($appt) {
            return [
                'date' => date('d/m/Y', strtotime($appt['date'])),
                'hour' => substr($appt['hour'], 0, 5),
                'location_name' => $appt['location_name'],
                'status' => $appt['status'],
                'report_id' => $appt['report_id']
            ];
        }, $history)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des détails']);
} 