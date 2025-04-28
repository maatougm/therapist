<?php
session_start();
require '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get all kine pages
$kine_pages = [
    'kine_dashboard.php' => 'Tableau de bord',
    'manage/searchClient.php' => 'Rechercher un patient',
    'manage/bookFor.php' => 'Créer un rendez-vous',
    'manage/createFor.php' => 'Ajouter un utilisateur'
];

// Handle page access
if (isset($_GET['page'])) {
    $page = $_GET['page'];
    if (array_key_exists($page, $kine_pages)) {
        // Store the original admin session
        $_SESSION['admin_backup'] = $_SESSION['user'];
        // Set the session to kine role temporarily
        $_SESSION['user']['role'] = 'kine';
        // Redirect to the kine page
        header("Location: ../$page");
        exit;
    }
}

include '../partials/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <?php include '../partials/sidebar.php'; ?>
        </div>

        <!-- Main content -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0">Accès aux pages Kinés</h2>
            </div>

            <div class="row">
                <?php foreach ($kine_pages as $page => $title): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($title) ?></h5>
                                <p class="card-text text-muted">
                                    Accéder à la page <?= htmlspecialchars($title) ?> en tant que kiné
                                </p>
                                <a href="?page=<?= urlencode($page) ?>" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Accéder
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?> 