<?php

require 'config/db.php';
session_start();
include 'partials/header.php';

// // Ensure the user is logged in as a therapist
// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'therapist') {
//     header('Location: login.php'); // Redirect if not a therapist
//     exit();
// }

$kine_id = $_SESSION['user']['id'];
$selectedWeek = $_GET['week'] ?? date('Y-m-d');
$startOfWeek = new DateTime($selectedWeek);
$startOfWeek->modify('monday this week');
$today = (new DateTime())->format('Y-m-d');
$now = new DateTime();
$currentTimeFormatted = $now->format('H:i');

$prevWeek = (clone $startOfWeek)->modify('-7 days')->format('Y-m-d');
$nextWeek = (clone $startOfWeek)->modify('+7 days')->format('Y-m-d');

// Get therapist locations
$stmt = $pdo->prepare("SELECT l.id, l.name FROM therapist_locations tl JOIN locations l ON l.id = tl.location_id WHERE tl.therapist_id = ? AND l.status = 'active' ORDER BY l.name");
$stmt->execute([$kine_id]);
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get appointments for the week
$appointmentsByLocationAndDay = [];
for ($i = 0; $i < 7; $i++) {
    $currentDay = (clone $startOfWeek)->modify("+$i days")->format('Y-m-d');
    foreach ($locations as $loc) {
        $stmtA = $pdo->prepare("
            SELECT a.id as appointment_id, TIME_FORMAT(a.hour, '%H:%i') as hour_formatted, a.date, u.id as user_id, u.name
            FROM appointments a
            JOIN users u ON u.id = a.user_id
            WHERE a.date = ? AND a.location_id = ?
            ORDER BY a.hour
        ");
        $stmtA->execute([$currentDay, $loc['id']]);
        $appointmentsByLocationAndDay[$loc['id']][$currentDay] = $stmtA->fetchAll();
    }
}

// Get the next hour's appointments across all locations for today
$nextHourStart = $now->format('H:00:00');
$nextHourEnd = $now->modify('+1 hour')->format('H:00:00');
$stmtNextHour = $pdo->prepare("
    SELECT l.name as location_name, TIME_FORMAT(a.hour, '%H:%i') as hour_formatted, u.name as patient_name, u.id as user_id
    FROM appointments a
    JOIN locations l ON a.location_id = l.id
    JOIN users u ON a.user_id = u.id
    WHERE a.date = ? AND a.hour >= ? AND a.hour < ? AND a.location_id IN (SELECT location_id FROM therapist_locations WHERE therapist_id = ?)
    ORDER BY a.hour, l.name
");
$stmtNextHour->execute([$today, $nextHourStart, $nextHourEnd, $kine_id]);
$nextHourAppointments = $stmtNextHour->fetchAll(PDO::FETCH_ASSOC);

?>
<link rel="stylesheet" href="./config/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 bg-light p-3">
            <?php include 'partials/sidebar.php'; ?>
        </div>

        <div class="col-md-9 py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4>Rendez-vous</h4>
                    <small class="text-muted">Semaine du <?= $startOfWeek->format('d/m/Y') ?></small>
                </div>
                <div>
                    <a href="?week=<?= $prevWeek ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Précédent</a>
                    <a href="?week=<?= date('Y-m-d') ?>" class="btn btn-sm btn-primary mx-2">Aujourd'hui</a>
                    <a href="?week=<?= $nextWeek ?>" class="btn btn-sm btn-outline-primary">Suivant <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <?php if (!empty($nextHourAppointments)): ?>
                <div class="card mb-3 border-primary">
                    <div class="card-header bg-primary text-white">
                        Prochaines rendez-vous (<?= (new DateTime())->format('H:00') ?> - <?= (new DateTime())->modify('+1 hour')->format('H:00') ?>)
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($nextHourAppointments as $appt): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-geo-alt-fill text-info me-2"></i> <?= htmlspecialchars($appt['location_name']) ?>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary view-client-details" data-client-id="<?= $appt['user_id'] ?>" data-bs-toggle="modal" data-bs-target="#clientDetailsModal">
                                            <i class="bi bi-person-fill me-1"></i> <?= htmlspecialchars($appt['patient_name']) ?> (<?= $appt['hour_formatted'] ?>)
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    Aucun rendez-vous dans l'heure à venir.
                </div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-2">
                <?php foreach ($locations as $i => $loc): ?>
                    <li class="nav-item">
                        <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#loc-<?= $loc['id'] ?>">
                            <?= htmlspecialchars($loc['name']) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content">
                <?php foreach ($locations as $i => $loc): ?>
                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="loc-<?= $loc['id'] ?>">
                        <ul class="nav nav-pills justify-content-center mb-3">
                            <?php for ($d = 0; $d < 7; $d++):
                                $currentDay = (clone $startOfWeek)->modify("+$d days");
                                $dayStr = $currentDay->format('Y-m-d');
                                ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= $dayStr === $today ? 'active' : '' ?>" data-bs-toggle="pill" href="#day-<?= $loc['id'] ?>-<?= $d ?>">
                                        <?= $currentDay->format('D d/m') ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>

                        <div class="tab-content">
                            <?php for ($d = 0; $d < 7; $d++):
                                $currentDay = (clone $startOfWeek)->modify("+$d days");
                                $dayStr = $currentDay->format('Y-m-d');
                                $appointments = $appointmentsByLocationAndDay[$loc['id']][$dayStr] ?? [];
                                $appointmentsByHour = [];
                                foreach ($appointments as $appt) {
                                    $appointmentsByHour[$appt['hour_formatted']][] = $appt;
                                }
                                ?>
                                <div class="tab-pane fade <?= $dayStr === $today ? 'show active' : '' ?>" id="day-<?= $loc['id'] ?>-<?= $d ?>">
                                    <?php if (empty($appointmentsByLocationAndDay[$loc['id']][$dayStr])): ?>
                                        <div class="alert alert-info" role="alert">
                                            Aucun rendez-vous pour cette journée.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Heure</th>
                                                        <th>Patient(s)</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php for ($h = 8; $h <= 19; $h++):
                                                        $hourFormatted = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                                                        $patients = $appointmentsByHour[$hourFormatted] ?? [];
                                                        ?>
                                                        <tr>
                                                            <td><?= $hourFormatted ?></td>
                                                            <td>
                                                                <?php if ($patients): ?>
                                                                    <?php foreach ($patients as $p): ?>
                                                                        <button type="button" class="btn btn-outline-primary btn-sm me-1 view-client-details" data-client-id="<?= $p['user_id'] ?>" data-bs-toggle="modal" data-bs-target="#clientDetailsModal">
                                                                            <i class="bi bi-person-fill"></i> <?= htmlspecialchars($p['name']) ?>
                                                                        </button>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Aucun</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php foreach ($patients as $p): ?>
                                                                    <form method="POST" action="controllers/appointmentController.php" class="d-inline">
                                                                        <input type="hidden" name="appointment_id" value="<?= $p['appointment_id'] ?>">
                                                                        <button type="submit" name="cancel_appointment" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Annuler</button>
                                                                    </form>
                                                                <?php endforeach; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endfor; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="clientDetailsModalLabel">Détails du client</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body" id="clientDetailsContent">
                            </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const clientDetailButtons = document.querySelectorAll('.view-client-details');
                    const modalContent = document.getElementById('clientDetailsContent');
                    const clientDetailsModal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));

                    clientDetailButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const clientId = this.getAttribute('data-client-id');
                            modalContent.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div>'; // Show loading spinner
                            fetch('manage/getClientDetails.php?id=' + clientId)
                                .then(response => response.text())
                                .then(data => {
                                    modalContent.innerHTML = data;
                                })
                                .catch(error => {
                                    modalContent.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails.</div>';
                                    console.error('Error fetching client details:', error);
                                });
                            clientDetailsModal.show();
                        });
                    });
                });
            </script>

        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>