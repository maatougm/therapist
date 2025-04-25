<?php include 'partials/header.php'; ?>
<div class="container mt-5">
  <h2>Inscription</h2>
  <form action="controllers/registerController.php" method="POST">
    <div class="mb-3">
      <label>Nom complet :</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Email :</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Mot de passe :</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Téléphone :</label>
      <input type="text" name="phone" class="form-control">
    </div>
    <div class="mb-3">
      <label>Date de naissance :</label>
      <input type="date" name="birthdate" class="form-control">
    </div>
    <div class="mb-3">
      <label>Sexe :</label>
      <select name="gender" class="form-control">
        <option value="Homme">Homme</option>
        <option value="Femme">Femme</option>
      </select>
    </div>
    <div class="mb-3">
      <label>Adresse :</label>
      <input type="text" name="address" class="form-control">
    </div>
    <button type="submit" name="register" class="btn btn-success">S'inscrire</button>
  </form>
</div>
<?php include 'partials/footer.php'; ?>
