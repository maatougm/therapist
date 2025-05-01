<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/db.php';

// Initialize variables
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Veuillez entrer votre email.";
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Veuillez entrer votre mot de passe.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If no errors, check credentials
    if (empty($email_err) && empty($password_err)) {
        try {
            $sql = "SELECT id, email, password, role FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Simple password match (no hashing)
                if ($password == $row['password']) {
                    // Get user's full details including name
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$row['id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['id'];
                    $_SESSION["email"] = $user['email'];
                    $_SESSION["role"] = $user['role'];
                    $_SESSION["name"] = $user['name'];

                    // Debug session variables
                    error_log("Login successful. Session variables: " . print_r($_SESSION, true));

                    // Redirect user based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                        exit;
                    } elseif ($user['role'] === 'therapist') {
                        header("Location: kine_dashboard.php");
                        exit;
                    } else {
                        header("Location: user_dashboard.php");
                        exit;
                    }
                } else {
                    $login_err = "Mot de passe incorrect.";
                }
            } else {
                $login_err = "Aucun compte trouvé avec cet email.";
            }
        } catch (PDOException $e) {
            $login_err = "Erreur de connexion à la base de données: " . $e->getMessage();
        }
    }
}

// Include header
include 'partials/header.php';
?>

<!-- Login Form Section -->
<div class="container py-5">
    
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-md fa-3x text-primary mb-3"></i>
                        <h2 class="h4 mb-0">Connexion</h2>
                    </div>

                    <!-- Error Display -->
                    <?php if (!empty($login_err)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($login_err) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($email) ?>" required>
                            <?php if (!empty($email_err)): ?>
                                <small class="text-danger"><?= htmlspecialchars($email_err) ?></small>
                            <?php endif; ?>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-4">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <?php if (!empty($password_err)): ?>
                                <small class="text-danger"><?= htmlspecialchars($password_err) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Pas encore de compte ?
                            <a href="register.php" class="text-primary">S'inscrire</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
