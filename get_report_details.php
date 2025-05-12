<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/errorHandler.php';

// Check authentication
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Check if report ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de rapport manquant']);
    exit;
}

$report_id = intval($_GET['id']);
$user_id = $_SESSION['id'];
$is_admin = $_SESSION['role'] === 'admin';

try {
    // Get report details
    $sql = "
        SELECT r.*, u.name as therapist_name, a.date as appointment_date
        FROM reports r
        JOIN appointments a ON r.appointment_id = a.id
        JOIN users u ON a.user_id = u.id
    ";
    
    if (!$is_admin) {
        $sql .= " INNER JOIN therapist_locations tl ON a.location_id = tl.location_id AND tl.therapist_id = ?";
    }
    
    $sql .= " WHERE r.id = ?";
    
    $params = [];
    if (!$is_admin) {
        $params[] = $user_id;
    }
    $params[] = $report_id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Rapport non trouvé']);
        exit;
    }
    
    // Format the response
    $response = [
        'success' => true,
        'therapist_name' => $report['therapist_name'],
        'date' => date('d/m/Y', strtotime($report['appointment_date'])),
        'content' => nl2br(htmlspecialchars($report['content']))
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du rapport']);
} 