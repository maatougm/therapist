<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session variables
error_log("Session variables: " . print_r($_SESSION, true));

// // Check if user is logged in
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'client') {
//     error_log("User not logged in or wrong role");
//     header('Location: login.php');
//     exit;
// }

// Include header
include 'partials/header.php';

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=kene_therapy;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données.");
}

// Get user's appointments with reports
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id as appointment_id,
            a.date,
            a.hour,
            a.status,
            l.name as location_name,
            r.content as report_content,
            r.created_at as report_date,
            CONCAT(u.first_name, ' ', u.last_name) as kine_name
        FROM appointments a
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN reports r ON a.id = r.appointment_id
        LEFT JOIN users u ON r.kine_id = u.id
        WHERE a.user_id = ?
        ORDER BY a.date DESC, a.hour DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['id']]);
    $appointments = $stmt->fetchAll();
    error_log("Number of appointments found: " . count($appointments));
} catch (PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $appointments = [];
}
?>

<!-- Include Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Include Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <?php include 'partials/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mes Rendez-vous</h1>
            </div>

            <!-- Debug Info (only show if no appointments) -->
            <?php if (empty($appointments)): ?>
                <div class="alert alert-warning">
                    <p>Vous n'avez pas encore de rendez-vous.</p>
                    <p>User ID: <?= $_SESSION['id'] ?? 'Not set' ?></p>
                </div>
            <?php else: ?>
                <!-- Appointments List -->
                <div class="row">
                    <?php foreach ($appointments as $appointment): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        Rendez-vous du <?= date('d/m/Y', strtotime($appointment['date'])) ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Heure:</strong> <?= substr($appointment['hour'], 0, 5) ?></p>
                                        <p class="mb-1"><strong>Lieu:</strong> <?= htmlspecialchars($appointment['location_name']) ?></p>
                                        <p class="mb-1">
                                            <strong>Statut:</strong>
                                            <span class="badge bg-<?= $appointment['status'] === 'confirmed' ? 'success' : ($appointment['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst($appointment['status']) ?>
                                            </span>
                                        </p>
                                    </div>

                                    <?php if ($appointment['report_content']): ?>
                                        <div class="report-section">
                                            <h6 class="mb-2">Rapport de séance</h6>
                                            <div class="report-content">
                                                <?= nl2br(htmlspecialchars($appointment['report_content'])) ?>
                                            </div>
                                            <div class="report-meta mt-2">
                                                <small class="text-muted">
                                                    Rédigé par <?= htmlspecialchars($appointment['kine_name']) ?> 
                                                    le <?= date('d/m/Y H:i', strtotime($appointment['report_date'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            Aucun rapport disponible pour cette séance.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'partials/footer.php'; ?> 