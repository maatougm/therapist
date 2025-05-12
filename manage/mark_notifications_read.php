<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    // Update all unread notifications for the current user
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = :user_id AND is_read = 0
    ");
    
    $stmt->bindParam(':user_id', $_SESSION['id'], PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new PDOException('Failed to mark notifications as read: ' . implode(' ', $stmt->errorInfo()));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Notifications marquées comme lues'
    ]);

} catch (PDOException $e) {
    error_log("Database Error in mark_notifications_read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in mark_notifications_read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue: ' . $e->getMessage()
    ]);
} 