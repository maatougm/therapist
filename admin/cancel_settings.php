<?php
include '../partials/header.php';
include '../partials/nav.php';
require '../config/db.php';
session_start();

$cancelLimit = $pdo->query("SELECT value FROM settings WHERE name = 'cancel_limit_hours'")->fetchColumn();
?>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
      <?php include '../partials/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <h4>Paramètres d'annulation</h4>
      <form method="POST" action="../controllers/adminController.php">
        <div class="mb-3">
          <label for="cancel_limit" class="form-label">Délai minimum pour annuler (en heures)</label>
          <input type="number" name="cancel_limit" class="form-control" value="<?= $cancelLimit ?>" min="1" max="48">
        </div>
        <button type="submit" name="update_cancel_limit" class="btn btn-primary">Mettre à jour</button>
      </form>
    </main>
  </div>
</div>
<?php include '../partials/footer.php'; ?>
    