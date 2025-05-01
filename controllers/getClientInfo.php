<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Debug session variables
error_log("Session variables: " . print_r($_SESSION, true));

// Check if user is logged in and is either admin or therapist
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    error_log("Unauthorized access attempt. Session: " . print_r($_SESSION, true));
    http_response_code(401);
    die('Unauthorized');
}

if (!isset($_GET['client_id'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger">ID client manquant</div>';
    exit;
}

$user_id = intval($_GET['client_id']);

try {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               GROUP_CONCAT(DISTINCT l.name) as locations
        FROM users u
        LEFT JOIN appointments a ON u.id = a.user_id
        LEFT JOIN locations l ON a.location_id = l.id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo '<div class="alert alert-danger">Client non trouvé</div>';
        exit;
    }

    $html = '<div class="row">';
    $html .= '<div class="col-md-6">';
    $html .= '<p><strong>ID Client:</strong> ' . htmlspecialchars($client['id'] ?? '') . '</p>';
    $html .= '<p><strong>Nom:</strong> ' . htmlspecialchars($client['name'] ?? '') . '</p>';
    $html .= '<p><strong>Email:</strong> ' . htmlspecialchars($client['email'] ?? '') . '</p>';
    $html .= '<p><strong>Téléphone:</strong> ' . htmlspecialchars($client['phone'] ?? 'Non renseigné') . '</p>';
    $html .= '</div>';
    $html .= '<div class="col-md-6">';
    $html .= '<p><strong>Lieux visités:</strong> ' . htmlspecialchars($client['locations'] ?? 'Aucun') . '</p>';
    $html .= '<p><strong>Date d\'inscription:</strong> ' . date('d/m/Y', strtotime($client['created_at'] ?? 'now')) . '</p>';
    $html .= '</div>';
    $html .= '</div>';

    // Add Previous Appointments Section
    $html .= '<div class="row mt-4">';
    $html .= '<div class="col-12">';
    $html .= '<h5 class="mb-3">Rendez-vous précédents</h5>';
    
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, l.name as location_name, r.id as report_id, r.content as report_content
            FROM appointments a
            LEFT JOIN locations l ON a.location_id = l.id
            LEFT JOIN reports r ON a.id = r.appointment_id
            WHERE a.user_id = ? AND a.status != 'cancelled'
            ORDER BY a.date DESC, a.hour DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($appointments)) {
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-hover">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Date</th>';
            $html .= '<th>Heure</th>';
            $html .= '<th>Lieu</th>';
            $html .= '<th>Statut</th>';
            $html .= '<th>Rapport</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($appointments as $appointment) {
                $html .= '<tr>';
                $html .= '<td>' . date('d/m/Y', strtotime($appointment['date'])) . '</td>';
                $html .= '<td>' . date('H:i', strtotime($appointment['hour'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($appointment['location_name'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($appointment['status'] ?? '') . '</td>';
                $html .= '<td>';
                if ($appointment['report_id']) {
                    $html .= '<button class="btn btn-sm btn-info" onclick="showReport(' . $appointment['report_id'] . ')">';
                    $html .= '<i class="bi bi-file-text"></i> Voir rapport';
                    $html .= '</button>';
                } else {
                    $html .= '<span class="text-muted">-</span>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-info">Aucun rendez-vous trouvé</div>';
        }
    } catch (PDOException $e) {
        $html .= '<div class="alert alert-danger">Erreur lors de la récupération des rendez-vous</div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    // Add Reports Section
    $html .= '<div class="row mt-4">';
    $html .= '<div class="col-12">';
    $html .= '<h5 class="mb-3">Rapports médicaux</h5>';
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, a.date, a.hour, l.name as location_name
            FROM reports r
            JOIN appointments a ON r.appointment_id = a.id
            JOIN locations l ON a.location_id = l.id
            WHERE a.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($reports)) {
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-hover">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Date</th>';
            $html .= '<th>Lieu</th>';
            $html .= '<th>Diagnostic</th>';
            $html .= '<th>Actions</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($reports as $report) {
                $html .= '<tr>';
                $html .= '<td>' . date('d/m/Y H:i', strtotime($report['date'] . ' ' . $report['hour'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($report['location_name'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($report['diagnosis'] ?? '') . '</td>';
                $html .= '<td>';
                $html .= '<button class="btn btn-sm btn-primary" onclick="showReport(' . $report['id'] . ')">';
                $html .= '<i class="bi bi-eye"></i> Voir détails';
                $html .= '</button>';
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-info">Aucun rapport trouvé</div>';
        }
    } catch (PDOException $e) {
        $html .= '<div class="alert alert-danger">Erreur lors de la récupération des rapports</div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    // Add JavaScript for report viewing
    $html .= '<script>
    function showReport(reportId) {
        fetch(`../controllers/getReport.php?report_id=${reportId}`)
            .then(response => response.text())
            .then(html => {
                const modal = new bootstrap.Modal(document.getElementById("reportModal"));
                document.getElementById("reportContent").innerHTML = html;
                modal.show();
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Erreur lors du chargement du rapport");
            });
    }
    </script>';

    // Add Report Modal
    $html .= '<div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails du rapport</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reportContent">
                    <!-- Report content will be loaded here -->
                </div>
            </div>
        </div>
    </div>';

    echo $html;
} catch (PDOException $e) {
    error_log("Database error in getClientInfo.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    error_log("Error Info: " . print_r($stmt->errorInfo(), true));
    http_response_code(500);
    echo '<div class="alert alert-danger">Erreur lors de la récupération des informations du client</div>';
}
?> 