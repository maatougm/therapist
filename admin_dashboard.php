<?php
// Start the session if it's not already started
// This is often done in a shared header or config file, but ensure it's done before accessing $_SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Get current view (weekly or monthly)
$view = isset($_GET['view']) ? $_GET['view'] : 'weekly';

// Get user data from session
$loggedInUserId = $_SESSION['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$loggedInUserId]);
$user = $stmt->fetch();

// Check if user was found and set the session role
if ($user && isset($user['role'])) {
    $_SESSION['role'] = $user['role'];
} else {
    // Handle case where user is not found
    header("Location: login.php");
    exit();
}

// Get current week dates
$today = new DateTime();
$weekStart = clone $today;
$weekStart->modify('monday this week');
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

// Get current month dates
$monthStart = new DateTime('first day of this month');
$monthEnd = new DateTime('last day of this month');

// Fetch appointments based on view (rest of your existing fetching logic)
if ($view === 'weekly') {
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as client_name, l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.date BETWEEN ? AND ?
        ORDER BY a.date ASC, a.hour ASC
    ");
    $stmt->execute([$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
} else {
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as client_name, l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.date BETWEEN ? AND ?
        ORDER BY a.date ASC, a.hour ASC
    ");
    $stmt->execute([$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]);
}
$appointments = $stmt->fetchAll();

// Fetch all users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY name");
$stmt->execute();
$users = $stmt->fetchAll();

// Fetch all therapists
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'therapist' ORDER BY name");
$stmt->execute();
$therapists = $stmt->fetchAll();

// Fetch all locations
$stmt = $pdo->prepare("SELECT * FROM locations ORDER BY name");
$stmt->execute();
$locations = $stmt->fetchAll();

// Prepare data for charts
$hourlyStats = array_fill(8, 11, 0); // 8h to 18h
$weeklyStats = array_fill(0, 7, 0);
$monthlyStats = array_fill(1, 31, 0);

foreach ($appointments as $appointment) {
    // Hourly stats
    $hour = (int)substr($appointment['hour'], 0, 2);
    if (isset($hourlyStats[$hour])) { // Check if the hour key exists (8-18)
        $hourlyStats[$hour]++;
    }

    // Weekly stats
    $dayOfWeek = (int)date('w', strtotime($appointment['date'])); // 0 for Sunday, 6 for Saturday
     // Adjust for 0-based Monday start if needed, but array_fill(0, 7, 0) matches 0-6 days
    $weeklyStats[$dayOfWeek]++;

    // Monthly stats
    $dayOfMonth = (int)date('j', strtotime($appointment['date']));
    $monthlyStats[$dayOfMonth]++;
}

// Include header
include 'partials/header.php';

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <?php include 'partials/sidebar.php'; ?> </div>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
             <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Tableau de bord administrateur</h1>
                 <div class="btn-toolbar mb-2 mb-md-0">
                     <div class="btn-group me-2">
                        <a href="?view=weekly" class="btn btn-sm <?= $view === 'weekly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            Vue Hebdomadaire
                        </a>
                        <a href="?view=monthly" class="btn btn-sm <?= $view === 'monthly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                             Vue Mensuelle
                        </a>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                         <div class="card-body">
                            <h5 class="card-title">Utilisateurs</h5>
                            <p class="card-text display-6"><?= count($users) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Kinésithérapeutes</h5>
                            <p class="card-text display-6"><?= count($therapists) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                         <div class="card-body">
                            <h5 class="card-title">RDV <?= $view === 'weekly' ? 'cette semaine' : 'ce mois' ?></h5>
                            <p class="card-text display-6"><?= count($appointments) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Lieux</h5>
                            <p class="card-text display-6"><?= count($locations) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                             <h5 class="mb-0">Heures de pointe</h5>
                        </div>
                        <div class="card-body">
                             <canvas id="hourlyChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">RDV par jour <?= $view === 'weekly' ? 'de la semaine' : 'du mois' ?></h5>
                        </div>
                        <div class="card-body">
                             <canvas id="<?= $view === 'weekly' ? 'weeklyChart' : 'monthlyChart' ?>" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Hourly Chart
new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(function($h) { return sprintf('%02dh', $h); }, array_keys($hourlyStats))) ?>,
        datasets: [{
            label: 'Nombre de RDV',
            data: <?= json_encode(array_values($hourlyStats)) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Weekly/Monthly Chart
new Chart(document.getElementById('<?= $view === 'weekly' ? 'weeklyChart' : 'monthlyChart' ?>'), {
    type: 'bar',
    data: {
        labels: <?= $view === 'weekly' ?
            json_encode(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']) :
            json_encode(range(1, 31))
        ?>,
        datasets: [{
            label: 'Nombre de RDV',
            data: <?= $view === 'weekly' ? json_encode($weeklyStats) : json_encode($monthlyStats) ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.5)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>