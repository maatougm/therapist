<?php

session_start();
include 'partials/header.php';
require 'config/db.php';
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$appointments = $pdo->query("SELECT appointments.*, users.name as client_name FROM appointments JOIN users ON appointments.user_id = users.id ORDER BY date DESC LIMIT 5")->fetchAll();
?>
 <link rel="stylesheet" href="./config/theme.css">
 <script src="./config/script.js"></script>
<div class="container py-4">
  <h3 class="admin-greeting">Bienvenue Admin 👋</h3>

 
  <div class="row g-4">
    <div class="col-md-6 col-xl-3">
      <div class="dashboard-card">
        <a href="admin/locations.php">
          <div class="card-body">
            <h5>Gérer les Cabinets</h5>
            <p>Voir, geler ou activer les cabinets.</p>
          </div>
        </a>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="dashboard-card">
        <a href="admin/therapist_access.php">
          <div class="card-body">
            <h5>Accès Kinés</h5>
            <p>Attribuer des cabinets aux kinés.</p>
          </div>
        </a>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="dashboard-card">
        <a href="admin/cancel_settings.php">
          <div class="card-body">
            <h5>Paramètres d'annulation</h5>
            <p>Modifier le délai d'annulation autorisé.</p>
          </div>
        </a>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="dashboard-card">
        <a href="kine_dashboard.php">
          <div class="card-body">
            <h5>Espace Kiné</h5>
            <p>Voir les rendez-vous et profils patients.</p>
          </div>
        </a>
      </div>
    </div>
  </div>

  <div class="mt-5">
    <h5 class="section-title">Derniers Rendez-vous</h5>
    <div class="appointments-list">
      <?php foreach ($appointments as $apt): ?>
      <div class="appointment-item">
        <div class="appointment-info">
          <span class="client-name"><?= $apt['client_name'] ?></span>
          <span class="appointment-date"><?= date('d/m/Y H:i', strtotime($apt['date'])) ?></span>
        </div>
        <span class="status-badge <?= $apt['status'] === 'confirmed' ? 'confirmed' : 'pending' ?>">
          <?= ucfirst($apt['status']) ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include 'partials/footer.php'; ?>