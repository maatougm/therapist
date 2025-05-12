<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Security: Only admins allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $type = isset($_POST['type']) ? trim($_POST['type']) : 'system';
        $appointmentId = !empty($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
        
        if (empty($message)) {
            throw new Exception("Le message est requis");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, appointment_id, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$userId, $type, $message, $appointmentId]);
        
        $success = "Notification de test créée avec succès";
    } catch (Exception $e) {
        $error = "Erreur: " . $e->getMessage();
    }
}

// Get all users for dropdown
$stmt = $pdo->prepare("SELECT id, name, email, role FROM users ORDER BY name");
$stmt->execute();
$users = $stmt->fetchAll();

// Get all appointments for dropdown
$stmt = $pdo->prepare("
    SELECT a.id, a.date, a.hour, u.name as client_name, l.name as location_name
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN locations l ON a.location_id = l.id
    ORDER BY a.date DESC, a.hour DESC
    LIMIT 100
");
$stmt->execute();
$appointments = $stmt->fetchAll();

include '../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Créer une notification de test</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card