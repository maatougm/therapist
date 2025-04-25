<?php
include '../partials/header.php';
include '../partials/nav.php';
require '../config/db.php';
session_start();
?>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 bg-light p-3">
      <?php include '../partials/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10 mt-4">
      <h3>Créer un compte patient (Kiné)</h3>
      <form method="POST" action="../controllers/registerController.php">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Nom complet :</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Email :</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Téléphone :</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="col-md-6 mb-3">
            <label>Genre :</label>
            <select name="gender" class="form-select">
              <option value="">-- Sélectionner --</option>
              <option value="male">Homme</option>
              <option value="female">Femme</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label>Date de naissance :</label>
            <input type="date" name="birthdate" class="form-control">
          </div>
          <div class="col-md-6 mb-3">
            <label>Adresse :</label>
            <input type="text" name="address" class="form-control">
          </div>
          <div class="col-md-6 mb-3">
            <label>Mot de passe :</label>
            <input type="text" name="password" class="form-control" value="patient123" required>
          </div>
        </div>
        <button type="submit" name="register_by_kine" class="btn btn-primary">Créer le compte</button>
      </form>

      <!-- View & Edit previous reports -->
      <hr class="my-5">
      <h4>Historique médical existant (si applicable)</h4>
      <form method="POST" action="../controllers/kineController.php">
        <div class="mb-3">
          <label for="rapport">Rapport médical :</label>
          <textarea name="rapport" class="form-control" rows="4" placeholder="Ajouter ou modifier un rapport médical..."></textarea>
        </div>
        <button type="submit" name="save_rapport" class="btn btn-secondary">Enregistrer le rapport</button>
      </form>
    </div>
  </div>
</div>

<?php include '../partials/footer.php'; ?>
