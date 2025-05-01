<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/errorHandler.php';

// Check authentication and role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    redirectWithError(url('login.php'), "Accès non autorisé");
}

// Initialize variables
$user_id = $_SESSION['id'];
$is_admin = $_SESSION['role'] === 'admin';
$therapist_id = $user_id; // Default to the logged-in user's ID

// Handle admin viewing therapist's dashboard
if ($is_admin && isset($_GET['therapist_id'])) {
    $therapist_id = intval($_GET['therapist_id']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'therapist'"); // Ensure the ID corresponds to a therapist
    $stmt->execute([$therapist_id]);
    $therapist = $stmt->fetch();
    if (!$therapist) {
        // If therapist_id is provided but invalid or not a therapist, default to the logged-in admin's view
        $therapist_id = $user_id;
        unset($_GET['therapist_id']); // Remove invalid therapist_id from context
        // Optionally, add an error message for admin
        // redirectWithError('kine_dashboard.php', "Kinésithérapeute non trouvé ou ID invalide.");
    }
} else {
     // If not admin or no therapist_id specified for admin, ensure therapist_id is the logged-in user's ID
    $therapist_id = $user_id;
}


// Get active locations
if ($is_admin && isset($_GET['therapist_id'])) {
    // Admin viewing a specific therapist, show locations linked to that therapist
    $stmt = $pdo->prepare("
        SELECT l.* FROM locations l
        JOIN therapist_locations tl ON l.id = tl.location_id
        WHERE tl.therapist_id = ? AND l.status = 'active'
    ");
     $stmt->execute([$therapist_id]);
} else if ($is_admin && !isset($_GET['therapist_id'])) {
    // Admin viewing all locations
     $stmt = $pdo->prepare("SELECT l.* FROM locations l WHERE l.status = 'active'");
     $stmt->execute();
}
else {
    // Therapist viewing their own locations
    $stmt = $pdo->prepare("
        SELECT l.* FROM locations l
        JOIN therapist_locations tl ON l.id = tl.location_id
        WHERE tl.therapist_id = ? AND l.status = 'active'
    ");
    $stmt->execute([$therapist_id]);
}
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get selected location IDs from GET or Session
// Using a therapist-specific session key for selected locations
if (isset($_GET['location_id'])) {
    $requested_location_ids = explode(',', $_GET['location_id']);
    // Filter requested location IDs to ensure they are valid and active for the current user context
    $valid_location_ids = [];
    $available_location_ids = array_column($locations, 'id');
    foreach ($requested_location_ids as $req_id) {
        if (in_array($req_id, $available_location_ids)) {
            $valid_location_ids[] = $req_id;
        }
    }
     $_SESSION['selected_locations'][$therapist_id] = $valid_location_ids; // Store per therapist/admin view
}

// Use selected locations from session for the current therapist/admin view, default if empty
$selected_location_ids = $_SESSION['selected_locations'][$therapist_id] ?? [];

// If no locations are selected (either from GET or session), default to the first available location
if (empty($selected_location_ids) && !empty($locations)) {
    $selected_location_ids = [reset($locations)['id']];
    $_SESSION['selected_locations'][$therapist_id] = $selected_location_ids;
}


// Handle week selection
$selectedWeek = $_GET['week'] ?? date('Y-m-d');
$startOfWeek = new DateTime($selectedWeek);
$startOfWeek->modify('monday this week');
$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+6 days'); // End of week is Sunday
$today = (new DateTime())->format('Y-m-d');
$prevWeek = (clone $startOfWeek)->modify('-7 days')->format('Y-m-d');
$nextWeek = (clone $startOfWeek)->modify('+7 days')->format('Y-m-d');

// Generate week days
$week_days = [];
$dayNames = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];

for ($i = 0; $i < 7; $i++) {
    $day = clone $startOfWeek;
    $day->modify("+$i days");
    $englishDayName = $day->format('l');
    $week_days[] = [
        'date' => $day->format('Y-m-d'),
        'day_name' => $dayNames[$englishDayName],
        'day_number' => $day->format('d')
    ];
}

// Generate time slots
$time_slots = [];
$start = new DateTime('08:00');
$end = new DateTime('20:00');
while ($start <= $end) {
    $time_slots[] = $start->format('H:i');
    $start->modify('+1 hour');
}

// Get weekly appointments
$weekly_appointments = [];
if (!empty($selected_location_ids)) {
    $placeholders = str_repeat('?,', count($selected_location_ids) - 1) . '?';

    $sql = "
        SELECT a.*, u.name as client_name, l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.location_id IN ($placeholders)
        AND a.status != 'cancelled'
        AND a.date BETWEEN ? AND ?
    ";

    $params = array_merge($selected_location_ids, [
        $startOfWeek->format('Y-m-d'),
        $endOfWeek->format('Y-m-d')
    ]);

    if (!$is_admin || ($is_admin && isset($_GET['therapist_id']))) {
        $sql .= " AND a.therapist_id = ?";
        $params[] = $therapist_id;
    }

    $sql .= " ORDER BY a.date ASC, a.hour ASC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($appointments as $appt) {
            $date = $appt['date'];
            $timeSlot = (new DateTime($appt['hour']))->format('H:i');
            if (!isset($weekly_appointments[$date])) {
                $weekly_appointments[$date] = [];
            }
            if (!isset($weekly_appointments[$date][$timeSlot])) {
                $weekly_appointments[$date][$timeSlot] = [];
            }
            $weekly_appointments[$date][$timeSlot][] = $appt;
        }
    } catch (PDOException $e) {
        error_log("Error fetching appointments: " . $e->getMessage());
    }
}

// Get statistics
$stats = null;
if (!empty($selected_location_ids)) {
    $placeholders = str_repeat('?,', count($selected_location_ids) - 1) . '?';
    $sql = "
        SELECT
            COUNT(*) as total_appointments,
            COUNT(DISTINCT a.user_id) as total_clients,
            COUNT(CASE WHEN a.date = CURDATE() THEN 1 END) as today_appointments,
            COUNT(CASE WHEN a.date > CURDATE() THEN 1 END) as upcoming_appointments,
            COUNT(CASE WHEN r.id IS NOT NULL THEN 1 END) as total_reports,
            COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_appointments,
            COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_appointments
        FROM appointments a
        LEFT JOIN reports r ON a.id = r.appointment_id
        WHERE a.location_id IN ($placeholders)
        AND a.status != 'cancelled'
    ";
     $params = $selected_location_ids;

    if (!$is_admin || ($is_admin && isset($_GET['therapist_id']))) {
        // Filter by therapist if not admin or if admin is viewing a specific therapist
        $sql .= " JOIN therapist_locations tl ON a.location_id = tl.location_id AND tl.therapist_id = ?";
        $params[] = $therapist_id;
    }


    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Set dark theme
echo '<script>document.documentElement.setAttribute("data-bs-theme", "dark");</script>';

// Include header after all PHP processing
include __DIR__ . '/partials/header.php';
setlocale(LC_TIME, 'fr_FR.utf8', 'fra'); // Keep setlocale as it might be used elsewhere
?>

<link rel="stylesheet" href="<?= url('config/theme.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <h1 class="h4 mb-4">Tableau de bord<?= ($is_admin && isset($therapist)) ? ' de ' . htmlspecialchars($therapist['name']) : '' ?></h1>

            <?php if (!empty($selected_location_ids)): ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Rendez-vous totaux</h5>
                                <p class="card-text display-6"><?= $stats['total_appointments'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Confirmés</h5>
                                <p class="card-text display-6 text-success"><?= $stats['confirmed_appointments'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">En attente</h5>
                                <p class="card-text display-6 text-warning"><?= $stats['pending_appointments'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Aujourd'hui</h5>
                                <p class="card-text display-6 text-info"><?= $stats['today_appointments'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Calendrier hebdomadaire</h5>
                            <div class="mt-2">
                                <?php foreach ($locations as $location): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="location"
                                                    id="location_<?= $location['id'] ?>"
                                                    value="<?= $location['id'] ?>"
                                                    <?= in_array($location['id'], (array)$selected_location_ids) ? 'checked' : '' ?>
                                                    onchange="updateSelectedLocations(this)">
                                        <label class="form-check-label" for="location_<?= $location['id'] ?>">
                                            <?= htmlspecialchars($location['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($locations)): ?>
                                    <p>Aucun lieu actif disponible pour cette vue.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="btn-group">
                            <?php
                                // Build base URL for navigation, preserving therapist_id for admin if set
                                $baseUrl = 'kine_dashboard.php?';
                                $navParams = [];
                                if (!empty($selected_location_ids)) {
                                    $navParams[] = 'location_id=' . implode(',', $selected_location_ids);
                                }
                                if ($is_admin && isset($_GET['therapist_id'])) {
                                    $navParams[] = 'therapist_id=' . $therapist_id;
                                }
                                $baseUrl .= implode('&', $navParams);
                                $baseUrl .= empty($navParams) ? 'week=' : '&week=';
                            ?>
                            <a href="<?= $baseUrl . $prevWeek ?>"
                               class="btn btn-sm btn-outline-secondary" title="Semaine précédente">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            <a href="<?= $baseUrl . $nextWeek ?>"
                               class="btn btn-sm btn-outline-secondary" title="Semaine suivante">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            <a href="<?= $baseUrl . date('Y-m-d') ?>"
                               class="btn btn-sm btn-outline-secondary" title="Cette semaine">
                                Cette semaine
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Heure</th>
                                        <?php foreach ($week_days as $day): ?>
                                            <th>
                                                <?= $day['day_name'] ?><br>
                                                <small class="text-muted"><?= $day['day_number'] ?></small>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($time_slots as $time): ?>
                                        <tr>
                                            <td class="time-cell"><?= $time ?></td>
                                            <?php foreach ($week_days as $day): ?>
                                                <td class="day-cell">
                                                    <?php
                                                    // Check if it's Sunday using the day name
                                                    $isSunday = $day['day_name'] === 'Dimanche';

                                                    if (isset($weekly_appointments[$day['date']][$time])) {
                                                        $appointments = $weekly_appointments[$day['date']][$time];
                                                        foreach ($appointments as $appt) { ?>
                                                            <div class="appointment-slot <?= $appt['status'] ?>"
                                                                 data-bs-toggle="modal"
                                                                 data-bs-target="#appointmentModal"
                                                                 data-appointment-id="<?= $appt['id'] ?>"
                                                                 data-client-id="<?= $appt['user_id'] ?>">
                                                                 <div class="appointment-info">
                                                                    <div class="appointment-client">
                                                                        <div class="text-truncate"><?= htmlspecialchars($appt['client_name']) ?></div>
                                                                        <div class="appointment-location"><?= htmlspecialchars($appt['location_name']) ?></div>
                                                                    </div>
                                                                    <div class="appointment-actions">
                                                                        <?php if ($appt['status'] === 'pending') { ?>
                                                                            <form method="POST" action="<?= url('manage/confirmAppointment.php') ?>" class="d-inline">
                                                                                <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                                                                <button type="submit" class="btn btn-sm btn-success" title="Confirmer">
                                                                                    <i class="bi bi-check-lg"></i>
                                                                                </button>
                                                                            </form>
                                                                        <?php } ?>
                                                                         <?php if ($appt['status'] !== 'cancelled') { ?>
                                                                            <form method="POST" action="<?= url('manage/cancelAppointment.php') ?>" class="d-inline">
                                                                                <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                                                                <button type="submit" class="btn btn-sm btn-danger" title="Annuler" onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');">
                                                                                    <i class="bi bi-x-lg"></i>
                                                                                </button>
                                                                            </form>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php }
                                                    } elseif (!$isSunday) {
                                                        $isPast = strtotime($day['date'] . ' ' . $time) < time();
                                                        ?>
                                                        <div class="empty-slot">
                                                            <?php if (!empty($selected_location_ids)): // Only show add button if locations are selected ?>
                                                                <?php foreach ($selected_location_ids as $loc_id) {
                                                                     // Find the location name for the tooltip
                                                                     $locationName = '';
                                                                     foreach($locations as $loc) {
                                                                         if ($loc['id'] == $loc_id) {
                                                                             $locationName = ' (' . htmlspecialchars($loc['name']) . ')';
                                                                             break;
                                                                         }
                                                                     }
                                                                    ?>
                                                                    <a href="<?= url('manage/bookFor.php') ?>?date=<?= $day['date'] ?>&hour=<?= $time ?>&location_id=<?= $loc_id ?><?= $is_admin && isset($_GET['therapist_id']) ? '&therapist_id=' . $therapist_id : '' ?>"
                                                                       class="btn btn-sm btn-outline-primary <?= $isPast ? 'disabled' : '' ?>"
                                                                       title="<?= $isPast ? 'Impossible d\'ajouter un rendez-vous dans le passé' : 'Ajouter un rendez-vous' . $locationName ?>"
                                                                       <?= $isPast ? 'onclick="return false;"' : '' ?>>
                                                                        <i class="bi bi-plus-lg"></i>
                                                                    </a>
                                                                <?php } ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php } ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                 <div class="alert alert-info" role="alert">
                    Veuillez sélectionner au moins un lieu pour afficher le calendrier.
                 </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du rendez-vous</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Informations du client</h6>
                            </div>
                            <div class="card-body" id="clientInfo"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Détails du rendez-vous</h6>
                            </div>
                            <div class="card-body" id="appointmentDetails"></div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Historique des rendez-vous</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="lastAppointments">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Heure</th>
                                        <th>Lieu</th>
                                        <th>Statut</th>
                                        <th>Rapport</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du rapport</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6>Kinésithérapeute</h6>
                    <p id="reportTherapist"></p>
                </div>
                <div class="mb-3">
                    <h6>Date du rapport</h6>
                    <p id="reportDate"></p>
                </div>
                <div class="mb-3">
                    <h6>Contenu du rapport</h6>
                    <div id="reportContent" class="border rounded p-3 bg-light"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function updateSelectedLocations(checkbox) {
    const selectedLocations = Array.from(document.querySelectorAll('input[name="location"]:checked'))
        .map(cb => cb.value);
    const urlParams = new URLSearchParams(window.location.search);
    const week = urlParams.get('week') || '<?= $selectedWeek ?>';
    const therapistId = urlParams.get('therapist_id'); // Get therapist_id from URL

    let redirectUrl = `?location_id=${selectedLocations.join(',')}&week=${week}`;
    if (therapistId) { // Append therapist_id if it was in the original URL
        redirectUrl += `&therapist_id=${therapistId}`;
    }

    // Update session storage via AJAX before redirecting
    fetch('update_selected_locations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'locations=' + selectedLocations.join(',') + '&therapist_id=<?= $therapist_id ?>' // Send current therapist_id context
    }).then(() => {
         // After successfully updating session, redirect
        window.location.href = redirectUrl;
    }).catch(error => {
        console.error('Error updating selected locations in session:', error);
        // Optionally, handle error visually
        alert('Erreur lors de la mise à jour des lieux.');
         // Still redirect, but data might not be filtered correctly server-side
         window.location.href = redirectUrl;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const appointmentModal = document.getElementById('appointmentModal');
    if (appointmentModal) {
        appointmentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const appointmentId = button.dataset.appointmentId;
            const clientId = button.dataset.clientId;

            // Reset content
            document.getElementById('clientInfo').innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Chargement...</div>';
            document.getElementById('appointmentDetails').innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Chargement...</div>';
            document.getElementById('lastAppointments').querySelector('tbody').innerHTML = '<tr><td colspan="5" class="text-center">Chargement...</td></tr>';

            // Load client info
            fetch(`<?= url('controllers/getClientInfo.php') ?>?user_id=${clientId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('clientInfo').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading client info:', error);
                    document.getElementById('clientInfo').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des informations du client</div>';
                });

            // Load appointment details
            fetch(`<?= url('controllers/getAppointmentDetails.php') ?>?appointment_id=${appointmentId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('appointmentDetails').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading appointment details:', error);
                    document.getElementById('appointmentDetails').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails du rendez-vous</div>';
                });

            // Load last appointments
            fetch(`<?= url('controllers/getClientAppointments.php') ?>?user_id=${clientId}&past_only=true`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('lastAppointments').querySelector('tbody').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading last appointments:', error);
                    document.getElementById('lastAppointments').querySelector('tbody').innerHTML = '<tr><td colspan="5" class="text-danger text-center">Erreur lors du chargement des derniers rendez-vous</td></tr>';
                });
        });
    }

    // Add tooltips to action buttons
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Report modal handling (assuming report modal triggering is handled in getClientAppointments.php response)
    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const reportData = JSON.parse(button.dataset.report); // Assuming report data is stored as a JSON string

            document.getElementById('reportTherapist').textContent = reportData.therapist_name || 'N/A';
            document.getElementById('reportDate').textContent = reportData.report_date || 'N/A';
            document.getElementById('reportContent').innerHTML = reportData.content || 'Aucun contenu.'; // Use innerHTML if content might contain HTML


        });
         // Clear modal content when hidden
        reportModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('reportTherapist').textContent = '';
            document.getElementById('reportDate').textContent = '';
            document.getElementById('reportContent').innerHTML = '';
        });
    }

     // Delegate click event for report links within the appointment history table
     document.getElementById('lastAppointments').addEventListener('click', function(event) {
        const target = event.target.closest('.report-link'); // Find the closest element with class .report-link
        if (target) {
            event.preventDefault(); // Prevent default link behavior
            const reportId = target.dataset.reportId;

            fetch(`<?= url('controllers/getReportDetails.php') ?>?report_id=${reportId}`)
                .then(response => {
                     if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(reportData => {
                    const reportModalElement = document.getElementById('reportModal');
                    const reportModal = bootstrap.Modal.getInstance(reportModalElement) || new bootstrap.Modal(reportModalElement);

                    document.getElementById('reportTherapist').textContent = reportData.therapist_name || 'N/A';
                    document.getElementById('reportDate').textContent = reportData.report_date || 'N/A';
                    document.getElementById('reportContent').innerHTML = reportData.content || 'Aucun contenu.';

                    reportModal.show();
                })
                .catch(error => {
                    console.error('Error loading report details:', error);
                    alert('Erreur lors du chargement des détails du rapport.');
                });
        }
     });

});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>