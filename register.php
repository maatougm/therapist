<?php
require 'config/db.php';

// Initialize variables for form data and error messages
$name = $email = $password = $confirm_password = '';
$errors = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Le nom est requis';
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'L\'email est requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format d\'email invalide';
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $password]);

            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $errors['general'] = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

include 'partials/header.php';
?>

<!-- Registration Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Registration Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <!-- Card Header -->
                        <div class="text-center mb-4">
                            <div class="registration-icon mb-3">
                                <i class="fas fa-user-plus fa-3x text-primary"></i>
                            </div>
                            <h2 class="h4 mb-0">Créer un compte</h2>
                            <p class="text-muted">Rejoignez notre communauté de patients</p>
                        </div>

                        <!-- General Error Message -->
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                        <?php endif; ?>

                        <!-- Registration Form -->
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <!-- Name Input -->
                            <div class="mb-4">
                                <label for="name" class="form-label">Nom complet</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                           id="name" 
                                           name="name" 
                                           value="<?= htmlspecialchars($name) ?>" 
                                           required>
                                </div>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Email Input -->
                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($email) ?>" 
                                           required>
                                </div>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Password Input -->
                            <div class="mb-4">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>
                                    S'inscrire
                                </button>
                            </div>
                        </form>

                        <!-- Login Link -->
                        <div class="text-center mt-4">
                            <p class="mb-0">Déjà inscrit ? 
                                <a href="login.php" class="text-primary">Se connecter</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Registration Section Styles */
.registration-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-light);
    border-radius: 50%;
}

/* Form Input Styles */
.input-group-text {
    background: var(--primary-light);
    border: none;
    color: var(--primary);
}

.form-control {
    border-left: none;
}

.form-control:focus {
    box-shadow: none;
    border-color: var(--primary);
}

/* Button Styles */
.btn-primary {
    background: var(--primary);
    border: none;
    padding: 0.75rem 1.5rem;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

/* Card Styles */
.card {
    border-radius: 1rem;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .card-body {
        padding: 2rem;
    }
}
</style>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php include 'partials/footer.php'; ?>
