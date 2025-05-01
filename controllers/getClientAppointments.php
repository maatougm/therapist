<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/errorHandler.php';

// Debug session variables
error_log("Session variables: " . print_r($_SESSION, true));

// Check if user is logged in and is either admin or therapist
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    error_log("Unauthorized access attempt. Session: " . print_r($_SESSION, true));
    http_response_code(401);
    die('Unauthorized');
}

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    echo '<tr><td colspan="5" class="text-danger text-center">ID client manquant</td></tr>';
    exit;
}

$user_id = intval($_GET['user_id']);
$past_only = isset($_GET['past_only']) && $_GET['past_only'] === 'true';

try {
    // Build the query
    $query = "
        SELECT a.*, l.name as location_name, 
               r.id as report_id,
               r.content as report_content,
               r.created_at as report_date,
               t.name as therapist_name,
               CASE 
                   WHEN r.id IS NOT NULL THEN 'Oui'
                   ELSE 'Non'
               END as has_report
        FROM appointments a
        JOIN locations l ON a.location_id = l.id
        LEFT JOIN reports r ON a.id = r.appointment_id
        LEFT JOIN users t ON r.therapist_id = t.id
        WHERE a.user_id = ?
        AND a.status != 'cancelled'
        AND a.date < CURDATE()
        ORDER BY a.date DESC, a.hour DESC LIMIT 10
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($appointments)) {
        echo '<tr><td colspan="5" class="text-center">Aucun rendez-vous trouvé</td></tr>';
        exit;
    }

    foreach ($appointments as $appointment) {
        $status_class = '';
        switch ($appointment['status']) {
            case 'confirmed':
                $status_class = 'text-success';
                break;
            case 'pending':
                $status_class = 'text-warning';
                break;
        }
        
        echo '<tr>';
        echo '<td>' . date('d/m/Y', strtotime($appointment['date'])) . '</td>';
        echo '<td>' . substr($appointment['hour'], 0, 5) . '</td>';
        echo '<td>' . htmlspecialchars($appointment['location_name']) . '</td>';
        echo '<td><span class="' . $status_class . '">' . ucfirst($appointment['status']) . '</span></td>';
        echo '<td>';
        if ($appointment['has_report'] === 'Oui') {
            echo '<button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#reportModal" 
                    data-report-id="' . $appointment['report_id'] . '"
                    data-therapist="' . htmlspecialchars($appointment['therapist_name']) . '"
                    data-content="' . htmlspecialchars($appointment['report_content']) . '"
                    data-date="' . date('d/m/Y H:i', strtotime($appointment['report_date'])) . '">
                    <i class="bi bi-file-text"></i> Voir rapport
                </button>';
        } else {
            echo '<span class="text-muted">-</span>';
        }
        echo '</td>';
        echo '</tr>';
    }
} catch (PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    echo '<tr><td colspan="5" class="text-danger text-center">Erreur lors de la récupération des rendez-vous</td></tr>';
}
?> 