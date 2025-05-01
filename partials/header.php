<?php
// Include configuration
require_once __DIR__ . '/../config/config.php';
?>

<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KeneTherapy</title>

  <!-- Bootstrap 5.3.3 & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Sidebar CSS -->
  <link rel="stylesheet" href="<?php echo url('assets/css/sidebar.css'); ?>">

  <!-- Custom Theme CSS -->
  <link rel="stylesheet" href="<?php echo url('assets/css/theme.css'); ?>">

  <style>
    .navbar {
      background-color: var(--bs-dark);
      padding: 0.5rem 1rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .navbar-brand {
      color: var(--bs-light);
      font-weight: 600;
    }

    .navbar-brand i {
      color: var(--bs-primary);
      margin-right: 0.5rem;
    }

    .nav-link {
      color: var(--bs-light);
      padding: 0.5rem 1rem;
      transition: all 0.3s ease;
    }

    .nav-link:hover {
      color: var(--bs-primary);
    }

    .theme-toggle {
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .theme-toggle:hover {
      background-color: rgba(255,255,255,0.1);
    }

    .logo-right {
      height: 40px;
      margin-left: 1rem;
    }

    .header-buttons {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .header-buttons .btn {
      padding: 0.375rem 0.75rem;
      border-radius: 0.5rem;
      transition: all 0.3s ease;
    }

    .header-buttons .btn:hover {
      transform: translateY(-1px);
    }

    /* Dark Mode Fixes */
    [data-bs-theme="dark"] .navbar {
      background-color: #212529;
    }

    [data-bs-theme="dark"] .navbar-brand,
    [data-bs-theme="dark"] .nav-link {
      color: #fff;
    }

    [data-bs-theme="dark"] .theme-toggle {
      color: #fff;
    }

    [data-bs-theme="dark"] .theme-toggle:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }
  </style>
</head>

<body>
  <!-- Top Navigation -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <!-- Left side: Brand and Toggle -->
      <div class="d-flex align-items-center">
        <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
          <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" href="/pfaa/index.php">
          <i class="fas fa-heartbeat"></i> KeneTherapy
        </a>
      </div>

      <!-- Right side: Theme Toggle, User Menu, and Logo -->
      <div class="d-flex align-items-center">
        <!-- Theme Toggle -->
        <div class="theme-toggle me-3" id="themeToggle">
          <i class="fas fa-moon"></i>
        </div>

        <?php if (isset($_SESSION['loggedin'])): ?>
          <!-- Header Buttons -->
          <div class="header-buttons">
            <a href="/pfaa/profile.php" class="btn btn-outline-primary" title="Profil">
              <i class="fas fa-user me-1"></i>
              <?php echo htmlspecialchars($_SESSION['name'] ?? 'Utilisateur'); ?>
            </a>
            <a href="/pfaa/logout.php" class="btn btn-outline-danger" title="Déconnexion">
              <i class="fas fa-sign-out-alt"></i>
            </a>
          </div>
        <?php endif; ?>

        <!-- Logo -->
        <img src="<?php echo url('assets/images/logo.png'); ?>" alt="Logo" class="logo-right">
      </div>
    </div>
  </nav>

  <!-- Theme Toggle Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const themeToggle = document.getElementById('themeToggle');
      const html = document.documentElement;
      const icon = themeToggle.querySelector('i');
      
      // Check for saved theme preference
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme) {
        html.setAttribute('data-bs-theme', savedTheme);
        icon.className = savedTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
      }

      // Toggle theme
      themeToggle.addEventListener('click', function() {
        const currentTheme = html.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        html.setAttribute('data-bs-theme', newTheme);
        icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        
        // Save preference
        localStorage.setItem('theme', newTheme);
      });
    });
  </script>

  <!-- Start Main Container -->
  <div class="container-fluid">
    <div class="row">
