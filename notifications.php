<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$userId = $_SESSION['id'];
$userRole = $_SESSION['role'] ?? 'user';

try {
    // Fetch notifications based on user role
    if ($userRole === 'admin' || $userRole === 'therapist') {
        $stmt = $pdo->prepare("
            SELECT 
                n.*,
                u.name as user_name,
                u.role as user_role,
                h.name as handler_name,
                a.status as appointment_status,
                a.date as appointment_date,
                a.hour as appointment_time,
                a.user_id as appointment_user_id,
                l.name as location_name
            FROM notifications n
            LEFT JOIN users u ON n.user_id = u.id
            LEFT JOIN users h ON n.handled_by = h.id
            LEFT JOIN appointments a ON n.appointment_id = a.id
            LEFT JOIN locations l ON a.location_id = l.id
            ORDER BY n.created_at DESC
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                n.*,
                h.name as handler_name,
                a.status as appointment_status,
                a.date as appointment_date,
                a.hour as appointment_time,
                a.user_id as appointment_user_id,
                l.name as location_name
            FROM notifications n
            LEFT JOIN users h ON n.handled_by = h.id
            LEFT JOIN appointments a ON n.appointment_id = a.id
            LEFT JOIN locations l ON a.location_id = l.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$userId]);
    }
    $all_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle mark all as read
    if (isset($_GET['mark_all_read'])) {
        if ($userRole === 'admin' || $userRole === 'therapist') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
        header('Location: notifications.php');
        exit;
    }

    // Handle mark single notification as read
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'], $_POST['notif_id'])) {
        $notifId = intval($_POST['notif_id']);
        if ($userRole === 'admin' || $userRole === 'therapist') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$notifId]);
        } else {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notifId, $userId]);
        }
        header('Location: notifications.php');
        exit;
    }

    // Handle confirm/reject appointment
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['notif_id'])) {
        $notifId = intval($_POST['notif_id']);
        $action = $_POST['action'];
        
        // Get notification details with appointment information
        $stmt = $pdo->prepare("
            SELECT n.*, a.date, a.hour, l.name as location_name, u.name as handler_name
            FROM notifications n
            LEFT JOIN appointments a ON n.appointment_id = a.id
            LEFT JOIN locations l ON a.location_id = l.id
            LEFT JOIN users u ON u.id = ?
            WHERE n.id = ?
        ");
        $stmt->execute([$userId, $notifId]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notification) {
            // Update appointment status
            $newStatus = $action === 'confirm' ? 'confirmed' : 'cancelled';
            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $notification['appointment_id']]);
            
            // Create notification data
            $notificationData = [
                'appointment_id' => $notification['appointment_id'],
                'status' => $newStatus,
                'location' => $notification['location_name'],
                'date' => date('d/m/Y', strtotime($notification['date'])),
                'time' => date('H:i', strtotime($notification['hour'])),
                'created_at' => date('Y-m-d H:i:s'),
                'type' => $newStatus === 'confirmed' ? 'appointment_confirmed' : 'appointment_cancelled',
                'appointment_status' => $newStatus,
                'user_id' => $notification['user_id'],
                'handled_by' => $userId
            ];
            
            // Add a new notification for the client
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, appointment_id, handled_by, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $notification['user_id'],
                $notificationData['type'],
                $notification['appointment_id'],
                $userId
            ]);
            
            // Mark original notification as read
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$notifId]);
        }
        
        header('Location: notifications.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error in notifications.php: " . $e->getMessage());
    $error = "Une erreur est survenue lors du chargement des notifications.";
}

include 'partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <?php include 'partials/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Toutes les notifications</h1>
                <?php if (!empty($all_notifications)): ?>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="?mark_all_read=1" class="btn btn-sm btn-outline-primary">  
                        <i class="fas fa-check-double me-1"></i> Tout marquer comme lu
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($all_notifications)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Aucune notification.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($all_notifications as $notif): ?>
                                <div class="list-group-item <?= $notif['is_read'] ? '' : 'fw-bold' ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-circle text-<?= $notif['is_read'] ? 'secondary' : 'primary' ?> me-2" style="font-size: 0.5rem;"></i>
                                                <?php if ($userRole === 'admin' || $userRole === 'therapist'): ?>
                                                    <span class="notification-message">
                                                        <?php if ($notif['type'] === 'appointment_pending'): ?>
                                                            <?= htmlspecialchars($notif['user_name']) ?> a réservé un rendez-vous le <?= date('d/m/Y', strtotime($notif['appointment_date'])) ?> à <?= date('H:i', strtotime($notif['appointment_time'])) ?> à <?= htmlspecialchars($notif['location_name']) ?>
                                                        <?php elseif ($notif['type'] === 'appointment_cancelled'): ?>
                                                            Le rendez-vous du <?= date('d/m/Y', strtotime($notif['appointment_date'])) ?> à <?= date('H:i', strtotime($notif['appointment_time'])) ?> à <?= htmlspecialchars($notif['location_name']) ?> pour <?= htmlspecialchars($notif['user_name']) ?> a été annulé par <?= htmlspecialchars($notif['handler_name'] ?? 'vous') ?>.
                                                        <?php elseif ($notif['type'] === 'appointment_confirmed'): ?>
                                                            Le rendez-vous du <?= date('d/m/Y', strtotime($notif['appointment_date'])) ?> à <?= date('H:i', strtotime($notif['appointment_time'])) ?> à <?= htmlspecialchars($notif['location_name']) ?> pour <?= htmlspecialchars($notif['user_name']) ?> a été confirmé par <?= htmlspecialchars($notif['handler_name'] ?? 'vous') ?>.
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="notification-message">
                                                        <?php if ($notif['type'] === 'appointment_pending'): ?>
                                                            Votre rendez-vous du <?= date('d/m/Y', strtotime($notif['appointment_date'])) ?> à <?= date('H:i', strtotime($notif['appointment_time'])) ?> à <?= htmlspecialchars($notif['location_name']) ?> est en attente de confirmation.
                                                        <?php elseif ($notif['type'] === 'appointment_cancelled'): ?>
                                                            Votre rendez-vous du <?= date('d/m/Y', strtotime($notif['appointment_date'])) ?> à <?= date('H:i', strtotime($notif['appointment_time'])) ?> à <?= htmlspecialchars($notif['location_name']) ?> a été annulé<?= ($notif['handled_by'] != $_SESSION['id']) ? ' par ' . htmlspecialchars($notif['handler_name'] ?? 'vous') : '' ?>.
                                                        <?php elseif ($notif['type'] === 'appointment_confirmed'): ?>
                                                            Votre rendez-vous du <?= date('d/m/Y', strtotime($notif['appointment_date'])) ?> à <?= date('H:i', strtotime($notif['appointment_time'])) ?> à <?= htmlspecialchars($notif['location_name']) ?> a été confirmé<?= ($notif['handled_by'] != $_SESSION['id']) ? ' par ' . htmlspecialchars($notif['handler_name'] ?? 'vous') : '' ?>.
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (($userRole === 'admin' || $userRole === 'therapist') && $notif['type'] === 'appointment_pending' && $notif['appointment_status'] === 'pending'): ?>
                                                <div class="mt-2">
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                                        <input type="hidden" name="action" value="confirm">
                                                        <button type="submit" class="btn btn-sm btn-success me-2">
                                                            <i class="fas fa-check me-1"></i> Confirmer
                                                        </button>
                                                    </form>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times me-1"></i> Refuser
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <form method="post" class="ms-2">
                                                <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                                <input type="hidden" name="mark_read" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-check me-1"></i> Marquer comme lu
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?> 
