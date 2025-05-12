<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . url('login.php'));
    exit;
}

// Handle Add Therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_therapist'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, 'therapist', ?, ?)");
    $stmt->execute([$name, $email, $password, $phone, $address]);

    header("Location: " . url('admin/therapists.php') . "?added=1");
    exit;
}

// Handle Update Therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_therapist'])) {
    $id = $_POST['therapist_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ? AND role = 'therapist'");
    $stmt->execute([$name, $email, $phone, $address, $id]);

    header("Location: " . url('admin/therapists.php') . "?updated=1");
    exit;
}

// Handle Delete Therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['therapist_id'])) {
        $therapist_id = $_POST['therapist_id'];
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // First, delete all appointments for this therapist
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE therapist_id = ?");
            $stmt->execute([$therapist_id]);
            
            // Then delete therapist locations
            $stmt = $pdo->prepare("DELETE FROM therapist_locations WHERE therapist_id = ?");
            $stmt->execute([$therapist_id]);
            
            // Finally delete the therapist
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'therapist'");
            $stmt->execute([$therapist_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success'] = "Thérapeute supprimé avec succès";
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de la suppression du thérapeute: " . $e->getMessage();
        }
        
        header('Location: therapists.php');
        exit();
    }
}

// Handle Add Location to Therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $therapist_id = $_POST['therapist_id'];
    $location_ids = $_POST['location_ids'] ?? [];

    if (!empty($location_ids)) {
        try {
            $pdo->beginTransaction();
            
            foreach ($location_ids as $location_id) {
                $stmt = $pdo->prepare("INSERT INTO therapist_locations (therapist_id, location_id) VALUES (?, ?)");
                $stmt->execute([$therapist_id, $location_id]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Lieux ajoutés avec succès";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de l'ajout des lieux: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Veuillez sélectionner au moins un lieu";
    }

    header("Location: " . url('admin/therapists.php'));
    exit;
}

// Handle Remove Location from Therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_location'])) {
    $therapist_id = $_POST['therapist_id'];
    $location_id = $_POST['location_id'];

    $stmt = $pdo->prepare("DELETE FROM therapist_locations WHERE therapist_id = ? AND location_id = ?");
    $stmt->execute([$therapist_id, $location_id]);

    header("Location: " . url('admin/therapists.php') . "?location_removed=1");
    exit;
}

// Get all therapists
$therapists = $pdo->query("SELECT * FROM users WHERE role = 'therapist' ORDER BY created_at DESC")->fetchAll();

// Get all locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

// Include header
include __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <?php include '../partials/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des Kinésithérapeutes</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTherapistModal">
                    <i class="fas fa-plus"></i> Ajouter un kiné
                </button>
            </div>

            <?php if (isset($_GET['added'])): ?>
                <div class="alert alert-success">Kiné ajouté avec succès.</div>
            <?php endif; ?>
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">Kiné mis à jour avec succès.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Kiné supprimé avec succès.</div>
            <?php endif; ?>
            <?php if (isset($_GET['location_added'])): ?>
                <div class="alert alert-success">Lieu ajouté avec succès.</div>
            <?php endif; ?>
            <?php if (isset($_GET['location_removed'])): ?>
                <div class="alert alert-success">Lieu retiré avec succès.</div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Adresse</th>
                            <th>Lieux</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($therapists as $therapist): 
                            // Get therapist's locations
                            $stmt = $pdo->prepare("
                                SELECT l.* FROM locations l
                                JOIN therapist_locations tl ON l.id = tl.location_id
                                WHERE tl.therapist_id = ?
                            ");
                            $stmt->execute([$therapist['id']]);
                            $therapist_locations = $stmt->fetchAll();
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($therapist['name']) ?></td>
                                <td><?= htmlspecialchars($therapist['email']) ?></td>
                                <td><?= htmlspecialchars($therapist['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($therapist['address'] ?? '') ?></td>
                                <td>
                                    <div class="locations-container">
                                        <?php foreach ($therapist_locations as $location): ?>
                                            <div class="location-badge">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= htmlspecialchars($location['name']) ?>
                                                <form method="POST" class="d-inline ms-1">
                                                    <input type="hidden" name="therapist_id" value="<?= $therapist['id'] ?>">
                                                    <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                                    <button type="submit" name="remove_location" class="location-remove-btn">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary location-add-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#addLocationModal<?= $therapist['id'] ?>">
                                            <i class="fas fa-plus me-1"></i> Ajouter
                                        </button>
                                    </div>
                                </td>
                                <td><?= date('d/m/Y', strtotime($therapist['created_at'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTherapistModal<?= $therapist['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteTherapistModal<?= $therapist['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Add Location Modal -->
                            <div class="modal fade" id="addLocationModal<?= $therapist['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                Ajouter des lieux pour <?= htmlspecialchars($therapist['name']) ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="therapist_id" value="<?= $therapist['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Sélectionner les lieux</label>
                                                    <div class="location-checkboxes" style="max-height: 300px; overflow-y: auto;">
                                                        <?php foreach ($locations as $location): 
                                                            // Check if location is already assigned
                                                            $stmt = $pdo->prepare("
                                                                SELECT * FROM therapist_locations 
                                                                WHERE therapist_id = ? AND location_id = ?
                                                            ");
                                                            $stmt->execute([$therapist['id'], $location['id']]);
                                                            if ($stmt->rowCount() == 0):
                                                        ?>
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox" name="location_ids[]" 
                                                                       value="<?= $location['id'] ?>" id="location_<?= $location['id'] ?>">
                                                                <label class="form-check-label" for="location_<?= $location['id'] ?>">
                                                                    <i class="fas fa-map-marker-alt me-2"></i>
                                                                    <?= htmlspecialchars($location['name']) ?>
                                                                </label>
                                                            </div>
                                                        <?php endif; endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-1"></i> Annuler
                                                </button>
                                                <button type="submit" name="add_location" class="btn btn-primary">
                                                    <i class="fas fa-plus me-1"></i> Ajouter
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editTherapistModal<?= $therapist['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Modifier le kiné</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="therapist_id" value="<?= $therapist['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Nom</label>
                                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($therapist['name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($therapist['email']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Téléphone</label>
                                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($therapist['phone'] ?? '') ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Adresse</label>
                                                    <textarea name="address" class="form-control"><?= htmlspecialchars($therapist['address'] ?? '') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <button type="submit" name="update_therapist" class="btn btn-primary">Enregistrer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteTherapistModal<?= $therapist['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Supprimer le kiné</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Êtes-vous sûr de vouloir supprimer ce kiné ? Cette action est irréversible.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="therapist_id" value="<?= $therapist['id'] ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-danger">Supprimer</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Therapist Modal -->
<div class="modal fade" id="addTherapistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un kiné</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea name="address" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="add_therapist" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?> 