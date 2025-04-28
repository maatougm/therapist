<?php
session_start();
require '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kine'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'kine', 'active')");
    $stmt->execute([$name, $email, $password]);

    header("Location: admin_kines.php?added=1");
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_kine'])) {
    $id = $_POST['kine_id'];

    $pdo->prepare("DELETE FROM therapist_locations WHERE therapist_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'kine'")->execute([$id]);

    header("Location: admin_kines.php?deleted=1");
    exit;
}

// Handle Location Access Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_locations'])) {
    $kine_id = $_POST['kine_id'];
    $locations = isset($_POST['locations']) ? $_POST['locations'] : [];

    // Remove all current assignments
    $pdo->prepare("DELETE FROM therapist_locations WHERE therapist_id = ?")->execute([$kine_id]);

    // Add new assignments
    foreach ($locations as $loc_id) {
        $pdo->prepare("INSERT INTO therapist_locations (therapist_id, location_id) VALUES (?, ?)")->execute([$kine_id, $loc_id]);
    }

    header("Location: admin_kines.php?locations_updated=1");
    exit;
}

// Debug: Check what roles exist in the database
$roles = $pdo->query("SELECT DISTINCT role FROM users")->fetchAll(PDO::FETCH_COLUMN);
error_log("Available roles: " . implode(', ', $roles));

// Get all kines - Modified query to be more inclusive
$kines = $pdo->query("
    SELECT u.*, 
           GROUP_CONCAT(DISTINCT l.name) as locations
    FROM users u
    LEFT JOIN therapist_locations tl ON u.id = tl.therapist_id
    LEFT JOIN locations l ON tl.location_id = l.id
    WHERE TRIM(u.role) IN ('kine', 'therapist')
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

// Debug: Check how many kines were found
error_log("Number of kines found: " . count($kines));

// Get all locations
$all_locations = $pdo->query("SELECT * FROM locations")->fetchAll();

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
                <h1 class="h2">Gestion des Kinés</h1>
            </div>

            <?php if (isset($_GET['added'])): ?>
                <div class="alert alert-success">Kiné ajouté avec succès.</div>
            <?php elseif (isset($_GET['deleted'])): ?>
                <div class="alert alert-warning">Kiné supprimé avec succès.</div>
            <?php elseif (isset($_GET['locations_updated'])): ?>
                <div class="alert alert-success">Accès aux cabinets mis à jour avec succès.</div>
            <?php endif; ?>

            <!-- Add Kine Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5>Ajouter un kiné</h5>
                    <form method="POST">
                        <input type="hidden" name="add_kine" value="1">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Nom complet :</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Email :</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Mot de passe :</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </form>
                </div>
            </div>

            <!-- Kines List -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Accès aux cabinets</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($kines) > 0): ?>
                            <?php foreach ($kines as $kine): ?>
                                <tr>
                                    <td><?= htmlspecialchars($kine['name']) ?></td>
                                    <td><?= htmlspecialchars($kine['email']) ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="kine_id" value="<?= $kine['id'] ?>">
                                            <input type="hidden" name="update_locations" value="1">
                                            <?php
                                            // Get assigned locations for this kine
                                            $assigned = $pdo->query("SELECT location_id FROM therapist_locations WHERE therapist_id = {$kine['id']}")->fetchAll(PDO::FETCH_COLUMN);
                                            foreach ($all_locations as $loc):
                                            ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="locations[]" 
                                                           value="<?= $loc['id'] ?>" 
                                                           id="loc<?= $kine['id'] ?>_<?= $loc['id'] ?>"
                                                           <?= in_array($loc['id'], $assigned) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="loc<?= $kine['id'] ?>_<?= $loc['id'] ?>">
                                                        <?= htmlspecialchars($loc['name']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                            <button type="submit" class="btn btn-sm btn-primary mt-2">Enregistrer</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="delete_kine" value="1">
                                            <input type="hidden" name="kine_id" value="<?= $kine['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Confirmer la suppression de ce kiné ?')">
                                                <i class="bi bi-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Aucun kiné trouvé</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include '../partials/footer.php'; ?>
