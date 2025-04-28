<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['client_id'])) {
    echo json_encode(['error' => 'ID client manquant']);
    exit();
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT id, name, email, phone 
        FROM users 
        WHERE id = ? AND role = 'client'
    ");
    $stmt->execute([$_GET['client_id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo json_encode(['error' => 'Client non trouvé']);
        exit();
    }

    echo json_encode($client);
} catch (PDOException $e) {
    error_log("Error fetching client info: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur lors de la récupération des informations']);
} 