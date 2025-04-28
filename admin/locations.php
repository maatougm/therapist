<?php
session_start();
include '../partials/header.php';
require '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$locations = $pdo->query("SELECT * FROM locations")->fetchAll();
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
                <h1 class="h2">Gestion des Cabinets</h1>
            </div>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
                <div class="alert alert-success">Le statut du cabinet a été mis à jour avec succès.</div>
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
                                    <form method="POST" action="../controllers/adminController.php" class="d-inline">
                                        <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                                        <?php if ($loc['status'] === 'active'): ?>
                                            <button type="submit" name="freeze_location" class="btn btn-sm btn-outline-danger" onclick="return confirm('Confirmer le gel de ce cabinet ?')">
                                                <i class="bi bi-pause-fill"></i> Geler
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="unfreeze_location" class="btn btn-sm btn-outline-success" onclick="return confirm('Confirmer l\'activation de ce cabinet ?')">
                                                <i class="bi bi-play-fill"></i> Activer
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
