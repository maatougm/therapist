<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ' . url('login.php'));
    exit;
}

// Get user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . url('login.php'));
    exit;
}

// Initialize variables for form data and messages
$success = '';
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = trim($_POST['name']);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate name
    if (empty($name)) {
        $error = 'Le nom est requis';
    }

    // Validate email
    if (empty($email)) {
        $error = 'L\'email est requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format d\'email invalide';
    } elseif ($email !== $user['email']) {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            $error = 'Cet email est déjà utilisé';
        }
    }

    // If password change is requested
    if (!empty($current_password)) {
        if (empty($new_password)) {
            $error = 'Le nouveau mot de passe est requis';
        } elseif (strlen($new_password) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Les nouveaux mots de passe ne correspondent pas';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Le mot de passe actuel est incorrect';
        }
    }

    // If no errors, proceed with update
    if (empty($error)) {
        try {
            // Prepare update query
            $sql = "UPDATE users SET name = ?, email = ?";
            $params = [$name, $email];

            // Add phone and address if provided
            if (!empty($phone)) {
                $sql .= ", phone = ?";
                $params[] = $phone;
            }
            if (!empty($address)) {
                $sql .= ", address = ?";
                $params[] = $address;
            }

            // Add password if changed
            if (!empty($new_password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            // Add user ID to params and execute query
            $sql .= " WHERE id = ?";
            $params[] = $user['id'];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Update session data
            $_SESSION['email'] = $email;

            $success = 'Profil mis à jour avec succès';
        } catch (PDOException $e) {
            $error = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

// Include header
include __DIR__ . '/partials/header.php';

// Include sidebar
include __DIR__ . '/partials/sidebar.php';
?>

<!-- Profile Page Section -->
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Profile Card -->
            <div class="col-md-4 mb-4">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <!-- Profile Image -->
                        <div class="mb-4">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <!-- User Info -->
                        <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                        <p class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></p>
                        <!-- Role Badge -->
                        <span class="badge bg-primary">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <!-- Card Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-user-edit me-2"></i>
                                Modifier le profil
                            </h4>
                        </div>

                        <!-- Success Message -->
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Error Message -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Profile Form -->
                        <form method="POST" action="">
                            <!-- Name Input -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom complet</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                            </div>

                            <!-- Email Input -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>

                            <!-- Phone Input -->
                            <div class="mb-3">
                                <label for="phone" class="form-label">Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Address Input -->
                            <div class="mb-3">
                                <label for="address" class="form-label">Adresse</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Password Section -->
                            <div class="card mt-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-lock me-2"></i>
                                        Changer le mot de passe
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Current Password -->
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-key"></i>
                                            </span>
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password">
                                        </div>
                                    </div>

                                    <!-- New Password -->
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password">
                                        </div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
