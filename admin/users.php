<?php
session_start();

// Security: Only admins allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Database connection
require_once __DIR__ . '/../config/db.php';

// Initialize
$success = '';
$error = '';
$selectedUser = null;

// Build search query
$searchQuery = "SELECT * FROM users";
$params = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $searchQuery .= " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $params = [$search, $search, $search];
}

$searchQuery .= " ORDER BY created_at DESC";

// Handle actions BEFORE any output or includes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        // Update User
        $userId = $_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $newPassword = trim($_POST['new_password']);

        $errors = [];

        if (empty($name)) {
            $errors[] = "Le nom est requis.";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email n'est pas valide.";
        }

        if (empty($errors)) {
            try {
                // Check email uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $errors[] = "Cet email est déjà utilisé.";
                } else {
                    // Update profile fields
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $address, $userId]);

                    // Update password if provided
                    if (!empty($newPassword)) {
                        if (strlen($newPassword) < 6) {
                            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
                        } else {
                            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$newPassword, $userId]);
                        }
                    }

                    if (empty($errors)) {
                        $success = "Utilisateur modifié avec succès.";
                        header('Location: users.php?success=' . urlencode($success));
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur de mise à jour: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $error = implode("<br>", $errors);
        }
    }

    if (isset($_POST['delete_user'])) {
        // Delete User
        $userId = $_POST['user_id'];

        try {
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "Utilisateur non trouvé.";
            } elseif ($user['role'] === 'admin') {
                $error = "Impossible de supprimer un administrateur.";
            } else {
                // Check if user has appointments
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
                $stmt->execute([$userId]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Impossible de supprimer un utilisateur ayant des rendez-vous.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $success = "Utilisateur supprimé avec succès.";
                    header('Location: users.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur de suppression: " . $e->getMessage();
        }
    }
}

// Fetch users with search
try {
    $stmt = $pdo->prepare($searchQuery);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des utilisateurs.";
    $users = [];
}

// If editing, load user
if (isset($_GET['profile_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_GET['profile_id']]);
        $selectedUser = $stmt->fetch();
        if (!$selectedUser) {
            $error = "Utilisateur non trouvé.";
        }
    } catch (PDOException $e) {
        $error = "Erreur de récupération du profil: " . $e->getMessage();
    }
}

// Now include the header (after all header() calls)
include '../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <?php include '../partials/sidebar.php'; ?>
        </div>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des utilisateurs</h1>
            </div>

            <!-- Search Bar -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Rechercher un utilisateur..." 
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Rechercher
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="users.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Success/Error -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>"><?= ucfirst($user['role']) ?></span></td>
                                        <td>
                                            <a href="?profile_id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Edit User Form Side Panel -->
            <?php if ($selectedUser): ?>
                <div class="offcanvas offcanvas-end" tabindex="-1" id="editUserPanel" aria-labelledby="editUserPanelLabel">
                    <div class="offcanvas-header bg-light">
                        <h5 class="offcanvas-title" id="editUserPanelLabel">
                            <i class="fas fa-user-edit me-2"></i>
                            Modifier l'utilisateur
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="user_id" value="<?= $selectedUser['id'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Rôle</label>
                                <div class="badge bg-<?= $selectedUser['role'] === 'admin' ? 'danger' : 'primary' ?> p-2 w-100">
                                    <?= ucfirst($selectedUser['role']) ?>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($selectedUser['name']) ?>" required>
                                <label for="name">
                                    <i class="fas fa-user me-2"></i>Nom complet
                                </label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($selectedUser['email']) ?>" required>
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($selectedUser['phone'] ?? '') ?>">
                                <label for="phone">
                                    <i class="fas fa-phone me-2"></i>Téléphone
                                </label>
                            </div>

                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="address" name="address" 
                                          style="height: 100px"><?= htmlspecialchars($selectedUser['address'] ?? '') ?></textarea>
                                <label for="address">
                                    <i class="fas fa-map-marker-alt me-2"></i>Adresse
                                </label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <label for="new_password">
                                    <i class="fas fa-key me-2"></i>Nouveau mot de passe
                                </label>
                                <small class="text-muted ms-3">Laisser vide si inchangé</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="update_user" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                                <a href="users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    // Show the offcanvas panel when a user is selected
                    document.addEventListener('DOMContentLoaded', function() {
                        var editPanel = new bootstrap.Offcanvas(document.getElementById('editUserPanel'));
                        editPanel.show();
                    });
                </script>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
// Footer
include '../partials/footer.php';
?>
