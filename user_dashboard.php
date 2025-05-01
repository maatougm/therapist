<?php
// Start session and require necessary controllers
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/controllers/usercontroller.php';
require_once __DIR__ . '/controllers/LocationController.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// Initialize controllers
$appointmentController = new AppointmentController($pdo);
$locationController = new LocationController();

$userId = $_SESSION['id'];
$userName = $_SESSION['name'] ?? 'Utilisateur';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'book') {
            $locationId = $_POST['location_id'];
            $date = $_POST['date'];
            $hour = $_POST['hour'];
            $result = $appointmentController->createAppointment($userId, $locationId, $date, $hour);

            if ($result['success']) {
                header('Location: user_dashboard.php?success=1');
            } else {
                header('Location: user_dashboard.php?error=' . urlencode($result['message']));
            }
            exit;
        }

        if ($_POST['action'] === 'cancel') {
            $appointmentId = $_POST['appointment_id'];
            $result = $appointmentController->cancelAppointment($appointmentId);

            if ($result['success']) {
                header('Location: user_dashboard.php?cancelled=1');
            } else {
                header('Location: user_dashboard.php?error=cancel_failed');
            }
            exit;
        }
    }
}

// Fetch user appointments
$appointments = $appointmentController->getAppointmentsByUser($userId);

// Prepare booked slots array for JS
$bookedSlots = [];
foreach ($appointments as $appt) {
    if ($appt['status'] !== 'cancelled' && $appt['date'] >= date('Y-m-d')) {
        $bookedSlots[$appt['date']] = [
            'hour' => $appt['hour'],
            'location_id' => $appt['location_id'],
            'status' => $appt['status']
        ];
    }
}

// Fetch active locations
$locationsList = $locationController->getActiveLocations();

// Build id => name array
$locations = [];
foreach ($locationsList as $loc) {
    $locations[$loc['id']] = $loc['name'];
}

// Fetch special closed whole days
$specialDaysStmt = $pdo->prepare("SELECT date FROM special_days WHERE is_whole_day = 1");
$specialDaysStmt->execute();
$specialDays = $specialDaysStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch special closed hours
$specialHoursStmt = $pdo->prepare("SELECT date, start_time, end_time FROM special_days WHERE is_whole_day = 0");
$specialHoursStmt->execute();
$specialHours = $specialHoursStmt->fetchAll(PDO::FETCH_ASSOC);

// Weekly Appointments
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));

$weeklyAppointments = array_filter($appointments, function($appt) use ($startOfWeek, $endOfWeek) {
    return $appt['date'] >= $startOfWeek && $appt['date'] <= $endOfWeek && $appt['status'] !== 'cancelled';
});
?>

<?php include 'partials/header.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
            <h1 class="h2">Tableau de bord</h1>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Rendez-vous créé avec succès!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_GET['cancelled'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                Rendez-vous annulé avec succès.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Erreur : <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($weeklyAppointments)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-calendar-week me-2 text-primary"></i>
                    Vos rendez-vous cette semaine
                </h5>
                <div class="row g-3">
                    <?php foreach ($weeklyAppointments as $appointment): ?>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                        <?= htmlspecialchars($locations[$appointment['location_id']] ?? 'Lieu inconnu') ?>
                                    </h6>
                                    <p class="card-text">
                                        <i class="fas fa-calendar-day me-2 text-primary"></i>
                                        <?= date('l d F Y', strtotime($appointment['date'])) ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-clock me-2 text-primary"></i>
                                        <?= htmlspecialchars(substr($appointment['hour'], 0, 5)) ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>
                                        Statut: <span class="badge bg-<?= $appointment['status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars(ucfirst($appointment['status'])) ?>
                                        </span>
                                    </p>
                                    <?php 
                                    $allowCancelHours = 6; 
                                    $appointmentDateTime = strtotime($appointment['date'] . ' ' . $appointment['hour']);
                                    $now = time();
                                    if ($appointmentDateTime - $now > $allowCancelHours * 3600):
                                    ?>
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')">
                                            <i class="fas fa-times me-2"></i> Annuler
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                    Choisissez un lieu
                </h5>
                <div class="locations-grid">
                    <?php foreach ($locations as $id => $name): ?>
                        <div class="location-item" 
                            onclick="selectLocation(<?= $id ?>, '<?= htmlspecialchars(addslashes($name)) ?>')">
                            <div class="location-content">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($name) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="calendarSection" style="display:none;">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4" id="selectedLocationName"></h5>
                    <div class="calendar-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <th>Lun</th>
                                    <th>Mar</th>
                                    <th>Mer</th>
                                    <th>Jeu</th>
                                    <th>Ven</th>
                                    <th>Sam</th>
                                    <th>Dim</th>
                                </tr>
                            </thead>
                            <tbody id="calendarBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => alert.classList.remove('show'));
}, 3000);

let selectedLocationId = null;
const bookedSlots = <?= json_encode($bookedSlots) ?>;
const locations = <?= json_encode($locations) ?>;
const specialDays = <?= json_encode($specialDays) ?>;
const specialHours = <?= json_encode($specialHours) ?>;

function selectLocation(id, name) {
    selectedLocationId = id;
    document.getElementById('selectedLocationName').innerHTML = `
        <i class="fas fa-calendar-alt me-2 text-primary"></i>
        Créneaux disponibles pour ${name}
    `;
    document.getElementById('calendarSection').style.display = 'block';
    loadCalendar();
}

function loadCalendar() {
    const calendarBody = document.getElementById('calendarBody');
    calendarBody.innerHTML = '';

    for (let hour = 8; hour <= 18; hour++) {
        const row = document.createElement('tr');
        
        const timeCell = document.createElement('td');
        timeCell.className = 'time-cell';
        timeCell.textContent = `${hour}:00`;
        row.appendChild(timeCell);

        for (let day = 1; day <= 7; day++) {
            const cell = document.createElement('td');
            cell.className = 'day-cell';
            const today = new Date();
            const startOfWeek = today.getDate() - today.getDay() + 1;
            const bookingDate = new Date(today.setDate(startOfWeek + (day - 1)));
            const dateStr = bookingDate.toISOString().split('T')[0];

            if (day === 7) { // Sunday
                cell.innerHTML = '<span class="calendar-badge badge-closed">Fermé</span>';
            } else if (specialDays.includes(dateStr)) {
                cell.innerHTML = '<span class="calendar-badge badge-closed">Fermé</span>';
            } else if (isHourClosed(dateStr, hour)) {
                cell.innerHTML = '<span class="calendar-badge badge-closed">Fermé</span>';
            } else if (bookedSlots[dateStr]) {
                const bookedHour = bookedSlots[dateStr]['hour'].slice(0,5);
                cell.innerHTML = `<span class="calendar-badge badge-booked">${bookedHour}</span>`;
            } else {
                const button = document.createElement('button');
                button.className = 'calendar-button';
                button.innerHTML = '<i class="fas fa-calendar-plus"></i>';
                button.onclick = () => bookSlot(dateStr, hour);
                cell.appendChild(button);
            }
            row.appendChild(cell);
        }
        calendarBody.appendChild(row);
    }
}

function isHourClosed(date, hour) {
    const hourStr = String(hour).padStart(2, '0') + ':00:00';
    return specialHours.some(special => 
        special.date === date &&
        hourStr >= special.start_time &&
        hourStr < special.end_time
    );
}

function bookSlot(date, hour) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="book">
        <input type="hidden" name="location_id" value="${selectedLocationId}">
        <input type="hidden" name="date" value="${date}">
        <input type="hidden" name="hour" value="${String(hour).padStart(2, '0')}:00">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
<?php include 'partials/footer.php'; ?>

