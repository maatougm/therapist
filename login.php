<?php include 'partials/header.php'; ?>
<div class="container mt-5">
  <h2>Connexion</h2>
  <form action="controllers/loginController.php" method="POST">
    <div class="mb-3">
      <label>Email:</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Mot de passe:</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" name="login" class="btn btn-primary">Se connecter</button>
  </form>
</div>
<?php include 'partials/footer.php'; ?>
