<?php
function addNotification($pdo, $userId, $message, $data = []) {
    try {
        // Validate user ID
        if (!$userId) {
            error_log("Invalid user ID for notification: " . print_r($data, true));
            throw new Exception("Invalid user ID for notification");
        }

        // Extract appointment_id from data if it exists
        $appointmentId = $data['appointment_id'] ?? null;
        
        // Get handled_by from data if it exists
        $handledBy = $data['handled_by'] ?? null;
        
        // Determine notification type based on data
        $type = 'appointment';
        if (isset($data['status'])) {
            switch ($data['status']) {
                case 'confirmed':
                    $type = 'appointment_confirmed';
                    break;
                case 'cancelled':
                    $type = 'appointment_cancelled';
                    break;
                case 'pending':
                    $type = 'appointment_pending';
                    break;
            }
        }

        // Insert notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                user_id, 
                type, 
                appointment_id,
                handled_by,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt->execute([
            $userId,
            $type,
            $appointmentId,
            $handledBy
        ])) {
            error_log("Failed to execute notification insert: " . print_r($stmt->errorInfo(), true));
            throw new Exception("Failed to insert notification");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error in addNotification: " . $e->getMessage());
        throw $e;
    }
} 