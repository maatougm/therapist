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
                                    <form method="POST" action="<?= url('manage/cancelAppointment.php') ?>" class="mt-3">
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
                <div class="row g-3">
                    <?php foreach ($locations as $id => $name): ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="location-card" onclick="selectLocation(<?= $id ?>, '<?= htmlspecialchars(addslashes($name)) ?>')">
                                <div class="location-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="location-name">
                                    <?= htmlspecialchars($name) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="calendarSection" style="display:none;">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0" id="selectedLocationName"></h5>
                        <div class="calendar-nav">
                            <button class="btn btn-outline-primary btn-sm" onclick="previousWeek()">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="mx-2" id="currentWeek"></span>
                            <button class="btn btn-outline-primary btn-sm" onclick="nextWeek()">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Calendar Legend -->
                    <div class="calendar-legend mb-3">
                        <div class="d-flex flex-wrap gap-2">
                            <div class="legend-item">
                                <span class="legend-color available"></span>
                                <span class="legend-text">Disponible</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color booked"></span>
                                <span class="legend-text">Votre rendez-vous</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color closed"></span>
                                <span class="legend-text">Fermé</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color unavailable"></span>
                                <span class="legend-text">Indisponible</span>
                            </div>
                        </div>
                    </div>

                    <div class="calendar-container">
                        <div class="table-responsive">
                            <table class="table table-bordered calendar-table">
                                <thead>
                                    <tr>
                                        <th class="time-column">Heure</th>
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
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-bell me-2"></i>
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<div class="modal fade" id="notificationsModal" tabindex="-1" aria-labelledby="notificationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationsModalLabel">Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="notificationsList" class="list-group">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Chargement des notifications...</p>
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

let currentWeekStart = new Date();
currentWeekStart.setHours(0, 0, 0, 0);
currentWeekStart.setDate(currentWeekStart.getDate() - currentWeekStart.getDay() + 1);

function updateCurrentWeekDisplay() {
    const weekEnd = new Date(currentWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    const options = { day: 'numeric', month: 'long' };
    const startDate = currentWeekStart.toLocaleDateString('fr-FR', options);
    const endDate = weekEnd.toLocaleDateString('fr-FR', options);
    
    document.getElementById('currentWeek').textContent = `${startDate} - ${endDate}`;
}

function previousWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
    updateCalendar();
}

function nextWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
    updateCalendar();
}

function selectLocation(locationId, locationName) {
    selectedLocationId = locationId;
    document.getElementById('selectedLocationName').textContent = locationName;
    document.getElementById('calendarSection').style.display = 'block';
    updateCalendar();
}

function checkSlotAvailability(date, hour) {
    const dateStr = date.toISOString().split('T')[0];
    const hourStr = hour + ':00';
    
    // Check if it's a special closed day
    if (specialDays.includes(dateStr)) {
        return false;
    }
    
    // Check if it's a special closed hour
    const isSpecialHour = specialHours.some(special => 
        special.date === dateStr &&
        hourStr >= special.start_time &&
        hourStr < special.end_time
    );
    if (isSpecialHour) {
        return false;
    }
    
    // Check if user already has an appointment at this time
    if (bookedSlots[dateStr] && bookedSlots[dateStr].hour === hourStr) {
        return false;
    }
    
    // Check if it's a past date/time
    const now = new Date();
    const slotDateTime = new Date(dateStr + 'T' + hourStr);
    if (slotDateTime < now) {
        return false;
    }
    
    // Check if it's Sunday (day 0)
    if (date.getDay() === 0) {
        return false;
    }
    
    return true;
}

function getSlotTooltip(date, hour) {
    const dateStr = date.toISOString().split('T')[0];
    const hourStr = hour + ':00';
    
    // Check if it's Sunday
    if (date.getDay() === 0) {
        return "Fermé le dimanche";
    }
    
    // Check if it's a special closed day
    if (specialDays.includes(dateStr)) {
        return "Jour spécial - Fermé";
    }
    
    // Check if it's a special closed hour
    const specialHour = specialHours.find(special => 
        special.date === dateStr &&
        hourStr >= special.start_time &&
        hourStr < special.end_time
    );
    if (specialHour) {
        return "Horaire spécial - Fermé";
    }
    
    // Check if user has an appointment
    if (bookedSlots[dateStr] && bookedSlots[dateStr].hour === hourStr) {
        return "Vous avez déjà un rendez-vous à cette heure";
    }
    
    // Check if it's a past date/time
    const now = new Date();
    const slotDateTime = new Date(dateStr + 'T' + hourStr);
    if (slotDateTime < now) {
        return "Cette date est passée";
    }
    
    return "Cliquez pour réserver";
}

function updateCalendar() {
    updateCurrentWeekDisplay();
    const calendarBody = document.getElementById('calendarBody');
    calendarBody.innerHTML = '';
    
    const hours = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00'];
    
    hours.forEach(hour => {
        const row = document.createElement('tr');
        
        // Time column
        const timeCell = document.createElement('td');
        timeCell.textContent = hour;
        row.appendChild(timeCell);
        
        // Days columns
        for (let i = 0; i < 7; i++) {
            const date = new Date(currentWeekStart);
            date.setDate(date.getDate() + i);
            const dateStr = date.toISOString().split('T')[0];
            
            const cell = document.createElement('td');
            const slot = document.createElement('div');
            slot.className = 'calendar-slot';
            
            // Set tooltip
            slot.setAttribute('data-bs-toggle', 'tooltip');
            slot.setAttribute('data-bs-placement', 'top');
            slot.setAttribute('title', getSlotTooltip(date, hour));
            
            // Check if it's Sunday
            if (date.getDay() === 0) {
                slot.className += ' closed';
                slot.innerHTML = '<i class="fas fa-lock"></i>';
            }
            // Check if it's a special closed day
            else if (specialDays.includes(dateStr)) {
                slot.className += ' closed';
                slot.innerHTML = '<i class="fas fa-calendar-times"></i>';
            }
            // Check if user has an appointment
            else if (bookedSlots[dateStr] && bookedSlots[dateStr].hour === hour + ':00') {
                slot.className += ' booked';
                slot.innerHTML = '<i class="fas fa-check"></i>';
            }
            // Check if slot is available
            else if (checkSlotAvailability(date, hour)) {
                slot.className += ' available';
                slot.onclick = () => bookAppointment(date, hour);
                slot.innerHTML = '<i class="fas fa-plus"></i>';
            }
            // Past or unavailable slot
            else {
                slot.className += ' unavailable';
                slot.innerHTML = '<i class="fas fa-times"></i>';
            }
            
            cell.appendChild(slot);
            row.appendChild(cell);
        }
        
        calendarBody.appendChild(row);
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function bookAppointment(date, hour) {
    const dateStr = date.toISOString().split('T')[0];
    const hourStr = hour + ':00';
    
    // Create and submit the booking form
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="book">
        <input type="hidden" name="location_id" value="${selectedLocationId}">
        <input type="hidden" name="date" value="${dateStr}">
        <input type="hidden" name="hour" value="${hourStr}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Initialize calendar when page loads
document.addEventListener('DOMContentLoaded', () => {
    updateCurrentWeekDisplay();
});

function showNotification(message, type = 'success') {
    const toast = document.getElementById('notificationToast');
    const toastBody = toast.querySelector('.toast-body');
    
    // Set message
    toastBody.textContent = message;
    
    // Set color based on type
    toast.className = 'toast';
    if (type === 'success') {
        toast.classList.add('bg-success', 'text-white');
    } else if (type === 'error') {
        toast.classList.add('bg-danger', 'text-white');
    }
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

function cancelAppointment(appointmentId) {
    if (confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')) {
        $.ajax({
            url: 'manage/cancelAppointment.php',
            method: 'POST',
            data: { appointment_id: appointmentId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    // Reload the page to show updated status
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Une erreur est survenue lors de l\'annulation du rendez-vous', 'error');
            }
        });
    }
}
</script>

<style>
.toast {
    min-width: 300px;
    background-color: white;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.toast.bg-success {
    background-color: #198754 !important;
}

.toast.bg-danger {
    background-color: #dc3545 !important;
}

.toast-header {
    border-bottom: none;
    padding: 0.75rem 1rem;
}

.toast-body {
    padding: 1rem;
}

.toast .btn-close {
    filter: brightness(0) invert(1);
}

.location-card {
    background: var(--bg-card);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.location-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    border-color: var(--primary);
}

.location-icon {
    font-size: 1.5rem;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.location-name {
    font-size: 0.9rem;
    color: var(--text);
    font-weight: 500;
}

.calendar-container {
    background: var(--bg-card);
    border-radius: 8px;
    padding: 0.5rem;
    overflow-x: auto;
}

.calendar-table {
    margin-bottom: 0;
    font-size: 0.9rem;
}

.calendar-table th {
    background: var(--primary);
    color: white;
    text-align: center;
    padding: 0.5rem;
    font-weight: 500;
    white-space: nowrap;
}

.calendar-table td {
    padding: 0.25rem;
    text-align: center;
    vertical-align: middle;
    min-width: 80px;
}

.time-column {
    background: var(--primary-light) !important;
    color: var(--text) !important;
    font-weight: 500;
    min-width: 60px !important;
}

.calendar-slot {
    padding: 0.25rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    min-height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-slot:hover {
    background: var(--hover-light);
}

.calendar-slot.available {
    background: var(--success);
    color: white;
}

.calendar-slot.booked {
    background: var(--primary);
    color: white;
    cursor: not-allowed;
}

.calendar-slot.past {
    background: var(--text-lighter);
    color: white;
    cursor: not-allowed;
}

.calendar-slot.closed {
    background: var(--text-lighter);
    color: white;
    cursor: not-allowed;
}

.calendar-slot.unavailable {
    background: var(--danger);
    color: white;
    cursor: not-allowed;
    opacity: 0.7;
}

.calendar-slot i {
    font-size: 0.8rem;
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.calendar-nav button {
    padding: 0.25rem 0.5rem;
    font-size: 0.9rem;
}

.calendar-nav span {
    font-weight: 500;
    color: var(--text);
    font-size: 0.9rem;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .calendar-table td {
        min-width: 70px;
    }
}

@media (max-width: 992px) {
    .calendar-table td {
        min-width: 60px;
    }
    
    .calendar-slot {
        padding: 0.2rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 768px) {
    .location-card {
        padding: 0.75rem;
    }
    
    .location-icon {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }
    
    .location-name {
        font-size: 0.85rem;
    }
    
    .calendar-table {
        font-size: 0.8rem;
    }
    
    .calendar-table td {
        min-width: 50px;
        padding: 0.15rem;
    }
    
    .calendar-slot {
        padding: 0.15rem;
        font-size: 0.75rem;
        min-height: 25px;
    }
    
    .calendar-nav button {
        padding: 0.15rem 0.35rem;
        font-size: 0.8rem;
    }
    
    .calendar-nav span {
        font-size: 0.8rem;
    }
    
    .calendar-slot i {
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .calendar-table td {
        min-width: 40px;
    }
    
    .time-column {
        min-width: 45px !important;
    }
    
    .calendar-slot {
        font-size: 0.7rem;
        min-height: 22px;
    }
}

.calendar-legend {
    font-size: 0.85rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: var(--bg-card);
    border-radius: 4px;
    border: 1px solid var(--border-light);
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
}

.legend-color.available {
    background: var(--success);
}

.legend-color.booked {
    background: var(--primary);
}

.legend-color.closed {
    background: var(--text-lighter);
}

.legend-color.unavailable {
    background: var(--danger);
    opacity: 0.7;
}

.legend-text {
    color: var(--text);
}

.calendar-slot {
    position: relative;
}

.calendar-slot[data-bs-toggle="tooltip"] {
    cursor: help;
}

@media (max-width: 768px) {
    .calendar-legend {
        font-size: 0.75rem;
    }
    
    .legend-item {
        padding: 0.15rem 0.35rem;
    }
    
    .legend-color {
        width: 12px;
        height: 12px;
    }
}
</style>
<?php include 'partials/footer.php'; ?>

