<?php
require_once 'db.php';

class TherapistController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Get all therapists
    public function getAllTherapists() {
        try {
            $stmt = $this->db->query("
                SELECT u.*, GROUP_CONCAT(l.name) as locations
                FROM users u
                LEFT JOIN therapist_locations tl ON u.id = tl.therapist_id
                LEFT JOIN locations l ON tl.location_id = l.id
                WHERE u.role = 'kine'
                GROUP BY u.id
                ORDER BY u.name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching therapists: " . $e->getMessage());
            return [];
        }
    }

    // Get therapist by ID
    public function getTherapistById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, GROUP_CONCAT(l.id) as location_ids, GROUP_CONCAT(l.name) as locations
                FROM users u
                LEFT JOIN therapist_locations tl ON u.id = tl.therapist_id
                LEFT JOIN locations l ON tl.location_id = l.id
                WHERE u.id = ? AND u.role = 'kine'
                GROUP BY u.id
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching therapist: " . $e->getMessage());
            return null;
        }
    }

    // Create new therapist
    public function createTherapist($data) {
        try {
            $this->db->beginTransaction();

            // Create user
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, role, created_at) 
                VALUES (?, ?, ?, 'kine', NOW())
            ");
            
            $stmt->execute([
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT)
            ]);

            $therapistId = $this->db->lastInsertId();

            // Assign locations if provided
            if (!empty($data['locations'])) {
                $stmt = $this->db->prepare("INSERT INTO therapist_locations (therapist_id, location_id) VALUES (?, ?)");
                foreach ($data['locations'] as $locationId) {
                    $stmt->execute([$therapistId, $locationId]);
                }
            }

            $this->db->commit();
            return ['success' => true, 'id' => $therapistId];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating therapist: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating therapist'];
        }
    }

    // Update therapist
    public function updateTherapist($id, $data) {
        try {
            $this->db->beginTransaction();

            // Update user
            $sql = "UPDATE users SET name = ?, email = ?";
            $params = [$data['name'], $data['email']];

            if (!empty($data['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ? AND role = 'kine'";
            $params[] = $id;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // Update locations
            if (isset($data['locations'])) {
                // Remove existing locations
                $stmt = $this->db->prepare("DELETE FROM therapist_locations WHERE therapist_id = ?");
                $stmt->execute([$id]);

                // Add new locations
                if (!empty($data['locations'])) {
                    $stmt = $this->db->prepare("INSERT INTO therapist_locations (therapist_id, location_id) VALUES (?, ?)");
                    foreach ($data['locations'] as $locationId) {
                        $stmt->execute([$id, $locationId]);
                    }
                }
            }

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating therapist: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error updating therapist'];
        }
    }

    // Delete therapist
    public function deleteTherapist($id) {
        try {
            $this->db->beginTransaction();

            // Check for appointments
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM appointments WHERE therapist_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Cannot delete therapist with existing appointments'];
            }

            // Remove location assignments
            $stmt = $this->db->prepare("DELETE FROM therapist_locations WHERE therapist_id = ?");
            $stmt->execute([$id]);

            // Delete therapist
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND role = 'kine'");
            $stmt->execute([$id]);

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error deleting therapist: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error deleting therapist'];
        }
    }

    // Get therapist's appointments
    public function getTherapistAppointments($therapistId, $startDate = null, $endDate = null) {
        try {
            $sql = "
                SELECT a.*, u.name as client_name, l.name as location_name
                FROM appointments a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN locations l ON a.location_id = l.id
                WHERE a.therapist_id = ?
            ";
            $params = [$therapistId];

            if ($startDate && $endDate) {
                $sql .= " AND a.appointment_date BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            $sql .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching therapist appointments: " . $e->getMessage());
            return [];
        }
    }
} 