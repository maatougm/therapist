<?php
/**
 * Appointment Controller
 * 
 * Handles the creation and management of appointments.
 * Includes security checks, input validation, and database operations.
 */

// Include authentication and database configuration
require_once __DIR__ . '/../../config/auth.php';
requireRole('kine');

// Set response type to JSON
header('Content-Type: application/json');

/**
 * Request Method Validation
 * 
 * Ensures only POST requests are processed
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Validate CSRF token for security
validateCSRF();

try {
    /**
     * Required Fields Validation
     * 
     * Checks for presence of all required appointment fields
     */
    $required_fields = ['client_id', 'location_id', 'date', 'hour'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
            exit();
        }
    }

    /**
     * Input Sanitization and Validation
     * 
     * - Sanitizes all input to prevent XSS
     * - Validates IDs as integers
     * - Validates date and time formats
     */
    $client_id = filter_var($_POST['client_id'], FILTER_VALIDATE_INT);
    $location_id = filter_var($_POST['location_id'], FILTER_VALIDATE_INT);
    $date = sanitizeInput($_POST['date']);
    $hour = sanitizeInput($_POST['hour']);
    $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : null;

    // Validate IDs
    if (!$client_id || !$location_id) {
        echo json_encode(['success' => false, 'message' => 'ID invalide']);
        exit();
    }

    // Validate date and time formats
    if (!validateDate($date) || !validateTime($hour)) {
        echo json_encode(['success' => false, 'message' => 'Format de date ou heure invalide']);
        exit();
    }

    /**
     * Appointment Availability Check
     * 
     * Verifies the selected time slot is not already booked
     */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE date = ? AND hour = ? AND location_id = ?
    ");
    $stmt->execute([$date, $hour, $location_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ce créneau horaire est déjà pris']);
        exit();
    }

    /**
     * Appointment Creation
     * 
     * Inserts new appointment with:
     * - Client ID
     * - Therapist ID (from session)
     * - Location ID
     * - Date and time
     * - Optional notes
     * - Status set to 'confirmed'
     */
    $stmt = $pdo->prepare("
        INSERT INTO appointments (
            user_id, 
            therapist_id, 
            location_id, 
            date, 
            hour, 
            notes, 
            status, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW())
    ");

    $stmt->execute([
        $client_id,
        $_SESSION['user']['id'],
        $location_id,
        $date,
        $hour,
        $notes
    ]);

    /**
     * Fetch Created Appointment Details
     * 
     * Retrieves complete appointment information including:
     * - Client name
     * - Location details
     * - Appointment status
     */
    $appointment_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as client_name, l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.id = ?
    ");
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return success response with appointment details
    echo json_encode([
        'success' => true, 
        'message' => 'Rendez-vous créé et confirmé avec succès',
        'appointment' => $appointment
    ]);
} catch (PDOException $e) {
    // Log error and return error response
    error_log("Error creating appointment: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du rendez-vous']);
} 