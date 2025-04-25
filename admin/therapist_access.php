<?php
session_start();

include '../partials/header.php';
require '../config/db.php';


$therapists = $pdo->query("SELECT id, name FROM users WHERE role = 'kine'")->fetchAll();
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();
$accessMap = [];
foreach ($pdo->query("SELECT * FROM therapist_locations") as $row) {
  $accessMap[$row['therapist_id']][] = $row['location_id'];
}
?>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
      <?php include '../partials/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <h4>Accès des Kinés</h4>
      <?php foreach ($therapists as $t): ?>
        <form method="POST" action="../controllers/adminController.php" class="border p-3 mb-3">
          <input type="hidden" name="therapist_id" value="<?= $t['id'] ?>">
          <h5><?= htmlspecialchars($t['name']) ?></h5>
          <div class="row">
            <?php foreach ($locations as $loc): ?>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="access[]" value="<?= $loc['id'] ?>" id="<?= $t['id'] ?>-<?= $loc['id'] ?>" <?= in_array($loc['id'], $accessMap[$t['id']] ?? []) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="<?= $t['id'] ?>-<?= $loc['id'] ?>">
                    <?= htmlspecialchars($loc['name']) ?>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="submit" name="assign_location" class="btn btn-primary mt-2">Mettre à jour</button>
        </form>
      <?php endforeach; ?>
    </main>
  </div>
</div>
<?php include '../partials/footer.php'; ?>
