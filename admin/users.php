<?php
/**
 * users.php - User Management System
 * 
 * This file handles all CRUD operations for user management in the admin panel.
 * It includes functionality for viewing, updating, and deleting users.
 */

// Start session first
session_start();

/**
 * Security Check
 * Ensure only admin users can access this page
 */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Include admin header
include __DIR__ . '/header.php';

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Set default role for testing (since we removed auth)
$_SESSION['role'] = 'admin';

/**
 * Update User Function
 * Handles the update of user information excluding role changes
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    // Sanitize and trim input data
    $userId = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Initialize error array to collect validation errors
    $errors = [];
    
    // Validate required fields
    if (empty($name)) {
        $errors[] = "Le nom est requis";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    // Proceed with update if no validation errors
    if (empty($errors)) {
        try {
            // Check for email uniqueness (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé par un autre utilisateur";
            } else {
                // Update user information without role
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $result = $stmt->execute([$name, $email, $phone, $address, $userId]);
                
                if ($result) {
                    $success = "Profil mis à jour avec succès";
                } else {
                    $errors[] = "Erreur lors de la mise à jour du profil";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
        }
    }
    
    // Display errors if any
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}

/**
 * Password Change Function
 * Handles the update of user password with proper validation
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = $_POST['user_id'];
    $newPassword = trim($_POST['new_password'] ?? '');
    
    // Only process password change if a new password is provided
    if (!empty($newPassword)) {
        // Validate password requirements
        $errors = [];
        if (strlen($newPassword) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        if (empty($errors)) {
            try {
                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update the password in the database
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $result = $stmt->execute([$hashedPassword, $userId]);
                
                if ($result) {
                    $success = "Mot de passe modifié avec succès";
                } else {
                    $errors[] = "Erreur lors de la modification du mot de passe";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la modification du mot de passe: " . $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            $error = implode("<br>", $errors);
        }
    }
}

/**
 * Delete User Function
 * Handles user deletion with proper checks and validations
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];
    
    try {
        // Check if user exists and get their role
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = "Utilisateur non trouvé";
        } elseif ($user['role'] === 'admin') {
            $error = "Impossible de supprimer un administrateur";
        } else {
            // Check if user has any appointments
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Impossible de supprimer un utilisateur ayant des rendez-vous";
            } else {
                // Delete user if all checks pass
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $success = "Utilisateur supprimé avec succès";
            }
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression de l'utilisateur: " . $e->getMessage();
    }
}

/**
 * Fetch All Users
 * Retrieves all users from the database with error handling
 */
try {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des utilisateurs: " . $e->getMessage();
    $users = [];
}

/**
 * Fetch Selected User Profile
 * Retrieves details of a specific user when profile_id is provided
 */
$selectedUser = null;
if (isset($_GET['profile_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_GET['profile_id']]);
        $selectedUser = $stmt->fetch();
        if (!$selectedUser) {
            $error = "Utilisateur non trouvé";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération du profil: " . $e->getMessage();
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestion des utilisateurs</h1>
</div>

<!-- Success/Error Messages -->
<?php if (isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<!-- Users Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
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
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Aucun utilisateur trouvé</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Edit Form -->
<?php if ($selectedUser): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h3 class="card-title">Modifier l'utilisateur</h3>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $selectedUser['id'] ?>">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($selectedUser['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($selectedUser['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($selectedUser['phone'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Adresse</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($selectedUser['address'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                    <input type="password" class="form-control" id="new_password" name="new_password">
                    <small class="text-muted">Laissez vide pour ne pas changer le mot de passe</small>
                </div>

                <button type="submit" name="update_user" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="users.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>