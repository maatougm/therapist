<?php
/**
 * User Dashboard
 * 
 * Displays appointment information and calendar for regular users.
 * Includes upcoming appointments, past appointments, and a weekly calendar view.
 */

// Include database configuration
require_once 'config/db.php';

// Get user data directly from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([1]); // Using a default ID since we removed auth
$user = $stmt->fetch();

// Get current week dates
$today = new DateTime();
$weekStart = clone $today;
$weekStart->modify('monday this week');
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

// Fetch user's appointments for this week
$stmt = $pdo->prepare("
    SELECT a.*, u.name as therapist_name, l.name as location_name
    FROM appointments a
    JOIN users u ON a.therapist_id = u.id
    JOIN locations l ON a.location_id = l.id
    WHERE a.user_id = ? AND a.date BETWEEN ? AND ?
    ORDER BY a.date, a.hour
");
$stmt->execute([1, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
$appointments = $stmt->fetchAll();

// Fetch all appointments for statistics
$stmt = $pdo->prepare("
    SELECT a.*, u.name as therapist_name, l.name as location_name
    FROM appointments a
    JOIN users u ON a.therapist_id = u.id
    JOIN locations l ON a.location_id = l.id
    WHERE a.user_id = ?
    ORDER BY a.date, a.hour
");
$stmt->execute([1]);
$allAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate upcoming and past appointments
$upcomingAppointments = [];
$pastAppointments = [];
foreach ($allAppointments as $appointment) {
    if ($appointment['date'] < date('Y-m-d')) {
        $pastAppointments[] = $appointment;
    } else {
        $upcomingAppointments[] = $appointment;
    }
}

// Get cancel time limit from settings
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'cancel_limit_hours'");
$stmt->execute();
$cancelLimitHours = (int)$stmt->fetchColumn();

?>

<!-- Main Container -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <?php include 'partials/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Bienvenue, <?= htmlspecialchars($user['name']) ?></h1>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Prochains RDV</h5>
                            <p class="card-text display-6"><?= count($upcomingAppointments) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">RDV passés</h5>
                            <p class="card-text display-6"><?= count($pastAppointments) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">RDV cette semaine</h5>
                            <p class="card-text display-6"><?= count($appointments) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Calendar -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Calendrier de la semaine</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <?php for ($d = 0; $d < 7; $d++): ?>
                                        <?php $day = clone $weekStart; $day->modify("+$d days"); ?>
                                        <th><?= $day->format('D d/m') ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($h = 8; $h <= 18; $h++): ?>
                                    <tr>
                                        <td><?= sprintf('%02dh', $h) ?></td>
                                        <?php for ($d = 0; $d < 7; $d++): ?>
                                            <?php
                                            $day = clone $weekStart;
                                            $day->modify("+$d days");
                                            $date = $day->format('Y-m-d');
                                            $hour = sprintf('%02d:00:00', $h);
                                            $appointment = null;
                                            foreach ($appointments as $appt) {
                                                if ($appt['date'] === $date && $appt['hour'] === $hour) {
                                                    $appointment = $appt;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <td>
                                                <?php if ($appointment): ?>
                                                    <div class="appointment-slot">
                                                        <span class="badge bg-primary">
                                                            <?= htmlspecialchars($appointment['therapist_name']) ?>
                                                        </span>
                                                        <small class="d-block">
                                                            <?= htmlspecialchars($appointment['location_name']) ?>
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Prochains rendez-vous</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcomingAppointments)): ?>
                        <div class="list-group">
                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($appointment['therapist_name']) ?></h6>
                                        <small><?= date('d/m/Y', strtotime($appointment['date'])) ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <?= substr($appointment['hour'], 0, 5) ?> - 
                                        <?= htmlspecialchars($appointment['location_name']) ?>
                                    </p>
                                    <?php
                                    $apptTime = strtotime($appointment['date'] . ' ' . $appointment['hour']);
                                    if (($apptTime - time()) / 3600 > $cancelLimitHours):
                                    ?>
                                        <form method="POST" action="controllers/cancelAppointment.php" class="mt-2">
                                            <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Annuler le rendez-vous
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucun rendez-vous à venir</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Past Appointments -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rendez-vous passés</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($pastAppointments)): ?>
                        <div class="list-group">
                            <?php foreach ($pastAppointments as $appointment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($appointment['therapist_name']) ?></h6>
                                        <small><?= date('d/m/Y', strtotime($appointment['date'])) ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <?= substr($appointment['hour'], 0, 5) ?> - 
                                        <?= htmlspecialchars($appointment['location_name']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucun rendez-vous passé</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'partials/footer.php'; ?>