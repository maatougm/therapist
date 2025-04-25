<?php 
$userName = $_SESSION['user']['name'] ?? '';
?>


<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>KeneTherapy</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --primary: #40E0D0;
      --secondary: #1a1a1a;
      --text: #2c3e50;
      --background: #ffffff;
      --nav-bg: var(--secondary);
      --nav-text: var(--text);
      --toggler-invert: 0%;
    }

    [data-bs-theme="dark"] {
      --primary: #40E0D0;
      --secondary: #f8f9fa;
      --text: #f8f9fa;
      --background: #1a1a1a;
      --nav-bg: var(--secondary);
      --nav-text: var(--text);
      --toggler-invert: 100%;
    }

    body {
      background-color: var(--background);
      color: var(--text);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .navbar {
      background-color: var(--nav-bg) !important;
      border-bottom: 2px solid var(--primary);
    }

    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
      color: var(--secondary);
    }

    .btn-primary:hover {
      background-color: #38c9ba;
      border-color: #38c9ba;
    }

    .nav-link {
      color: var(--nav-text) !important;
      font-weight: 500;
    }

    .nav-link:hover {
      color: var(--primary) !important;
    }

    .theme-toggle {
      min-width: 40px;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="/pfaa/index.php" style="color: var(--primary); font-weight: 600;">KeneTherapy</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon" style="filter: invert(var(--toggler-invert));"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav ms-auto align-items-center">
          <?php if (isset($_SESSION['user'])): ?>
            <li class="nav-item me-3">
              <span class="nav-link d-flex align-items-center" style="color: var(--primary); font-weight: bold;">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($userName) ?>
              </span>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/pfaa/logout.php" style="color: var(--nav-text);">
                <span style="color: #ff4444; margin-right: 5px;">●</span>Déconnexion
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="/pfaa/login.php">Connexion</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/pfaa/register.php">Inscription</a>
            </li>
          <?php endif; ?>
          <li class="nav-item ms-2">
            <button class="btn btn-sm btn-primary py-1 px-2 theme-toggle" id="themeToggle">
              <span class="theme-icon">🌙</span>
            </button>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
