<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/locationController.php';

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
            if ($pastAppointments < 4  ) {
                $status = 'pending';
            } elseif ($pastAppointments >= 4 && $slotAppointments < 3) {
                $status = 'confirmed';
            }

            // Insert the new appointment
            $stmt = $this->db->prepare("INSERT INTO appointments (user_id, location_id, date, hour, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $locationId, $date, $hour, $status]);

            return ['success' => true, 'message' => "Rendez-vous créé avec succès.", 'status' => $status];

        } catch (Exception $e) {
            error_log("Erreur lors de la création du rendez-vous: " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur interne."];
        }
    }

    // Cancel an existing appointment
    public function cancelAppointment($appointmentId) {
        try {
            $stmt = $this->db->prepare("UPDATE appointments SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$appointmentId]);
            return ['success' => true, 'message' => "Rendez-vous annulé."];
        } catch (Exception $e) {
            error_log("Erreur lors de l'annulation: " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur d'annulation."];
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
