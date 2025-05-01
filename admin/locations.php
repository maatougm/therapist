<?php
session_start();
require '../config/db.php';
require '../helpers/errorHandler.php';

// Handle freeze/unfreeze actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['freeze_location'])) {
        $location_id = $_POST['location_id'] ?? null;
        if (!$location_id) {
            redirectWithError('locations.php', "Veuillez sélectionner un cabinet");
        } else {
            try {
                $pdo->beginTransaction();
                
                // Update location status
                $stmt = $pdo->prepare("UPDATE locations SET status = 'frozen' WHERE id = ?");
                $stmt->execute([$location_id]);
                
                // Cancel today's appointments
                $stmt = $pdo->prepare("
                    UPDATE appointments 
                    SET status = 'cancelled'
                    WHERE location_id = ? 
                    AND date = CURDATE()
                    AND hour <= CURTIME()
                ");
                $stmt->execute([$location_id]);
                
                // Delete future appointments
                $stmt = $pdo->prepare("
                    DELETE FROM appointments 
                    WHERE location_id = ? 
                    AND (
                        date > CURDATE() 
                        OR (date = CURDATE() AND hour > CURTIME())
                    )
                ");
                $stmt->execute([$location_id]);
                
                $pdo->commit();
                redirectWithSuccess('locations.php', "Le cabinet a été gelé avec succès");
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error freezing location: " . $e->getMessage());
                redirectWithError('locations.php', "Une erreur est survenue lors du gel du cabinet");
            }
        }
    } elseif (isset($_POST['unfreeze_location'])) {
        $location_id = $_POST['location_id'] ?? null;
        if (!$location_id) {
            redirectWithError('locations.php', "Veuillez sélectionner un cabinet");
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE locations SET status = 'active' WHERE id = ?");
                $stmt->execute([$location_id]);
                redirectWithSuccess('locations.php', "Le cabinet a été réactivé avec succès");
            } catch (PDOException $e) {
                error_log("Error unfreezing location: " . $e->getMessage());
                redirectWithError('locations.php', "Une erreur est survenue lors de la réactivation du cabinet");
            }
        }
    }
}

// Get locations data
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();

// Include header after all potential redirects
include '../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <?php include '../partials/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des Cabinets</h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Adresse</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                            <tr>
                                <td><?= htmlspecialchars($loc['name']) ?></td>
                                <td><?= htmlspecialchars($loc['address'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-<?= $loc['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= $loc['status'] === 'active' ? 'Actif' : 'Gelé' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                                        <?php if ($loc['status'] === 'active'): ?>
                                            <button type="submit" name="freeze_location" class="btn btn-sm btn-outline-danger" onclick="return confirm('Confirmer le gel de ce cabinet ?')">
                                                <i class="bi bi-snow"></i> Geler
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="unfreeze_location" class="btn btn-sm btn-outline-success" onclick="return confirm('Confirmer la réactivation de ce cabinet ?')">
                                                <i class="bi bi-sun"></i> Réactiver
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include '../partials/footer.php'; ?>
