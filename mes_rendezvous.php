<?php


// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/db.php';

// Check user authentication
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Fetch user appointments
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.date,
            a.hour,
            a.status,
            a.created_at,
            a.updated_at,
            l.name as location_name,
            l.address as location_address,
            CASE 
                WHEN a.date < CURDATE() THEN 'past'
                ELSE 'future'
            END as appointment_type
        FROM appointments a
        INNER JOIN locations l ON a.location_id = l.id
        WHERE a.user_id = ?
        AND a.status != 'cancelled'
        AND (a.date >= CURDATE() OR a.status = 'confirmed')
        ORDER BY a.date ASC, a.hour ASC
    ");

    $stmt->execute([$_SESSION['id']]);
    $appointments = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = "Une erreur est survenue lors de la récupération de vos rendez-vous. Veuillez réessayer plus tard.";
}

// Include header
include 'partials/header.php';
?>
<?php include 'partials/sidebar.php'; ?>

<!-- Main Content -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Mes Rendez-vous</h1>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($appointments)): ?>
        <div class="alert alert-info" role="alert">
            Vous n'avez pas encore de rendez-vous.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($appointments as $appointment): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 <?php echo $appointment['appointment_type']; ?>-appointment">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                <?php echo htmlspecialchars($appointment['date']); ?>
                            </h5>
                            <p class="card-text">
                                <i class="fas fa-clock text-secondary me-2"></i>
                                <?php echo htmlspecialchars($appointment['hour']); ?>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                <?php echo htmlspecialchars($appointment['location_name']); ?>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                Statut: <?php echo htmlspecialchars($appointment['status']); ?>
                            </p>
                            
                            <?php if ($appointment['appointment_type'] === 'future' && $appointment['status'] === 'pending'): ?>
                                <div class="mt-3">
                                    <button class="btn btn-danger btn-sm" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="rescheduleAppointment(<?php echo $appointment['id']; ?>)">
                                        <i class="fas fa-calendar-alt me-1"></i> Reporter
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
function cancelAppointment(appointmentId) {
    if (confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')) {
        // Add AJAX call to cancel appointment
        console.log('Cancelling appointment:', appointmentId);
    }
}

function rescheduleAppointment(appointmentId) {
    // Add logic to reschedule appointment
    console.log('Rescheduling appointment:', appointmentId);
}
</script>

<?php include 'partials/footer.php'; ?> 