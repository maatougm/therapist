<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<link rel="stylesheet" href="pfaa/config/theme.css">
<nav class="navbar navbar-expand-lg" style="background-color: var(--nav-bg); border-bottom: 2px solid var(--primary);">
  <div class="container-fluid">
    <a class="navbar-brand" href="/pfaa/index.php" style="color: var(--primary); font-weight: 600;">KeneTherapy</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon" style="filter: invert(var(--toggler-invert));"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if (isset($_SESSION['user'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="/pfaa/logout.php" style="color: var(--nav-text); position: relative;">
              <span style="color: #ff4444; margin-right: 5px;">●</span>Déconnexion
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="/pfaa/login.php" style="color: var(--nav-text);">Connexion</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/pfaa/register.php" style="color: var(--nav-text);">Inscription</a>
          </li>
        <?php endif; ?>
        <!-- Theme Toggle -->
        <li class="nav-item ms-2">
          <button class="btn btn-sm btn-primary py-1 px-2" id="themeToggle" style="min-width: 40px;">
            <span class="theme-icon">🌙</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</nav>