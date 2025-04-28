<?php
// Include configuration
require_once __DIR__ . '/../config/config.php';

// Since we removed auth, we'll use a default role for now
$role = 'admin'; // Change this to 'kine' for kine users

// Get current page path relative to base URL
$current_path = str_replace($base_url, '', $_SERVER['REQUEST_URI']);
$current_path = ltrim($current_path, '/');

// Define navigation items for admin
$admin_nav_items = [
    'admin_dashboard.php' => ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord'],
    'admin/users.php' => ['icon' => 'fas fa-users', 'text' => 'Gestion des utilisateurs'],
    'admin/therapists.php' => ['icon' => 'fas fa-user-md', 'text' => 'Gestion des kinés'],
    'admin/locations.php' => ['icon' => 'fas fa-map-marker-alt', 'text' => 'Gestion des lieux'],
    'admin/cancel_time.php' => ['icon' => 'fas fa-calendar-check', 'text' => 'cancel time'],
    'profile.php' => ['icon' => 'fas fa-user', 'text' => 'Mon profil']
];

// Define navigation items for kine
$kine_nav_items = [
    'kine_dashboard.php' => ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord'],
    'manage/bookFor.php' => ['icon' => 'fas fa-calendar-plus', 'text' => 'Créer un RDV'],
    'manage/searchClient.php' => ['icon' => 'fas fa-search', 'text' => 'Rechercher un patient'],
    'manage/appointments.php' => ['icon' => 'fas fa-calendar-check', 'text' => 'Mes rendez-vous'],
    'profile.php' => ['icon' => 'fas fa-user', 'text' => 'Mon profil']
];

// Get the current navigation items based on role
$nav_items = ($role === 'admin') ? $admin_nav_items : $kine_nav_items;

// Function to check if current page matches navigation item
function isActivePage($page_path, $current_path) {
    return strpos($current_path, $page_path) === 0;
}
?>

<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <!-- User Profile -->
        <div class="text-center mb-4">
            <div class="user-avatar mb-3">
                <i class="fas fa-user-circle fa-4x text-light"></i>
            </div>
            <h6 class="text-light mb-1"><?= $role === 'admin' ? 'Administrateur' : 'Kinésithérapeute' ?></h6>
            <small class="text-muted"><?= $role === 'admin' ? 'Administrateur' : 'Kinésithérapeute' ?></small>
        </div>

        <!-- Navigation Links -->
        <ul class="nav flex-column">
            <?php foreach ($nav_items as $page => $item): ?>
                <li class="nav-item">
                    <a class="nav-link text-light <?= isActivePage($page, $current_path) ? 'active' : '' ?>" href="<?= url($page) ?>">
                        <i class="<?= $item['icon'] ?> me-2"></i>
                        <?= $item['text'] ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <?php if ($role === 'admin'): ?>
                <!-- Kine Tabs Section -->
                <li class="nav-item mt-3">
                    <a class="nav-link text-light" data-bs-toggle="collapse" href="#kineTabs" role="button" aria-expanded="false" aria-controls="kineTabs">
                        <i class="fas fa-user-md me-2"></i>
                        Voir les onglets kiné
                    </a>
                    <div class="collapse" id="kineTabs">
                        <ul class="nav flex-column ms-3">
                            <?php foreach ($kine_nav_items as $page => $item): ?>
                                <li class="nav-item">
                                    <a class="nav-link text-light" href="<?= url($page) ?>">
                                        <i class="<?= $item['icon'] ?> me-2"></i>
                                        <?= $item['text'] ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Return to Home Button -->
        <div class="mt-4">
            <a href="<?= url('index.php') ?>" class="btn btn-outline-light w-100">
                <i class="fas fa-home me-2"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>
</nav>

<!-- Add Bootstrap JS for collapse functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
