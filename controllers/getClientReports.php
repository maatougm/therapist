<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger">ID client manquant</div>';
    exit;
}

$user_id = intval($_GET['user_id']);

try {
    $stmt = $pdo->prepare("
        SELECT r.*, a.date, a.time, l.name as location_name
        FROM reports r
        JOIN appointments a ON r.appointment_id = a.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.user_id = ?
        ORDER BY a.date DESC, a.time DESC
    ");
    $stmt->execute([$user_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($reports)) {
        echo '<div class="alert alert-info">Aucun rapport trouvé pour ce client</div>';
        exit;
    }

    $html = '<div class="table-responsive">';
    $html .= '<table class="table table-striped">';
    $html .= '<thead><tr>';
    $html .= '<th>Date</th>';
    $html .= '<th>Lieu</th>';
    $html .= '<th>Diagnostic</th>';
    $html .= '<th>Actions</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($reports as $report) {
        $html .= '<tr>';
        $html .= '<td>' . date('d/m/Y H:i', strtotime($report['date'] . ' ' . $report['time'])) . '</td>';
        $html .= '<td>' . htmlspecialchars($report['location_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($report['diagnosis']) . '</td>';
        $html .= '<td>';
        $html .= '<button class="btn btn-sm btn-primary view-report" data-id="' . $report['id'] . '">Voir</button>';
        $html .= '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '</div>';

    echo $html;
} catch (PDOException $e) {
    http_response_code(500);
    echo '<div class="alert alert-danger">Erreur lors de la récupération des rapports</div>';
} 