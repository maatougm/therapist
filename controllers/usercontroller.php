<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/locationController.php';
require_once __DIR__ . '/../helpers/notificationHelper.php';

class AppointmentController {
    private $db;
    private $locationController;

    public function __construct($db) {
        $this->db = $db;
        $this->locationController = new LocationController();
    }

    // Create a new appointment
    public function createAppointment($userId, $locationId, $date, $hour) {
        try {
            // Check if location is active
            if (!$this->locationController->isLocationActive($locationId)) {
                return ['success' => false, 'message' => "Le lieu sélectionné n'est pas disponible."];
            }

            // Count past appointments of the user
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND date < CURDATE()");
            $stmt->execute([$userId]);
            $pastAppointments = $stmt->fetchColumn();

            // Count how many appointments already exist at this slot
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM appointments WHERE date = ? AND hour = ? AND location_id = ? AND status != 'confirmed'");
            $stmt->execute([$date, $hour, $locationId]);
            $slotAppointments = $stmt->fetchColumn();

            // Decide the status based on past appointments and slot availability
            $status = 'pending';
            if ($pastAppointments < 4) {
                $status = 'pending';
            } elseif ($pastAppointments >= 4 && $slotAppointments < 3) {
                $status = 'confirmed';
            }

            // Insert the new appointment
            $stmt = $this->db->prepare("INSERT INTO appointments (user_id, location_id, date, hour, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $locationId, $date, $hour, $status]);
            $appointmentId = $this->db->lastInsertId();

            // Get location and user details
            $stmt = $this->db->prepare("
                SELECT l.name as location_name, l.id as location_id,
                       u.name as client_name
                FROM locations l
                JOIN users u ON u.id = ?
                WHERE l.id = ?
            ");
            $stmt->execute([$userId, $locationId]);
            $details = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$details) {
                throw new Exception("Impossible de récupérer les détails du rendez-vous");
            }

            // Create appointment notification data
            $notificationData = [
                'appointment_id' => $appointmentId,
                'status' => $status,
                'location' => $details['location_name'],
                'date' => date('d/m/Y', strtotime($date)),
                'time' => date('H:i', strtotime($hour)),
                'client_name' => $details['client_name'],
                'created_at' => date('Y-m-d H:i:s'),
                'appointment_status' => 'pending',
                'type' => 'appointment_pending',
                'user_id' => $userId,
                'handled_by' => $userId
            ];

            // Notify only the client
            addNotification(
                $this->db,
                $userId,
                '',  // Empty message as we'll format it in the display
                $notificationData
            );

            return ['success' => true, 'message' => "Rendez-vous créé avec succès.", 'status' => $status];

        } catch (Exception $e) {
            error_log("Erreur lors de la création du rendez-vous: " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur lors de la création du rendez-vous: " . $e->getMessage()];
        }
    }

    // Cancel an existing appointment
    public function cancelAppointment($appointmentId) {
        try {
            // Get appointment details before cancellation
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       l.name as location_name,
                       c.name as client_name,
                       t.name as therapist_name
                FROM appointments a
                LEFT JOIN locations l ON a.location_id = l.id
                LEFT JOIN users c ON a.user_id = c.id
                LEFT JOIN users t ON a.therapist_id = t.id
                WHERE a.id = ?
            ");
            $stmt->execute([$appointmentId]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment) {
                return ['success' => false, 'message' => "Rendez-vous non trouvé."];
            }

            // Update appointment status
            $stmt = $this->db->prepare("UPDATE appointments SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$appointmentId]);

            // Create notification data
            $notificationData = [
                'appointment_id' => $appointmentId,
                'status' => 'cancelled',
                'location' => $appointment['location_name'],
                'date' => date('d/m/Y', strtotime($appointment['date'])),
                'time' => date('H:i', strtotime($appointment['hour'])),
                'client_name' => $appointment['client_name'],
                'created_at' => date('Y-m-d H:i:s'),
                'type' => 'appointment_cancelled',
                'appointment_status' => 'cancelled',
                'user_id' => $appointment['user_id'],
                'handled_by' => $_SESSION['id']
            ];

            // Notify only the client
            addNotification(
                $this->db,
                $appointment['user_id'],
                '',  // Empty message as we'll format it in the display
                $notificationData
            );

            return ['success' => true, 'message' => "Rendez-vous annulé."];
        } catch (Exception $e) {
            error_log("Erreur lors de l'annulation: " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur d'annulation: " . $e->getMessage()];
        }
    }

    // Retrieve all appointments by user
    public function getAppointmentsByUser($userId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY date DESC, hour DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur de récupération: " . $e->getMessage());
            return [];
        }
    }

    // Retrieve a specific appointment by ID
    public function getAppointmentById($appointmentId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la recherche du rendez-vous: " . $e->getMessage());
            return null;
        }
    }
}
