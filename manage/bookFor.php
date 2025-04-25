<?php include '../partials/header.php'; ?>
<?php include '../partials/nav.php'; ?>
<?php
require '../config/db.php';
session_start();

$searchResults = [];
if (isset($_POST['search'])) {
  $term = '%' . $_POST['search_term'] . '%';
  $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'client' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)");
  $stmt->execute([$term, $term, $term]);
  $searchResults = $stmt->fetchAll();
}

$locations = $pdo->query("SELECT * FROM locations WHERE status = 'active'")->fetchAll();

$start = new DateTime();
$start->modify('monday this week');
$appointmentCounts = [];
foreach ($locations as $loc) {
  for ($i = 0; $i < 7; $i++) {
    $day = clone $start;
    $day->modify("+$i day");
    $dateStr = $day->format('Y-m-d');
    for ($h = 8; $h <= 19; $h++) {
      $hour = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE date = ? AND hour = ? AND location_id = ?");
      $stmt->execute([$dateStr, $hour, $loc['id']]);
      $appointmentCounts[$loc['id']][$dateStr][$hour] = $stmt->fetchColumn();
    }
  }
}
?>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 col-lg-2 bg-light p-3">
      <?php include '../partials/sidebar.php'; ?>
    </div>

    <div class="col-md-9 col-lg-10 mt-4">
      <h2>Rechercher un patient</h2>

      <form method="POST" class="mb-4">
        <div class="input-group">
          <input type="text" name="search_term" class="form-control" placeholder="Nom, email ou téléphone...">
          <button class="btn btn-primary" type="submit" name="search">Rechercher</button>
        </div>
      </form>

      <?php if (!empty($searchResults)): ?>
        <table class="table table-sm table-hover table-bordered align-middle text-center">
          <thead class="table-light">
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Téléphone</th>
              <th>Profil</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($searchResults as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><?= htmlspecialchars($user['phone']) ?></td>
              <td>
                <a href="?profile_id=<?= $user['id'] ?>" class="btn btn-outline-info btn-sm">Voir</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <?php if (isset($_GET['profile_id'])): 
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_GET['profile_id']]);
        $profile = $stmt->fetch();

        $stmtA = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY date DESC");
        $stmtA->execute([$_GET['profile_id']]);
        $history = $stmtA->fetchAll();

        $stmtR = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
        $stmtR->execute([$_GET['profile_id']]);
        $reports = $stmtR->fetchAll();
      ?>
      <div class="card mt-5 shadow-sm">
        <div class="card-body">
          <h4 class="mb-4">Détails du profil</h4>
          <ul class="list-group mb-4">
            <li class="list-group-item"><strong>Nom :</strong> <?= htmlspecialchars($profile['name']) ?></li>
            <li class="list-group-item"><strong>Email :</strong> <?= htmlspecialchars($profile['email']) ?></li>
            <li class="list-group-item"><strong>Téléphone :</strong> <?= htmlspecialchars($profile['phone']) ?></li>
            <li class="list-group-item"><strong>Adresse :</strong> <?= htmlspecialchars($profile['address'] ?? 'Non spécifiée') ?></li>
          </ul>

          <h5 class="mt-4">Choisir un créneau horaire</h5>

          <form method="POST" action="../controllers/appointmentController.php">
            <input type="hidden" name="user_id" value="<?= $profile['id'] ?>">

            <div class="mb-3">
              <label for="location_id" class="form-label">Cabinet</label>
              <select name="location_id" id="location_id" class="form-select form-select-sm" required>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="table-responsive mb-3">
              <table class="table table-sm table-bordered text-center align-middle shadow-sm">
                <thead class="table-light">
                  <tr>
                    <?php
                      $start = new DateTime();
                      $start->modify('monday this week');
                      for ($i = 0; $i < 7; $i++):
                        $day = clone $start;
                        $day->modify("+$i day");
                    ?>
                      <th><?= $day->format('D d/m') ?></th>
                    <?php endfor; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php for ($h = 8; $h <= 19; $h++): ?>
                    <tr>
                      <?php for ($i = 0; $i < 7; $i++):
                        $date = clone $start;
                        $date->modify("+$i day");
                        $dateStr = $date->format('Y-m-d');
                        $hour = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $value = $dateStr . '|' . $hour;
                        $selectedLocation = $locations[0]['id'];
                        $currentCount = $appointmentCounts[$selectedLocation][$dateStr][$hour] ?? 0;
                      ?>
                        <td>
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="datetime" value="<?= $value ?>">
                            <label class="form-check-label small">
                              <?= $h ?>:00 (<?= $currentCount ?>/3)
                            </label>
                          </div>
                        </td>
                      <?php endfor; ?>
                    </tr>
                  <?php endfor; ?>
                </tbody>
              </table>
            </div>

            <button type="submit" name="book_for_patient_from_grid" class="btn btn-success btn-sm">Confirmer le rendez-vous</button>
          </form>

          <h5 class="mt-4">Historique des rendez-vous</h5>
          <ul class="list-group mb-4">
            <?php foreach ($history as $a): ?>
            <li class="list-group-item small"><?= htmlspecialchars($a['date']) ?> à <?= htmlspecialchars($a['hour']) ?> — <strong><?= htmlspecialchars($a['status']) ?></strong></li>
            <?php endforeach; ?>
          </ul>

          <h5>Rapports médicaux</h5>
          <ul class="list-group">
            <?php foreach ($reports as $r): ?>
            <li class="list-group-item small"><strong><?= htmlspecialchars($r['created_at']) ?>:</strong> <?= nl2br(htmlspecialchars($r['rapport'])) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include '../partials/footer.php'; ?>
