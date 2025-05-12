<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Debug session
error_log("Header - Session data: " . print_r($_SESSION, true));

// Include configuration
require_once __DIR__ . '/../config/config.php';
?>

<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?? 'Sport Physio Plus' ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?php echo url('assets/images/logo.jpg'); ?>">

  <!-- Bootstrap 5.3.3 & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

  

  <!-- Custom Theme CSS -->
  <link rel="stylesheet" href="<?php echo url('assets/css/theme.css'); ?>">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="<?php echo isset($_SESSION['loggedin']) && $_SESSION['loggedin'] ? 'has-sidebar' : ''; ?>">
  <!-- Sidebar Toggle Button -->
  <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
      <i class="fas fa-bars"></i>
    </button>
  <?php endif; ?>

  <!-- Top Navigation -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <!-- Left side: Brand and Toggle -->
      <div class="d-flex align-items-center">
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
          <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
          </button>
        <?php endif; ?>
        <a class="navbar-brand d-flex align-items-center" href="/pfaa/index.php">
          <img src="<?php echo url('assets/images/logo.jpg'); ?>" alt="Sport Physio Plus Logo" class="navbar-logo me-2">
          Sport Physio Plus
        </a>
      </div>

      <!-- Right side: Theme Toggle, User Menu, Notification Bell -->
      <div class="d-flex align-items-center">
        <!-- Theme Toggle -->
        <button id="theme-toggle" class="btn btn-link text-light" aria-label="Toggle theme">
          <i class="fas fa-moon"></i>
        </button>

        <?php
          $loggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'];
          if ($loggedIn) {
            require_once __DIR__ . '/../config/db.php';
            $userId = $_SESSION['id'];
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $unreadCount = 0;
            foreach ($notifications as $notif) {
              if (!$notif['is_read']) $unreadCount++;
            }
          }
        ?>
        <?php if ($loggedIn): ?>
          <!-- Notification Bell -->
          <div class="dropdown me-3">
            <button class="btn btn-link position-relative text-light" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
              <i class="fas fa-bell fa-lg"></i>
              <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notifDropdown" id="notifDropdownMenu" style="min-width: 350px; max-width: 400px; max-height: 400px; overflow-y: auto; z-index: 1050;">
              <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                <span class="fw-bold">Notifications</span>
                <a href="/pfaa/notifications.php" class="text-primary small">Voir tout</a>
              </li>
              <li id="notifLoading" class="text-center py-3">
                <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;">
                  <span class="visually-hidden">Chargement...</span>
                </div>
              </li>
              <li id="notifEmpty" class="text-center py-3 d-none">
                <i class="fas fa-inbox fa-2x text-muted mb-2"></i><br>
                <span class="text-muted">Aucune notification</span>
              </li>
              <!-- Notifications will be injected here -->
            </ul>
          </div>
        <?php endif; ?>
        <div class="header-buttons">
          <?php if ($loggedIn): ?>
            <?php
              $role = $_SESSION['role'] ?? '';
              $dashboardUrl = '/pfaa/user_dashboard.php';
              if ($role === 'admin') $dashboardUrl = '/pfaa/admin_dashboard.php';
              elseif ($role === 'therapist') $dashboardUrl = '/pfaa/kine_dashboard.php';
            ?>
            <a href="<?= $dashboardUrl ?>" class="btn btn-success" title="Dashboard">
              <i class="fas fa-tachometer-alt me-1"></i> Dashboard
            </a>
            <a href="/pfaa/profile.php" class="btn btn-outline-primary" title="Profil">
              <i class="fas fa-user me-1"></i>
              <?php echo htmlspecialchars($_SESSION['name'] ?? 'Utilisateur'); ?>
            </a>
            <a href="/pfaa/logout.php" class="btn btn-outline-danger" title="Déconnexion">
              <i class="fas fa-sign-out-alt"></i>
            </a>
          <?php else: ?>
            <a href="/pfaa/login.php" class="btn btn-outline-primary">
              <i class="fas fa-sign-in-alt me-1"></i> Login
            </a>
            <a href="/pfaa/register.php" class="btn btn-primary">
              <i class="fas fa-user-plus me-1"></i> Register
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- Theme Toggle Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar toggle
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.querySelector('.sidebar');
      const mainContent = document.querySelector('.main-content');
      const body = document.body;
      
      if (sidebarToggle && sidebar && mainContent) {
        // Check for saved sidebar state
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
          sidebar.classList.add('collapsed');
          mainContent.classList.add('expanded');
          sidebarToggle.classList.add('collapsed');
        }
        
        sidebarToggle.addEventListener('click', function() {
          sidebar.classList.toggle('collapsed');
          mainContent.classList.toggle('expanded');
          sidebarToggle.classList.toggle('collapsed');
          
          // Save state
          localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
      }

      // Initialize notifications
      const notifDropdown = document.getElementById('notifDropdown');
      const notifDropdownMenu = document.getElementById('notifDropdownMenu');
      
      if (notifDropdown && notifDropdownMenu) {
        // Initialize Bootstrap dropdown
        const dropdown = new bootstrap.Dropdown(notifDropdown, {
          offset: [0, 10],
          popperConfig: {
            modifiers: [
              {
                name: 'preventOverflow',
                options: {
                  boundary: 'viewport'
                }
              }
            ]
          }
        });
        
        // Initial fetch
        fetchNotifications();
        
        // Poll every 30 seconds
        setInterval(fetchNotifications, 30000);
        
        // Mark as read on dropdown open
        notifDropdown.addEventListener('shown.bs.dropdown', function() {
          fetch('/pfaa/manage/mark_notifications_read.php')
            .then(res => res.json())
            .then(() => fetchNotifications())
            .catch(error => console.error('Error marking notifications as read:', error));
        });
      }
    });

    // Notification polling and UI
    function fetchNotifications() {
      console.log('Fetching notifications...');
      const loading = document.getElementById('notifLoading');
      const empty = document.getElementById('notifEmpty');
      const menu = document.getElementById('notifDropdownMenu');
      
      if (!loading || !empty || !menu) {
        console.error('Notification elements not found:', {
          loading: !!loading,
          empty: !!empty,
          menu: !!menu
        });
        return;
      }
      
      loading.classList.remove('d-none');
      empty.classList.add('d-none');
      
      fetch('/pfaa/manage/fetch_notifications.php')
        .then(res => {
          if (!res.ok) {
            throw new Error('Erreur réseau lors du chargement des notifications');
          }
          return res.json();
        })
        .then(data => {
          console.log('Received notifications:', data);
          
          if (!data.success) {
            throw new Error(data.error || 'Erreur lors du chargement des notifications');
          }
          
          const notifications = data.notifications || [];
          const unreadCount = data.unread_count || 0;
          
          // Update badge
          const badge = document.getElementById('notifBadge');
          if (badge) {
            if (unreadCount > 0) {
              badge.textContent = unreadCount;
              badge.classList.remove('d-none');
            } else {
              badge.classList.add('d-none');
            }
          }
          
          // Remove old notifications
          menu.querySelectorAll('.notification-item, .dropdown-divider').forEach(el => el.remove());
          
          // Hide loading
          loading.classList.add('d-none');
          
          if (!notifications.length) {
            empty.classList.remove('d-none');
            return;
          }
          
          // Show notifications
          notifications.forEach((notif, idx) => {
            const li = document.createElement('li');
            li.className = 'notification-item';
            
            // Add unread class if notification is unread
            if (notif.status === 'unread') {
              li.classList.add('notification-unread');
            }
            
            // Determine status color
            let statusColor = '';
            if (notif.type === 'appointment_cancelled') {
              statusColor = 'text-danger';
            } else if (notif.type === 'appointment_confirmed') {
              statusColor = 'text-success';
            } else if (notif.type === 'appointment_pending') {
              statusColor = 'text-warning';
            }
            
            li.innerHTML = `
              <a href="/pfaa/notifications.php" class="dropdown-item ${notif.status === 'unread' ? 'fw-bold' : ''}" tabindex="0" role="button">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-circle text-${notif.status === 'unread' ? 'primary' : 'secondary'} me-2" style="font-size: 0.5rem;"></i>
                    <span class="${statusColor}">${notif.message}</span>
                  </div>
                  <small class="text-muted ms-2">${notif.created_at}</small>
                </div>
                ${notif.location_name ? `<div class="notification-details small text-muted">
                  <div><i class="fas fa-map-marker-alt me-1"></i> ${notif.location_name}</div>
                  ${notif.date ? `<div><i class="fas fa-calendar me-1"></i> ${notif.date}</div>` : ''}
                  ${notif.time ? `<div><i class="fas fa-clock me-1"></i> ${notif.time}</div>` : ''}
                  ${notif.appointment_status ? `<div><i class="fas fa-info-circle me-1"></i> Statut: ${notif.appointment_status}</div>` : ''}
                </div>` : ''}
              </a>
            `;
            
            menu.appendChild(li);
            
            if (idx < notifications.length - 1) {
              const divider = document.createElement('li');
              divider.innerHTML = '<hr class="dropdown-divider">';
              menu.appendChild(divider);
            }
          });
        })
        .catch(error => {
          console.error('Error fetching notifications:', error);
          if (loading) loading.classList.add('d-none');
          if (empty) {
            empty.classList.remove('d-none');
            empty.innerHTML = `
              <div class="text-center">
                <i class="fas fa-exclamation-circle text-danger fa-2x mb-2"></i>
                <p class="text-danger mb-0">${error.message}</p>
              </div>
            `;
          }
        });
    }
  </script>

  <!-- Main Content Wrapper -->
  <div class="main-content">
    <!-- Start Main Container -->
    <div class="container-fluid">
      <div class="row">

    <!-- Add this before the closing </body> tag -->
    <script src="<?php echo url('assets/js/theme.js'); ?>"></script>
</body>
</html>
