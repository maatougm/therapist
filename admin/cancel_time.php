<?php
session_start();
require '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get current cancellation time limit
$cancelLimit = $pdo->query("SELECT value FROM settings WHERE name = 'cancel_limit_hours'")->fetchColumn();

// Handle Update Cancel Time
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cancel_limit'])) {
    $limit = intval($_POST['cancel_limit'] ?? 24);
    
    // Validate input
    if ($limit < 1 || $limit > 72) {
        header("Location: cancel_time.php?error=invalid_time");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'cancel_limit_hours'");
    $stmt->execute([$limit]);

    header("Location: cancel_time.php?updated=1");
    exit;
}

// Now include the header after all header operations are done
include '../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <?php include '../partials/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Délai d'annulation</h1>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">Le délai d'annulation a été mis à jour avec succès.</div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_time'): ?>
                <div class="alert alert-danger">Le délai doit être compris entre 1 et 72 heures.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Paramètres d'annulation</h5>
                    <p class="card-text">
                        Définissez le délai minimum avant lequel un patient peut annuler son rendez-vous sans pénalité.
                        Ce délai s'applique à tous les rendez-vous du système.
                    </p>
                    
                    <form method="POST" class="mt-4">
                        <div class="mb-3">
                            <label for="cancel_limit" class="form-label">Délai minimum pour annuler (en heures)</label>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="number" name="cancel_limit" id="cancel_limit" 
                                       class="form-control" value="<?= $cancelLimit ?>" 
                                       min="1" max="72" required>
                                <span class="input-group-text">heures</span>
                            </div>
                            <div class="form-text">
                                Les patients ne pourront pas annuler leur rendez-vous moins de 
                                <span class="fw-bold"><?= $cancelLimit ?></span> heures avant l'heure du rendez-vous.
                            </div>
                        </div>
                        <button type="submit" name="update_cancel_limit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Mettre à jour
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../partials/footer.php'; ?>  