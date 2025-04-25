<?php
include '../partials/header.php';
require '../config/db.php';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kine'])) {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'kine')");
  $stmt->execute([$name, $email, $password]);

  header("Location: admin_kines.php?added=1");
  exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_kine'])) {
  $id = $_POST['kine_id'];

  $pdo->prepare("DELETE FROM therapist_locations WHERE therapist_id = ?")->execute([$id]);
  $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'kine'")->execute([$id]);

  header("Location: admin_kines.php?deleted=1");
  exit;
}

$kines = $pdo->query("SELECT * FROM users WHERE role = 'kine' ORDER BY created_at DESC")->fetchAll();
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 col-lg-2 bg-light p-3">
      <?php include '../partials/sidebar.php'; ?>
    </div>

    <main class="col-md-9 col-lg-10 px-md-4 py-4">
      <h4>Gestion des Kinés</h4>

      <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">Kiné ajouté avec succès.</div>
      <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-warning">Kiné supprimé avec succès.</div>
      <?php endif; ?>

      <div class="card mb-4">
        <div class="card-body">
          <h5>Ajouter un kiné</h5>
          <form method="POST">
            <input type="hidden" name="add_kine" value="1">
            <div class="row">
              <div class="col-md-4 mb-3">
                <label>Nom complet :</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="col-md-4 mb-3">
                <label>Email :</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="col-md-4 mb-3">
                <label>Mot de passe :</label>
                <input type="password" name="password" class="form-control" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
          </form>
        </div>
      </div>

      <table class="table table-striped">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kines as $kine): ?>
            <tr>
              <td><?= htmlspecialchars($kine['name']) ?></td>
              <td><?= htmlspecialchars($kine['email']) ?></td>
              <td>
                <form method="POST" style="display:inline-block">
                  <input type="hidden" name="delete_kine" value="1">
                  <input type="hidden" name="kine_id" value="<?= $kine['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </main>
  </div>
</div>
