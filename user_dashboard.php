<?php
session_start();
require 'config/db.php';
$user_id = $_SESSION['user']['id'];

// Get selected week or default to today
$selectedDate = isset($_GET['week']) ? new DateTime($_GET['week']) : new DateTime();
$startDate = clone $selectedDate;
$startDate->modify('monday this week');

// Calculate week range
$weekDates = [];
$today = new DateTime();
$today->setTime(0, 0, 0); // Set time to midnight for accurate comparison

for ($i = 0; $i < 7; $i++) {
    $currentDay = (clone $startDate)->modify("+$i days");
    $weekDates[] = $currentDay;
}

$prevWeek = (clone $startDate)->modify('-7 days')->format('Y-m-d');
$nextWeek = (clone $startDate)->modify('+7 days')->format('Y-m-d');

// Get active locations
$locations = $pdo->query("SELECT * FROM locations WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

// Preload user's appointments for the current week with location name and time
$userAppointmentsThisWeek = [];
$stmtUserAppointments = $pdo->prepare("
    SELECT a.date, TIME_FORMAT(a.hour, '%H:%i') as time, l.name as location_name
    FROM appointments a
    JOIN locations l ON a.location_id = l.id
    WHERE a.user_id = :user_id
    AND a.date BETWEEN :start_date AND :end_date
");
$stmtUserAppointments->bindParam(':user_id', $user_id);
$stmtUserAppointments->bindParam(':start_date', $startDate->format('Y-m-d'));
$stmtUserAppointments->bindParam(':end_date', (clone $startDate)->modify('+6 days')->format('Y-m-d'));
$stmtUserAppointments->execute();
$userAppointments = $stmtUserAppointments->fetchAll(PDO::FETCH_ASSOC);

foreach ($userAppointments as $appointment) {
    $userAppointmentsThisWeek[$appointment['date']] = [
        'time' => $appointment['time'],
        'location_name' => htmlspecialchars($appointment['location_name'])
    ];
}

// Preload booked slots for the current week and all locations
$bookedSlots = [];
foreach ($locations as $loc) {
    $locationId = $loc['id'];
    foreach ($weekDates as $day) {
        $date = $day->format('Y-m-d');
        $stmt = $pdo->prepare("SELECT TIME_FORMAT(hour, '%H:%i') as time FROM appointments WHERE location_id = :location_id AND date = :date");
        $stmt->bindParam(':location_id', $locationId);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $bookedSlots[$locationId][$date] = $results;
    }
}

?>
<?php include 'partials/header.php'; ?>
<link rel="stylesheet" href="./config/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<main class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 text-primary mb-0">Bonjour <?= htmlspecialchars($_SESSION['user']['name']) ?> 👋</h1>
            <small class="text-muted">Semaine du <?= $startDate->format('d/m/Y') ?></small>
        </div>
        <div class="btn-group">
            <a href="?week=<?= $prevWeek ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-left"></i></a>
            <a href="?week=<?= date('Y-m-d') ?>" class="btn btn-primary btn-sm mx-2">Aujourd'hui</a>
            <a href="?week=<?= $nextWeek ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <div class="card border-primary shadow">
        <div class="card-body">
            <ul class="nav nav-tabs" role="tablist">
                <?php foreach ($locations as $i => $loc): ?>
                    <li class="nav-item">
                        <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" id="location-tab-<?= $loc['id'] ?>" data-bs-toggle="tab" data-bs-target="#loc<?= $loc['id'] ?>" type="button" role="tab" aria-controls="loc<?= $loc['id'] ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                            <?= htmlspecialchars($loc['name']) ?>
                            <?php
                            // Check if the user has an appointment today at *this* location
                            foreach ($userAppointmentsThisWeek as $bookedDate => $appointmentInfo) {
                                if ($bookedDate == date('Y-m-d') && $appointmentInfo['location_name'] == htmlspecialchars($loc['name'])) {
                                    echo ' <span class="badge bg-info">Réservé ici</span>';
                                    break;
                                }
                            }
                            ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content mt-3">
                <?php foreach ($locations as $i => $loc): ?>
                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="loc<?= $loc['id'] ?>" role="tabpanel" aria-labelledby="location-tab-<?= $loc['id'] ?>">
                    <?php
                    // Display a reminder if the user has an appointment today at a *different* location
                    if (isset($userAppointmentsThisWeek[date('Y-m-d')])) {
                        $bookedInfo = $userAppointmentsThisWeek[date('Y-m-d')];
                        if ($bookedInfo['location_name'] != htmlspecialchars($loc['name'])) {
                            echo '<div class="alert alert-warning" role="alert">';
                            echo 'Vous avez déjà une réservation aujourd\'hui à ' . $bookedInfo['location_name'] . ' à ' . $bookedInfo['time'] . '.';
                            echo '</div>';
                        }
                    }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Heure</th>
                                    <?php foreach ($weekDates as $day): ?>
                                        <th class="text-center">
                                            <?= $day->format('D<br>d/m') ?>
                                            <?php if ($day < $today): ?>
                                                <br><span class="text-muted">(Passé)</span>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($h = 8; $h <= 19; $h++):
                                    $hourFormatted = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                                ?>
                                    <tr>
                                        <th class="align-middle"><?= $hourFormatted ?></th>
                                        <?php foreach ($weekDates as $day):
                                            $dateFormatted = $day->format('Y-m-d');
                                            $isPastDay = ($day < $today);
                                            $hasBookedOnThisDay = in_array($dateFormatted, array_keys($userAppointmentsThisWeek));
                                            $isBookedAtThisLocation = in_array(substr($hourFormatted, 0, 5), $bookedSlots[$loc['id']][$dateFormatted] ?? []);

                                        ?>
                                            <td class="text-center p-1 <?= $isPastDay || $hasBookedOnThisDay ? 'bg-light text-muted' : '' ?>">
                                                <?php if ($isPastDay): ?>
                                                    <span class="text-muted">Non disponible</span>
                                                <?php elseif ($hasBookedOnThisDay): ?>
                                                    <?php if (isset($userAppointmentsThisWeek[date('Y-m-d')])): ?>
                                                        <?php $bookedInfo = $userAppointmentsThisWeek[date('Y-m-d')]; ?>
                                                        <span class="text-danger">Réservé</span><br>
                                                        <small>à: <?= $bookedInfo['location_name'] ?> (<?= $bookedInfo['time'] ?>)</small>
                                                    <?php else: ?>
                                                        <span class="text-danger">Réservé aujourd'hui</span>
                                                    <?php endif; ?>
                                                <?php elseif (!$isBookedAtThisLocation): ?>
                                                    <form method="POST" action="controllers/appointmentController.php">
                                                        <input type="hidden" name="date" value="<?= $dateFormatted ?>">
                                                        <input type="hidden" name="hour" value="<?= $hourFormatted ?>:00">
                                                        <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                                                        <button type="submit" name="book_appointment" class="btn btn-sm btn-outline-primary w-100">
                                                            Réserver
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Réservé</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'partials/footer.php'; ?>