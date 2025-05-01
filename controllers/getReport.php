<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is either admin or therapist
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    http_response_code(401);
    echo '<div class="alert alert-warning">Veuillez vous connecter pour voir ces informations</div>';
    exit;
}

if (!isset($_GET['report_id'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger">ID rapport manquant</div>';
    exit;
}

$report_id = intval($_GET['report_id']);

try {
    $stmt = $pdo->prepare("
        SELECT r.*, a.date, a.time, l.name as location_name, 
               u.id as user_id, u.name as client_name, u.email as client_email
        FROM reports r
        JOIN appointments a ON r.appointment_id = a.id
        JOIN locations l ON a.location_id = l.id
        JOIN users u ON a.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        http_response_code(404);
        echo '<div class="alert alert-warning">Rapport non trouvé</div>';
        exit;
    }

    $html = '<div class="report-details">';
    $html .= '<h4>Détails du rapport</h4>';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-6">';
    $html .= '<p><strong>ID Client:</strong> ' . htmlspecialchars($report['user_id']) . '</p>';
    $html .= '<p><strong>Date:</strong> ' . date('d/m/Y H:i', strtotime($report['date'] . ' ' . $report['time'])) . '</p>';
    $html .= '<p><strong>Lieu:</strong> ' . htmlspecialchars($report['location_name']) . '</p>';
    $html .= '<p><strong>Client:</strong> ' . htmlspecialchars($report['client_name']) . '</p>';
    $html .= '<p><strong>Email:</strong> ' . htmlspecialchars($report['client_email']) . '</p>';
    $html .= '</div>';
    $html .= '<div class="col-md-6">';
    $html .= '<p><strong>Diagnostic:</strong> ' . htmlspecialchars($report['diagnosis']) . '</p>';
    $html .= '<p><strong>Traitement:</strong> ' . htmlspecialchars($report['treatment']) . '</p>';
    $html .= '<p><strong>Notes:</strong> ' . htmlspecialchars($report['notes']) . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    echo $html;
} catch (PDOException $e) {
    error_log("Error fetching report: " . $e->getMessage());
    http_response_code(500);
    echo '<div class="alert alert-danger">Erreur lors de la récupération du rapport</div>';
}
?> 