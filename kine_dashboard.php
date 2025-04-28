<?php
require_once 'config/auth.php';
requireRole('kine');
require 'config/db.php';

include 'partials/header.php';

setlocale(LC_TIME, 'fr_FR.utf8', 'fra'); // For French if you want ucfirst in French, optional now

$therapist = $_SESSION['user'];
$kine_id = $therapist['id'];

$selectedWeek = $_GET['week'] ?? date('Y-m-d');
$startOfWeek = new DateTime($selectedWeek);
$startOfWeek->modify('monday this week');
$today = (new DateTime())->format('Y-m-d');

// Prev/Next week URLs
$prevWeek = (clone $startOfWeek)->modify('-7 days')->format('Y-m-d');
$nextWeek = (clone $startOfWeek)->modify('+7 days')->format('Y-m-d');

// Get assigned locations
$stmt = $pdo->prepare("
    SELECT l.* FROM locations l
    JOIN therapist_locations tl ON l.id = tl.location_id
    WHERE tl.therapist_id = ? AND l.status = 'active'
");
$stmt->execute([$kine_id]);
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build week days array
$week_days = [];
for ($i = 0; $i < 7; $i++) {
    $day = clone $startOfWeek;
    $day->modify("+$i days");
    $week_days[] = [
        'date' => $day->format('Y-m-d'),
        'day_name' => ucfirst($day->format('l')), // ✅ Replaced strftime
        'day_number' => $day->format('d')
    ];
}

// Time slots (example: 08:00 to 20:00 every 1 hour)
$time_slots = [];
$start = new DateTime('08:00');
$end = new DateTime('20:00');
while ($start <= $end) {
    $time_slots[] = $start->format('H:i');
    $start->modify('+1 hour');
}

// Weekly appointments by location
$weekly_appointments = [];
foreach ($locations as $loc) {
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as client_name, l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.location_id = ? 
          AND a.date BETWEEN ? AND ?
    ");
    $stmt->execute([
        $loc['id'],
        $startOfWeek->format('Y-m-d'),
        (clone $startOfWeek)->modify('+6 days')->format('Y-m-d')
    ]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appointments as $appt) {
        $timeSlot = (new DateTime($appt['hour']))->format('H:i');
        $weekly_appointments[$appt['date']][$loc['id']][$timeSlot] = $appt;
    }
}

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_appointments,
        COUNT(DISTINCT a.user_id) as total_clients,
        COUNT(CASE WHEN a.date = CURDATE() THEN 1 END) as today_appointments,
        COUNT(CASE WHEN a.date > CURDATE() THEN 1 END) as upcoming_appointments,
        COUNT(CASE WHEN r.id IS NOT NULL THEN 1 END) as total_reports,
        COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_appointments,
        COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_appointments
    FROM appointments a
    JOIN therapist_locations tl ON a.location_id = tl.location_id
    LEFT JOIN reports r ON a.id = r.appointment_id
    WHERE tl.therapist_id = ?
");
$stmt->execute([$kine_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Force dark mode
echo '<script>document.documentElement.setAttribute("data-bs-theme", "dark");</script>';
?>

<link rel="stylesheet" href="./config/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <div class="sidebar-avatar mb-3">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h6 class="mb-1 text-white"><?= htmlspecialchars($_SESSION['user']['name']) ?></h6>
                    <small class="text-muted">Kinésithérapeute</small>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="kine_dashboard.php">
                            <i class="bi bi-calendar-week"></i> Mes Rendez-vous
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage/searchClient.php">
                            <i class="bi bi-search"></i> Rechercher un patient
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage/bookFor.php">
                            <i class="bi bi-calendar-plus"></i> Créer un rendez-vous
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage/createFor.php">
                            <i class="bi bi-person-plus"></i> Ajouter un utilisateur
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Tableau de bord</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="?week=<?= $prevWeek ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-chevron-left"></i> Semaine précédente
                        </a>
                        <a href="?week=<?= $nextWeek ?>" class="btn btn-sm btn-outline-secondary">
                            Semaine suivante <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    <a href="?week=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">
                        Cette semaine
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Rendez-vous totaux</h5>
                            <p class="card-text display-6"><?= $stats['total_appointments'] ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Confirmés</h5>
                            <p class="card-text display-6 text-success"><?= $stats['confirmed_appointments'] ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">En attente</h5>
                            <p class="card-text display-6 text-warning"><?= $stats['pending_appointments'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rendez-vous d'aujourd'hui</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Lieu</th>
                                    <th>Heure</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $today_query = "
                                    SELECT a.*, u.name as client_name, l.name as location_name 
                                    FROM appointments a 
                                    JOIN users u ON a.user_id = u.id 
                                    JOIN locations l ON a.location_id = l.id
                                    WHERE a.therapist_id = ? 
                                    AND a.date = CURDATE() 
                                    ORDER BY a.hour ASC
                                ";
                                $stmt = $pdo->prepare($today_query);
                                $stmt->execute([$kine_id]);
                                $today_appointments = $stmt->fetchAll();

                                if (empty($today_appointments)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Aucun rendez-vous aujourd'hui</td>
                                    </tr>
                                <?php else:
                                    foreach ($today_appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['location_name']); ?></td>
                                            <td><?php echo date('H:i', strtotime($appointment['hour'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $appointment['status'] == 'confirmed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="manage/reports.php?appointment_id=<?php echo $appointment['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-file-text"></i> Rapport
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Weekly Calendar -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <?php foreach ($week_days as $day): ?>
                                        <th class="text-center">
                                            <div><?= $day['day_name'] ?></div>
                                            <div class="fw-bold"><?= $day['day_number'] ?></div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($time_slots as $time): ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?= $time ?></td>
                                        <?php foreach ($week_days as $day): ?>
                                            <td>
                                                <?php
                                                $found = false;
                                                foreach ($locations as $location) {
                                                    if (isset($weekly_appointments[$day['date']][$location['id']][$time])) {
                                                        $appt = $weekly_appointments[$day['date']][$location['id']][$time];
                                                        $found = true;
                                                        ?>
                                                        <div class="appointment-slot p-2 mb-2 rounded" 
                                                             style="background-color: <?php echo $appt['status'] == 'confirmed' ? 'var(--success)' : 'var(--warning)'; ?>"
                                                             data-bs-toggle="modal" 
                                                             data-bs-target="#appointmentModal"
                                                             data-appointment-id="<?php echo $appt['id']; ?>"
                                                             data-client-id="<?php echo $appt['user_id']; ?>">
                                                            <div class="fw-bold"><?php echo htmlspecialchars($appt['client_name']); ?></div>
                                                            <div class="small"><?php echo htmlspecialchars($appt['location_name']); ?></div>
                                                            <div class="small"><?php echo $appt['status'] == 'confirmed' ? 'Confirmé' : 'En attente'; ?></div>
                                                        </div>
                                                        <?php
                                                    }
                                                }
                                                if (!$found) {
                                                    echo '<div class="text-center text-muted">-</div>';
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Appointment Details Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du rendez-vous</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Client Information -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Informations du client</h6>
                            </div>
                            <div class="card-body" id="clientInfo">
                                <!-- Client info will be loaded here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appointment Details -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Détails du rendez-vous</h6>
                            </div>
                            <div class="card-body" id="appointmentDetails">
                                <!-- Appointment details will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Appointments -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Derniers rendez-vous</h6>
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
                                <tbody>
                                    <!-- Last appointments will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Reports -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Rapports</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="reportsTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Contenu</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Reports will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const appointmentModal = document.getElementById('appointmentModal');
    
    appointmentModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const appointmentId = button.getAttribute('data-appointment-id');
        const clientId = button.getAttribute('data-client-id');
        
        // Load client information
        fetch(`/pfaa/api/clients/info.php?client_id=${clientId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('clientInfo').innerHTML = `
                    <p><strong>Nom:</strong> ${data.name}</p>
                    <p><strong>Email:</strong> ${data.email}</p>
                    <p><strong>Téléphone:</strong> ${data.phone}</p>
                `;
            });

        // Load appointment details
        fetch(`/pfaa/api/appointments/details.php?appointment_id=${appointmentId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('appointmentDetails').innerHTML = `
                    <p><strong>Date:</strong> ${data.date}</p>
                    <p><strong>Heure:</strong> ${data.hour}</p>
                    <p><strong>Lieu:</strong> ${data.location_name}</p>
                    <p><strong>Statut:</strong> ${data.status}</p>
                    <p><strong>Notes:</strong> ${data.notes || 'Aucune note'}</p>
                `;
            });

        // Load last appointments
        fetch(`/pfaa/api/appointments/client.php?client_id=${clientId}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#lastAppointments tbody');
                tbody.innerHTML = data.map(appointment => `
                    <tr>
                        <td>${appointment.date}</td>
                        <td>${appointment.hour}</td>
                        <td>${appointment.location_name}</td>
                        <td><span class="badge bg-${appointment.status === 'confirmed' ? 'success' : 'warning'}">${appointment.status}</span></td>
                        <td>
                            ${appointment.has_report ? 
                                `<a href="/pfaa/manage/reports.php?appointment_id=${appointment.id}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> Voir
                                </a>` :
                                `<a href="/pfaa/manage/reports.php?appointment_id=${appointment.id}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Créer
                                </a>`
                            }
                        </td>
                    </tr>
                `).join('');
            });

        // Load reports
        fetch(`/pfaa/api/reports/client.php?client_id=${clientId}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#reportsTable tbody');
                tbody.innerHTML = data.map(report => `
                    <tr>
                        <td>${report.created_at}</td>
                        <td>${report.content.substring(0, 100)}...</td>
                        <td>
                            <a href="/pfaa/manage/reports.php?report_id=${report.id}" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                `).join('');
            });
    });
});
</script>

<style>
.appointment-slot {
    cursor: pointer;
    transition: transform 0.2s;
}

.appointment-slot:hover {
    transform: scale(1.02);
}

.modal-content {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
}

.modal-header {
    border-bottom-color: var(--bs-border-color);
}

.modal-footer {
    border-top-color: var(--bs-border-color);
}
</style>

<?php include 'partials/footer.php'; ?>
