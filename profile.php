<?php
session_start();
require 'config/db.php';

$user_id = $_SESSION['user']['id'];

// Load current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If the form is submitted
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$name, $email, $phone, $address, $user_id]);

    $_SESSION['user']['name'] = $name; // Update the name in the session too

    header("Location: profile.php?updated=1");
    exit();
}
?>

<?php include 'partials/header.php'; ?>
<link rel="stylesheet" href="./config/theme.css">

<div class="container py-5">
    <h2 class="mb-4 text-primary">Mon Profil</h2>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Profil mis à jour avec succès !</div>
    <?php endif; ?>

    <div class="card shadow border-primary">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Nom complet</label>
                    <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($user['name']) ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Téléphone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Adresse</label>
                    <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($user['address']) ?>">
                </div>

                <div class="text-end">
                    <button type="submit" name="update_profile" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
