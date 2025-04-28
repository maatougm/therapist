<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    error_log("Login.php - Session started");
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database configuration
require_once 'config/db.php';
error_log("Login.php - Database configuration loaded");

// Initialize variables
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Debug: Log POST data
error_log("Login.php - POST data: " . print_r($_POST, true));

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    error_log("Login.php - Processing POST request");
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
        error_log("Login.php - Email validation failed: Empty email");
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        error_log("Login.php - Email validated: " . $email);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
        error_log("Login.php - Password validation failed: Empty password");
    } else {
        $password = trim($_POST["password"]);
        error_log("Login.php - Password validated");
    }
    
    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        error_log("Login.php - Starting credential validation");
        
        try {
            // Prepare a select statement
            $sql = "SELECT id, email, password, role FROM users WHERE email = :email";
            error_log("Login.php - SQL query prepared");
            
            $stmt = $pdo->prepare($sql);
            error_log("Login.php - Statement prepared successfully");
            
            // Bind parameters
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                error_log("Login.php - Statement executed successfully");
                
                // Check if email exists
                if ($stmt->rowCount() == 1) {
                    error_log("Login.php - Email found in database");
                    
                    // Fetch the result
                    $row = $stmt->fetch();
                    $id = $row['id'];
                    $stored_password = $row['password'];
                    $role = $row['role'];
                    
                    error_log("Login.php - User data fetched");
                    error_log("Login.php - User role: " . $role);
                    
                    // Compare plain text passwords
                    if ($password === $stored_password) {
                        error_log("Login.php - Password verified successfully");
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["email"] = $email;
                        $_SESSION["role"] = $role;
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        error_log("Login.php - Session data set: " . print_r($_SESSION, true));
                        
                        // Redirect user based on role
                        if ($role === 'admin') {
                            error_log("Login.php - Redirecting admin to dashboard");
                            header("location: admin_dashboard.php");
                            exit();
                        } else if ($role === 'therapist') {
                            error_log("Login.php - Redirecting therapist to dashboard");
                            header("location: kine_dashboard.php");
                            exit();
                        } else {
                            error_log("Login.php - Redirecting client to dashboard");
                            header("location: user_dashboard.php");
                            exit();
                        }
                    } else {
                        error_log("Login.php - Password verification failed");
                        $login_err = "Invalid email or password.";
                    }
                } else {
                    error_log("Login.php - Email not found in database");
                    $login_err = "Invalid email or password.";
                }
            } else {
                error_log("Login.php - Statement execution failed");
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
        } catch (PDOException $e) {
            error_log("Login.php - Database error: " . $e->getMessage());
            $login_err = "Oops! Something went wrong. Please try again later.";
        }
    }
}

// Include header after any potential redirects
include 'partials/header.php';
?>

<!-- Login Form Section -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <!-- Login Card -->
            <div class="card shadow">
                <div class="card-body p-4">
                    <!-- Card Header with Logo -->
                    <div class="text-center mb-4">
                        <i class="fas fa-user-md fa-3x text-primary mb-3"></i>
                        <h2 class="h4 mb-0">Connexion</h2>
                    </div>

                    <!-- Error Message Display -->
                    <?php if ($login_err): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($login_err) ?></div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        
                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($email) ?>" required>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-4">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" 
                                       name="password" required>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Se connecter
                            </button>
                        </div>
                    </form>

                    <!-- Registration Link -->
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
