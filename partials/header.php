<?php
?>


<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>KeneTherapy</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= url('assets/css/sidebar.css') ?>">

  <style>
    :root {
      --primary: #2B7A78;      /* Medical Teal */
      --secondary: #17252A;    /* Dark Navy */
      --accent: #3AAFA9;       /* Light Teal */
      --light: #DEF2F1;        /* Very Light Teal */
      --text: #2C3E50;         /* Dark Blue-Gray */
      --background: #FEFFFF;   /* Pure White */
      --nav-bg: var(--secondary);
      --nav-text: var(--light);
      --success: #4CAF50;      /* Medical Green */
      --warning: #FF9800;      /* Medical Orange */
      --danger: #F44336;       /* Medical Red */
    }

    [data-bs-theme="dark"] {
      --primary: #3AAFA9;      /* Light Teal */
      --secondary: #17252A;    /* Dark Navy */
      --accent: #2B7A78;       /* Medical Teal */
      --light: #DEF2F1;        /* Very Light Teal */
      --text: #FEFFFF;         /* White */
      --background: #17252A;   /* Dark Navy */
      --nav-bg: var(--secondary);
      --nav-text: var(--light);
    }

    body {
      background-color: var(--background);
      color: var(--text);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: .875rem;
    }

    .navbar {
      background-color: var(--nav-bg) !important;
      border-bottom: 2px solid var(--primary);
    }

    .navbar-brand {
      color: var(--primary) !important;
      font-weight: 700;
    }

    .navbar-brand i {
      color: var(--accent);
    }

    .nav-link {
      color: var(--nav-text) !important;
    }

    .nav-link:hover {
      color: var(--primary) !important;
    }

    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
    }

    .btn-primary:hover {
      background-color: var(--accent);
      border-color: var(--accent);
    }

    .card {
      background-color: var(--background);
      border-color: var(--primary);
    }

    .table {
      color: var(--text);
    }

    .table thead th {
      background-color: var(--secondary);
      color: var(--light);
    }

    .feather {
      width: 16px;
      height: 16px;
      vertical-align: text-bottom;
    }
  </style>
</head>
<body>
  <!-- Top Navigation -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">
        <i class="fas fa-heartbeat"></i>
        KeneTherapy
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="index.php">
              <i class="fas fa-home"></i>
              Accueil
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content Container -->
  <div class="container-fluid">
    <div class="row">
</body>
</html>
