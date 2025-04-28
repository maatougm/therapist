<?php
// Database configuration
$host = 'localhost';
$db   = 'kene_therapy';
$user = 'root';  // Using root temporarily
$pass = '';      // Empty password for root
$charset = 'utf8mb4';

// Enable error reporting temporarily for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Show the actual error for debugging
    die("Database connection error: " . $e->getMessage());
}
