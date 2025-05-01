<?php
// Include configuration
require_once __DIR__ . '/../config/config.php';

// Get user role from session
$role = $_SESSION['role'];

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
];

// Define navigation items for kine
$kine_nav_items = [
    'kine_dashboard.php' => ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord'],
    'manage/bookFor.php' => ['icon' => 'fas fa-calendar-plus', 'text' => 'Créer un RDV'],
    'manage/searchClient.php' => ['icon' => 'fas fa-search', 'text' => 'Rechercher un patient'],
    'manage/createfor.php' => ['icon' => 'fas fa-user-plus', 'text' => 'Créer un compte'],
];

// Define navigation items for client
$client_nav_items = [
    'user_dashboard.php' => ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord'],
    './mes_rendezvous.php' => ['icon' => 'fas fa-calendar', 'text' => 'Mes rendez-vous'],
    'profile.php' => ['icon' => 'fas fa-user', 'text' => 'Mon profil'],
];

// Function to check if current page matches navigation item
function isActivePage($page_path, $current_path) {
    // Check if the current path starts with the page path
    // Ensure we don't match partial segments (e.g., 'users' matching 'users_edit.php')
    // unless the page path itself is a directory
    if (substr($current_path, 0, strlen($page_path)) === $page_path) {
        // Check if the character immediately after the matched path is a '/' or the end of the string
        $next_char_index = strlen($page_path);
        if (!isset($current_path[$next_char_index]) || $current_path[$next_char_index] === '/') {
            return true;
        }
    }
    return false;
}
?>

<!-- Link to external CSS -->
<link rel="stylesheet" href="<?php echo url('assets/css/sidebar.css'); ?>">

<nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <a href="<?php echo url('profile.php'); ?>" class="text-decoration-none">
            <div class="text-center mb-4">
                <div class="user-avatar mb-3">
                    <i class="fas fa-user-circle fa-4x text-light"></i>
                </div>
                <h6 class="text-light mb-1">
                    <?php
                    switch($role) {
                        case 'admin':
                            echo 'Administrateur';
                            break;
                        case 'therapist':
                            echo 'Kinésithérapeute';
                            break;
                        case 'client':
                            echo 'Patient';
                            break;
                        default:
                            echo 'Utilisateur';
                    }
                    ?>
                </h6>
                <small class="text-muted">
                    <?php
                    switch($role) {
                        case 'admin':
                            echo 'Admin';
                            break;
                        case 'therapist':
                            echo 'Kinésithérapeute';
                            break;
                        case 'client':
                            echo 'Patient';
                            break;
                        default:
                            echo 'Utilisateur';
                    }
                    ?>
                </small>
            </div>
        </a>

        <hr class="text-light">

        <ul class="nav flex-column">
            <?php if ($role === 'admin'): ?>
                <!-- Admin Navigation -->
                <?php foreach ($admin_nav_items as $path => $item): ?>
                    <li class="nav-item">
                        <a class="nav-link text-light <?php echo isActivePage($path, $current_path) ? 'active' : ''; ?>"
                           href="<?php echo url($path); ?>">
                            <i class="<?php echo $item['icon']; ?> me-2"></i>
                            <?php echo $item['text']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <!-- Kine Tabs Section -->
                <?php
                    $is_kine_section_active = false;
                    foreach ($kine_nav_items as $path => $item) {
                        if (isActivePage($path, $current_path)) {
                            $is_kine_section_active = true;
                            break;
                        }
                    }
                ?>
                <li class="nav-item mt-3">
                    <a class="nav-link text-light" data-bs-toggle="collapse" href="#kineTabs" role="button"
                       aria-expanded="<?= $is_kine_section_active ? 'true' : 'false' ?>"
                       aria-controls="kineTabs">
                        <i class="fas fa-user-md me-2"></i>
                        Voir les onglets kiné
                    </a>
                    <div class="collapse <?= $is_kine_section_active ? 'show' : '' ?>" id="kineTabs">
                        <ul class="nav flex-column ms-3">
                            <?php foreach ($kine_nav_items as $page => $item): ?>
                                <li class="nav-item">
                                    <a class="nav-link text-light <?php echo isActivePage($page, $current_path) ? 'active' : ''; ?>"
                                       href="<?= url($page) ?>">
                                        <i class="<?= $item['icon'] ?> me-2"></i>
                                        <?= $item['text'] ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php elseif ($role === 'therapist'): ?>
                <!-- Kine Navigation -->
                <?php foreach ($kine_nav_items as $path => $item): ?>
                    <li class="nav-item">
                        <a class="nav-link text-light <?php echo isActivePage($path, $current_path) ? 'active' : ''; ?>"
                           href="<?php echo url($path); ?>">
                            <i class="<?php echo $item['icon']; ?> me-2"></i>
                            <?php echo $item['text']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Client Navigation -->
                <?php foreach ($client_nav_items as $path => $item): ?>
                    <li class="nav-item">
                        <a class="nav-link text-light <?php echo isActivePage($path, $current_path) ? 'active' : ''; ?>"
                           href="<?php echo url($path); ?>">
                            <i class="<?php echo $item['icon']; ?> me-2"></i>
                            <?php echo $item['text']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <div class="mt-4">
            <a href="<?= url('index.php') ?>" class="btn btn-outline-light w-100">
                <i class="fas fa-home me-2"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>