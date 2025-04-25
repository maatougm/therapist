<?php include '../partials/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();
?>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 bg-light p-3">
      <?php include '../partials/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <h4>Gestion des Cabinets</h4>

      <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
        <div class="alert alert-success">Le statut du cabinet a été mis à jour avec succès.</div>
      <?php endif; ?>

      <table class="table table-bordered table-hover align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>Nom</th>
            <th>Statut</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($locations as $loc): ?>
            <tr>
              <td><?= htmlspecialchars($loc['name']) ?></td>
              <td>
                <span class="badge <?= $loc['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                  <?= $loc['status'] === 'active' ? 'Actif' : 'Gelé' ?>
                </span>
              </td>
              <td>
                <form method="POST" action="../controllers/adminController.php" class="d-inline">
                  <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                  <?php if ($loc['status'] === 'active'): ?>
                    <button type="submit" name="freeze_location" class="btn btn-danger btn-sm" onclick="return confirm('Confirmer le gel de ce cabinet ?')">
                      Geler
                    </button>
                  <?php else: ?>
                    <button type="submit" name="unfreeze_location" class="btn btn-success btn-sm" onclick="return confirm('Confirmer l\'activation de ce cabinet ?')">
                      Activer
                    </button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </main>
  </div>
</div>

<?php include '../partials/footer.php'; ?>
