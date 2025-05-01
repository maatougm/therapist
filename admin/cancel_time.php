<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . url('login.php'));
    exit;
}

// Handle Update Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cancel_time'])) {
        $cancel_time = intval($_POST['cancel_time'] ?? 24);
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('cancel_time', ?) 
                              ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$cancel_time, $cancel_time]);
    }
    
    if (isset($_POST['add_special_day'])) {
        $date = $_POST['special_date'];
        $start_time = $_POST['start_time'] ?? '00:00';
        $end_time = $_POST['end_time'] ?? '23:59';
        $is_whole_day = isset($_POST['whole_day']) ? 1 : 0;
        $location_id = $_POST['location_id'] ?? null;
        
        if (!$location_id) {
            $error = "Veuillez sélectionner un cabinet";
        } else {
            $stmt = $pdo->prepare("INSERT INTO special_days (date, start_time, end_time, is_whole_day, location_id) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$date, $start_time, $end_time, $is_whole_day, $location_id]);
        }
    }
    
    if (isset($_POST['delete_special_day'])) {
        $day_id = intval($_POST['day_id']);
        $stmt = $pdo->prepare("DELETE FROM special_days WHERE id = ?");
        $stmt->execute([$day_id]);
    }
    
    header("Location: " . url('admin/cancel_time.php') . "?updated=1");
    exit;
}

// Get current settings
$cancel_time = $pdo->query("SELECT value FROM settings WHERE name = 'cancel_time'")->fetchColumn() ?: 24;

// Get special days with location names
$special_days = $pdo->query("
    SELECT sd.*, l.name as location_name 
    FROM special_days sd 
    LEFT JOIN locations l ON sd.location_id = l.id 
    ORDER BY sd.date DESC
")->fetchAll();

// Get locations for dropdown
$locations = $pdo->query("SELECT id, name FROM locations WHERE status = 'active' ORDER BY name")->fetchAll();

// Include header
include __DIR__ . '/../partials/header.php';
?>

<style>
    .time-input {
        max-width: 200px;
    }
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .card-header {
        background-color:rgb(27, 53, 60);
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }
    .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .special-day-item {
        background-color: #f8f9fa;
        border-radius: 0.375rem;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
    }
    .delete-form {
        display: inline;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Paramètres d'annulation</h1>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Paramètres mis à jour avec succès.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- General Cancellation Time -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Délai d'annulation général
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Délai minimum pour annuler (en heures)</label>
                            <div class="input-group time-input">
                                <input type="number" name="cancel_time" class="form-control" 
                                       value="<?= htmlspecialchars($cancel_time) ?>" 
                                       min="1" max="72" required>
                                <span class="input-group-text">heures</span>
                            </div>
                            <div class="form-text">
                                Les patients ne pourront pas annuler leur rendez-vous moins de 
                                <span class="fw-bold"><?= $cancel_time ?></span> heures avant l'heure du rendez-vous.
                            </div>
                        </div>
                        <button type="submit" name="update_cancel_time" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Special Days -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Jours spéciaux
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Cabinet</label>
                                    <select name="location_id" class="form-select" required>
                                        <option value="">Sélectionner un cabinet</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="special_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Heure de début</label>
                                    <input type="time" name="start_time" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Heure de fin</label>
                                    <input type="time" name="end_time" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" name="whole_day" class="form-check-input" id="whole_day">
                                        <label class="form-check-label" for="whole_day">Journée entière</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_special_day" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Ajouter
                        </button>
                    </form>

                    <h6 class="mb-3">Jours spéciaux configurés</h6>
                    <?php if (empty($special_days)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun jour spécial n'a été configuré.
                        </div>
                    <?php else: ?>
                        <?php foreach ($special_days as $day): ?>
                            <div class="special-day-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if (!empty($day['location_name'])): ?>
                                            <strong><?= htmlspecialchars($day['location_name']) ?></strong> - 
                                        <?php endif; ?>
                                        <strong><?= date('d/m/Y', strtotime($day['date'])) ?></strong>
                                        <?php if (!$day['is_whole_day']): ?>
                                            <span class="text-muted ms-2">
                                                (<?= $day['start_time'] ?> - <?= $day['end_time'] ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted ms-2">(Journée entière)</span>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" class="delete-form" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce jour spécial ?');">
                                        <input type="hidden" name="day_id" value="<?= $day['id'] ?>">
                                        <button type="submit" name="delete_special_day" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle time inputs based on whole day checkbox
    const wholeDayCheckbox = document.getElementById('whole_day');
    const timeInputs = document.querySelectorAll('input[type="time"]');
    
    wholeDayCheckbox.addEventListener('change', function() {
        timeInputs.forEach(input => {
            input.disabled = this.checked;
            if (this.checked) {
                input.value = '';
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>  