<?php
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? '';
?>

<div class="d-flex flex-column flex-shrink-0 p-3 bg-light" style="height: 100vh;">
  <h5 class="mb-4 text-center" style="color: var(--primary); font-weight: bold;">KeneTherapy</h5>
  <ul class="nav nav-pills flex-column mb-auto">
    
    <?php if ($role === 'admin'): ?>
      <li class="nav-item">
        <a href="/pfaa/admin_dashboard.php" class="nav-link">🏠 Tableau de bord</a>
      </li>
      <li>
        <a href="/pfaa/admin/locations.php" class="nav-link">🏥 Cabinets</a>
      </li>
      <li>
        <a href="/pfaa/admin/therapist_access.php" class="nav-link">🔑 Accès Kinés</a>
      </li>
      <li>
        <a href="/pfaa/admin/admin_kines.php" class="nav-link">👥 Gérer les Kinés</a>
      </li>

    <?php elseif ($role === 'kine'): ?>
      <li class="nav-item">
        <a href="/pfaa/kine_dashboard.php" class="nav-link">📅 Mes Rendez-vous</a>
      </li>
      <li>
        <a href="/pfaa/manage/searchClient.php" class="nav-link">🔍 Rechercher un patient</a>
      </li>
      <li>
        <a href="/pfaa/manage/bookFor.php" class="nav-link">📝 Créer un rendez-vous</a>
      </li>
      <li>
        <a href="/pfaa/manage/createFor.php" class="nav-link">➕ Ajouter un utilisateur</a>
      </li>
    <?php endif; ?>

    <li class="mt-4">
      <a href="/pfaa/logout.php" class="nav-link text-danger">🚪 Déconnexion</a>
    </li>
  </ul>
</div>
