<?php
session_start();
include '../partials/header.php';
require '../config/db.php';

// Force dark mode
echo '<script>document.documentElement.setAttribute("data-bs-theme", "dark");</script>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $phone = $_POST['phone'];
  $address = $_POST['address'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, address, role, password) VALUES (?, ?, ?, ?, 'client', ?)");
  $stmt->execute([$name, $email, $phone, $address, $password]);
  
  header('Location: searchClient.php');
  exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Ajouter un patient</h2>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom complet</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Adresse</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Créer le compte patient</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Override Bootstrap theme variables */
:root[data-bs-theme="dark"] {
  --bs-body-bg: #121212;
  --bs-body-color: #ffffff;
  --bs-card-bg: #121212;
  --bs-border-color: #333;
  --sidebar-bg: #1a1a1a;
  --sidebar-hover: #2a2a2a;
  --sidebar-active: #2a2a2a;
  --sidebar-text: #e0e0e0;
  --sidebar-icon: #4e73df;
}

:root[data-bs-theme="light"] {
  --bs-body-bg: #f8f9fa;
  --bs-body-color: #212529;
  --bs-card-bg: #f8f9fa;
  --bs-border-color: #dee2e6;
  --sidebar-bg: #f0f2f5;
  --sidebar-hover: #e4e6eb;
  --sidebar-active: #d8dadf;
  --sidebar-text: #1a1a1a;
  --sidebar-icon: #0d6efd;
}

/* Sidebar Styles */
.sidebar {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 100;
  padding: 48px 0 0;
  box-shadow: inset -1px 0 0 var(--bs-border-color);
  background-color: var(--sidebar-bg);
  width: 250px;
  overflow-y: auto;
}

.sidebar-avatar {
  width: 80px;
  height: 80px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--sidebar-hover);
  border-radius: 50%;
  font-size: 2.5rem;
  color: var(--sidebar-text);
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar .nav-link {
  color: var(--sidebar-text);
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin: 0.25rem 1rem;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  font-size: 0.9rem;
  font-weight: 500;
}

.sidebar .nav-link i {
  width: 20px;
  margin-right: 0.75rem;
  font-size: 1.1rem;
  color: var(--sidebar-icon);
}

.sidebar .nav-link:hover {
  background: var(--sidebar-hover);
  color: var(--sidebar-text);
  transform: translateX(5px);
}

.sidebar .nav-link.active {
  background: var(--sidebar-active);
  color: var(--sidebar-text);
  font-weight: 600;
  border-left: 3px solid var(--sidebar-icon);
}

.sidebar .text-muted {
  color: var(--sidebar-text) !important;
  opacity: 0.8;
}

.sidebar .text-danger {
  color: #dc3545 !important;
  font-weight: 500;
}

.sidebar .text-danger:hover {
  color: #dc3545 !important;
  background: rgba(220, 53, 69, 0.1);
}

/* Main Content Styles */
main {
  margin-left: 250px;
  padding-top: 1.5rem;
  min-height: 100vh;
  background-color: var(--bs-body-bg);
  color: var(--bs-body-color);
}

/* Card Styles */
.card {
  background-color: var(--bs-card-bg);
  border: 1px solid var(--bs-border-color);
  color: var(--bs-body-color);
  box-shadow: none;
}

.card-body {
  padding: 1.5rem;
}

.card-header {
  background-color: var(--sidebar-bg) !important;
  border-bottom: 1px solid var(--bs-border-color);
}

.form-control, .form-select {
  background-color: var(--bs-card-bg);
  border-color: var(--bs-border-color);
  color: var(--bs-body-color);
}

.form-control:focus, .form-select:focus {
  background-color: var(--bs-card-bg);
  border-color: var(--sidebar-icon);
  color: var(--bs-body-color);
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-control::placeholder {
  color: var(--bs-body-color);
  opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
  .sidebar {
    position: static;
    height: auto;
    padding-top: 0;
    width: 100%;
  }
  
  main {
    margin-left: 0;
  }
}

/* Scrollbar Styles */
.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: var(--sidebar-bg);
}

.sidebar::-webkit-scrollbar-thumb {
  background: var(--bs-border-color);
  border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: var(--sidebar-hover);
}

/* Form Validation Styles */
.was-validated .form-control:valid {
  background-color: var(--bs-card-bg);
  border-color: #198754;
}

.was-validated .form-control:invalid {
  background-color: var(--bs-card-bg);
  border-color: #dc3545;
}

.invalid-feedback {
  color: #dc3545;
}

/* Button Styles */
.btn-primary {
  background-color: var(--sidebar-icon);
  border-color: var(--sidebar-icon);
}

.btn-primary:hover {
  background-color: #0b5ed7;
  border-color: #0a58ca;
}

/* Border Bottom for Header */
.border-bottom {
  border-color: var(--bs-border-color) !important;
}
</style>

<script>
// Form validation
(function() {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>

<?php include '../partials/footer.php'; ?>
