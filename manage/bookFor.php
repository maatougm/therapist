<?php
/**
 * Appointment Booking Page
 * 
 * Allows therapists to create new appointments for clients.
 * Includes form validation, client selection, and appointment scheduling.
 */

require_once '../config/auth.php';
requireRole('kine');
require '../config/db.php';
include '../partials/header.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: /pfaa/login.php');
    exit();
}

$therapist_id = $_SESSION['user']['id'];

try {
    /**
     * Fetch Available Clients
     * 
     * Retrieves all clients (users with role 'client') for the dropdown selection
     */
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM users 
        WHERE role = 'client' 
        ORDER BY name
    ");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * Fetch Available Locations
     * 
     * Retrieves all locations for the dropdown selection
     */
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM locations 
        ORDER BY name
    ");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * Fetch Upcoming Appointments
     * 
     * Retrieves appointments for the current therapist:
     * - Today's appointments
     * - Upcoming appointments (next 7 days)
     */
    $today = date('Y-m-d');
    $next_week = date('Y-m-d', strtotime('+7 days'));

    // Today's appointments
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as client_name, l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.therapist_id = ? AND a.date = ?
        ORDER BY a.hour
    ");
    $stmt->execute([$_SESSION['user']['id'], $today]);
    $today_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming appointments
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as client_name, l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.therapist_id = ? AND a.date > ? AND a.date <= ?
        ORDER BY a.date, a.hour
    ");
    $stmt->execute([$_SESSION['user']['id'], $today, $next_week]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log error and display error message
    error_log("Error fetching data: " . $e->getMessage());
    $error = "Une erreur est survenue lors du chargement des données";
}

// Force dark mode
echo '<script>document.documentElement.setAttribute("data-bs-theme", "dark");</script>';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <?php include '../partials/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Créer un rendez-vous</h1>
            </div>

            <!-- Appointment Creation Form -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <form id="appointmentForm" action="/pfaa/manage/controllers/appointmentController.php" method="POST">
                                <!-- CSRF Token -->
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <!-- Client Selection -->
                                <div class="mb-3">
                                    <label for="client_id" class="form-label">Client</label>
                                    <select class="form-select" id="client_id" name="client_id" required>
                                        <option value="">Sélectionner un client</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value="<?php echo $client['id']; ?>">
                                                <?php echo htmlspecialchars($client['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Location Selection -->
                                <div class="mb-3">
                                    <label for="location_id" class="form-label">Lieu</label>
                                    <select class="form-select" id="location_id" name="location_id" required>
                                        <option value="">Sélectionner un lieu</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?php echo $location['id']; ?>">
                                                <?php echo htmlspecialchars($location['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Date Selection -->
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" required 
                                           min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <!-- Time Selection -->
                                <div class="mb-3">
                                    <label for="hour" class="form-label">Heure</label>
                                    <input type="time" class="form-control" id="hour" name="hour" required 
                                           min="08:00" max="20:00" step="1800">
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary">Créer le rendez-vous</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Rendez-vous d'aujourd'hui</h5>
                            <?php if (!empty($today_appointments)): ?>
                                <div class="list-group">
                                    <?php foreach ($today_appointments as $appointment): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($appointment['client_name']); ?></h6>
                                                <small><?php echo htmlspecialchars($appointment['hour']); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($appointment['location_name']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Aucun rendez-vous aujourd'hui</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upcoming Appointments -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Rendez-vous à venir</h5>
                            <?php if (!empty($upcoming_appointments)): ?>
                                <div class="list-group">
                                    <?php foreach ($upcoming_appointments as $appointment): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($appointment['client_name']); ?></h6>
                                                <small><?php echo date('d/m/Y', strtotime($appointment['date'])); ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <?php echo htmlspecialchars($appointment['hour']); ?> - 
                                                <?php echo htmlspecialchars($appointment['location_name']); ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Aucun rendez-vous à venir</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Form Validation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('appointmentForm');
    const dateInput = document.getElementById('date');
    const hourInput = document.getElementById('hour');

    // Set minimum date to today
    dateInput.min = new Date().toISOString().split('T')[0];

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form inputs
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        // Submit form
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rendez-vous créé avec succès!');
                window.location.reload();
            } else {
                alert(data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la création du rendez-vous');
        });
    });
});
</script>

<?php include '../partials/footer.php'; ?>
