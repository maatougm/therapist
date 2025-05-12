<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Start output buffering
ob_start();

try {
    session_start();
    
    // Debug session
    error_log("Session data: " . print_r($_SESSION, true));
    
    require_once __DIR__ . '/../config/db.php';

    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        throw new Exception('User not logged in');
    }

    // Debug database connection
    error_log("Testing database connection...");
    $testStmt = $pdo->query("SELECT 1");
    if (!$testStmt) {
        throw new Exception('Database connection test failed');
    }
    error_log("Database connection successful");

    // Get notifications for the current user with all necessary joins
    $query = "
        SELECT 
            n.id,
            n.type,
            n.appointment_id,
            n.is_read,
            n.created_at,
            n.handled_by,
            COALESCE(a.date, '') as appointment_date,
            COALESCE(a.hour, '') as appointment_time,
            COALESCE(a.status, '') as appointment_status,
            COALESCE(l.name, '') as location_name,
            COALESCE(u.name, '') as client_name,
            COALESCE(h.name, '') as handled_by_name,
            CASE 
                WHEN n.is_read = 1 THEN 'read'
                ELSE 'unread'
            END as status
        FROM notifications n
        LEFT JOIN appointments a ON n.appointment_id = a.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN users h ON n.handled_by = h.id
        WHERE " . ($_SESSION['role'] === 'admin' ? "1=1" : "n.user_id = :user_id") . "
        ORDER BY n.created_at DESC
        LIMIT 10
    ";

    error_log("Preparing query for user ID: " . $_SESSION['id'] . " and role: " . $_SESSION['role']);
    
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        throw new PDOException('Failed to prepare statement: ' . implode(' ', $pdo->errorInfo()));
    }

    if ($_SESSION['role'] !== 'admin') {
        $userId = $_SESSION['id'];
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    }
    
    error_log("Executing query...");
    if (!$stmt->execute()) {
        throw new PDOException('Failed to execute statement: ' . implode(' ', $stmt->errorInfo()));
    }

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($notifications === false) {
        throw new PDOException('Failed to fetch notifications: ' . implode(' ', $stmt->errorInfo()));
    }

    error_log("Found " . count($notifications) . " notifications");

    // Count unread notifications
    $unreadCount = 0;
    foreach ($notifications as &$notification) {
        if ($notification['status'] === 'unread') {
            $unreadCount++;
        }

        // Format the date
        if ($notification['created_at']) {
            $notification['created_at'] = date('d/m/Y H:i', strtotime($notification['created_at']));
        }
        
        // Format appointment date and time if they exist
        if (!empty($notification['appointment_date'])) {
            $notification['date'] = date('d/m/Y', strtotime($notification['appointment_date']));
        }
        if (!empty($notification['appointment_time'])) {
            $notification['time'] = date('H:i', strtotime($notification['appointment_time']));
        }

        // Format the message based on type
        if ($notification['type'] === 'appointment_confirmed') {
            $notification['message'] = "Votre rendez-vous du " . $notification['date'] . 
                                     " à " . $notification['time'] . 
                                     " à " . $notification['location_name'] . " a été confirmé";
        } elseif ($notification['type'] === 'appointment_cancelled') {
            $notification['message'] = "Votre rendez-vous du " . $notification['date'] . 
                                     " à " . $notification['time'] . 
                                     " à " . $notification['location_name'] . " a été annulé";
        } elseif ($notification['type'] === 'new_appointment') {
            $notification['message'] = $notification['client_name'] . " a réservé un rendez-vous le " . 
                                     $notification['date'] . 
                                     " à " . $notification['time'] . 
                                     " à " . $notification['location_name'];
        } elseif ($notification['type'] === 'appointment_pending') {
            $notification['message'] = "Nouvelle demande de rendez-vous de " . $notification['client_name'] . 
                                     " le " . $notification['date'] . 
                                     " à " . $notification['time'] . 
                                     " à " . $notification['location_name'];
        } else {
            // Default message if type is not recognized
            $notification['message'] = "Nouvelle notification";
        }
    }

    $response = [
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ];

    // Clear any previous output
    ob_clean();
    
    // Set headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Send response
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database Error in fetch_notifications.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    error_log("Error Info: " . print_r($e->errorInfo, true));
    
    // Clear any previous output
    ob_clean();
    
    // Set headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in fetch_notifications.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any previous output
    ob_clean();
    
    // Set headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue: ' . $e->getMessage()
    ]);
} finally {
    // End output buffering
    ob_end_flush();
} 