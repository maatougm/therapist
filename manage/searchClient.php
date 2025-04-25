<?php
include '../partials/header.php';
include '../partials/nav.php';
require '../config/db.php';
session_start();

$searchResults = [];
if (isset($_POST['search'])) {
  $term = '%' . $_POST['search_term'] . '%';
  $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'client' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)");
  $stmt->execute([$term, $term, $term]);
  $searchResults = $stmt->fetchAll();
}
?>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 bg-light p-3">
      <?php include '../partials/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10 mt-4">
      <h3>Rechercher un patient</h3>

      <form method="POST" class="mb-4">
        <div class="input-group">
          <input type="text" name="search_term" class="form-control" placeholder="Nom, email ou téléphone..." >
          <button class="btn btn-primary" type="submit" name="search">Rechercher</button>
        </div>
      </form>

      <?php if (!empty($searchResults)): ?>
        <table class="table table-bordered table-hover table-sm">
          <thead class="table-light">
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Téléphone</th>
              <th>Voir Profil</th>
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
          <h4 class="mb-3">Détails du profil</h4>
          <ul class="list-group mb-4">
            <li class="list-group-item"><strong>Nom :</strong> <?= htmlspecialchars($profile['name']) ?></li>
            <li class="list-group-item"><strong>Email :</strong> <?= htmlspecialchars($profile['email']) ?></li>
            <li class="list-group-item"><strong>Téléphone :</strong> <?= htmlspecialchars($profile['phone']) ?></li>
            <li class="list-group-item"><strong>Genre :</strong> <?= htmlspecialchars($profile['gender'] ?? '-') ?></li>
            <li class="list-group-item"><strong>Date de naissance :</strong> <?= htmlspecialchars($profile['birthdate'] ?? '-') ?></li>
            <li class="list-group-item"><strong>Adresse :</strong> <?= htmlspecialchars($profile['address'] ?? '-') ?></li>
          </ul>

          <h5>Historique des rendez-vous</h5>
          <ul class="list-group mb-4">
            <?php foreach ($history as $a): ?>
              <li class="list-group-item small">
                <?= htmlspecialchars($a['date']) ?> à <?= htmlspecialchars($a['hour']) ?>
                — <strong><?= htmlspecialchars($a['status']) ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>

          <h5>Rapports médicaux</h5>
          <ul class="list-group">
            <?php foreach ($reports as $r): ?>
              <li class="list-group-item small">
                <strong><?= htmlspecialchars($r['created_at']) ?>:</strong><br>
                <?= nl2br(htmlspecialchars($r['rapport'])) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../partials/footer.php'; ?>
