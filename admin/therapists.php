<?php
session_start();
include '../partials/header.php';
require '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle Add Therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_therapist'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, 'kine', ?, ?)");
    $stmt->execute([$name, $email, $password, $phone, $address]);

    header("Location: therapists.php?added=1");
    exit;
}

// Handle Update Therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_therapist'])) {
    $id = $_POST['therapist_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ? AND role = 'kine'");
    $stmt->execute([$name, $email, $phone, $address, $id]);

    header("Location: therapists.php?updated=1");
    exit;
}

// Handle Delete Therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_therapist'])) {
    $id = $_POST['therapist_id'];

    // First delete therapist locations
    $pdo->prepare("DELETE FROM therapist_locations WHERE therapist_id = ?")->execute([$id]);
    
    // Then delete the therapist
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'kine'")->execute([$id]);

    header("Location: therapists.php?deleted=1");
    exit;
}

// Get all therapists
$therapists = $pdo->query("SELECT * FROM users WHERE role = 'kine' ORDER BY created_at DESC")->fetchAll();
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
                <h1 class="h2">Gestion des Kinésithérapeutes</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTherapistModal">
                    <i class="bi bi-plus-lg"></i> Ajouter un kiné
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

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Adresse</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($therapists as $therapist): ?>
                            <tr>
                                <td><?= htmlspecialchars($therapist['name']) ?></td>
                                <td><?= htmlspecialchars($therapist['email']) ?></td>
                                <td><?= htmlspecialchars($therapist['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($therapist['address'] ?? '') ?></td>
                                <td><?= date('d/m/Y', strtotime($therapist['created_at'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTherapistModal<?= $therapist['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteTherapistModal<?= $therapist['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

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
                                                <button type="submit" name="delete_therapist" class="btn btn-danger">Supprimer</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
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

<?php include '../partials/footer.php'; ?> 