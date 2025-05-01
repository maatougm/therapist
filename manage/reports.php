<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is either admin or kine
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    header('Location: ' . url('login.php'));
    exit;
}

// Get appointment ID from URL
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$action = $_GET['action'] ?? 'view';

// Get appointment details
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.name as client_name, 
           l.name as location_name,
           r.therapist_id as report_therapist_id,
           rt.name as report_therapist_name
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    JOIN locations l ON a.location_id = l.id
    LEFT JOIN reports r ON a.id = r.appointment_id
    LEFT JOIN users rt ON r.therapist_id = rt.id
    WHERE a.id = ? AND " . ($_SESSION['role'] === 'admin' ? "1=1" : "r.therapist_id = ?")
);
$params = [$appointment_id];
if ($_SESSION['role'] !== 'admin') {
    $params[] = $_SESSION['id'];
}
$stmt->execute($params);
$appointment = $stmt->fetch();

if (!$appointment) {
    header('Location: ' . url('kine_dashboard.php'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $errors = [];

    if (empty($content)) {
        $errors[] = "Le contenu du rapport est requis";
    }

    if (empty($errors)) {
        try {
            // Check if report already exists
            $stmt = $pdo->prepare("SELECT id FROM reports WHERE appointment_id = ?");
            $stmt->execute([$appointment_id]);
            $existing_report = $stmt->fetch();

            if ($existing_report) {
                // Update existing report
                $stmt = $pdo->prepare("UPDATE reports SET content = ?, updated_at = NOW() WHERE appointment_id = ?");
                $stmt->execute([$content, $appointment_id]);
            } else {
                // Create new report
                $stmt = $pdo->prepare("
                    INSERT INTO reports (appointment_id, therapist_id, content, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$appointment_id, $_SESSION['id'], $content]);
            }

            $_SESSION['success'] = "Rapport enregistré avec succès";
            header('Location: ' . url('manage/reports.php') . '?appointment_id=' . $appointment_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'enregistrement du rapport: " . $e->getMessage();
        }
    }
}

// Get existing report if any
$stmt = $pdo->prepare("SELECT * FROM reports WHERE appointment_id = ?");
$stmt->execute([$appointment_id]);
$report = $stmt->fetch();

// Include header
include __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Rapport de consultation</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= url('kine_dashboard.php') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour au tableau de bord
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="report-container">
                <div class="report-header">
                    <h5 class="card-title mb-0">Informations du rendez-vous</h5>
                </div>
                <div class="report-content">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Client:</strong> <?= htmlspecialchars($appointment['client_name']) ?></p>
                            <p><strong>Lieu:</strong> <?= htmlspecialchars($appointment['location_name']) ?></p>
                            <?php if (!empty($appointment['report_therapist_name'])): ?>
                                <p><strong>Rapport écrit par:</strong> <?= htmlspecialchars($appointment['report_therapist_name']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date:</strong> <?= date('d/m/Y', strtotime($appointment['date'])) ?></p>
                            <p><strong>Heure:</strong> <?= date('H:i', strtotime($appointment['hour'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-container">
                <div class="report-header">
                    <h5 class="card-title mb-0">Rapport de consultation</h5>
                </div>
                <div class="report-content">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="content" class="report-label">Contenu du rapport</label>
                            <textarea class="report-textarea" id="content" name="content" rows="10" required><?= htmlspecialchars($report['content'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?> 