<?php
require_once __DIR__ . '/../config/db.php';

class LocationController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // Get all locations
    public function getAllLocations() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM locations ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching locations: " . $e->getMessage());
            return [];
        }
    }

    // Get location by ID
    public function getLocationById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM locations WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching location: " . $e->getMessage());
            return null;
        }
    }

    // Create new location
    public function createLocation($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO locations (name, address, city, postal_code, phone, email, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $data['name'],
                $data['address'],
                $data['city'],
                $data['postal_code'],
                $data['phone'],
                $data['email']
            ]);
        } catch (PDOException $e) {
            error_log("Error creating location: " . $e->getMessage());
            return false;
        }
    }

    // Update location
    public function updateLocation($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE locations 
                SET name = ?, address = ?, city = ?, postal_code = ?, phone = ?, email = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $data['name'],
                $data['address'],
                $data['city'],
                $data['postal_code'],
                $data['phone'],
                $data['email'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating location: " . $e->getMessage());
            return false;
        }
    }

    // Delete location
    public function deleteLocation($id) {
        try {
            // First check if location is assigned to any therapists
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM therapist_locations WHERE location_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                return ['success' => false, 'message' => 'Cannot delete location as it is assigned to therapists'];
            }

            $stmt = $this->pdo->prepare("DELETE FROM locations WHERE id = ?");
            return ['success' => $stmt->execute([$id])];
        } catch (PDOException $e) {
            error_log("Error deleting location: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error deleting location'];
        }
    }

    // Get locations assigned to a therapist
    public function getTherapistLocations($therapistId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT l.* FROM locations l
                JOIN therapist_locations tl ON l.id = tl.location_id
                WHERE tl.therapist_id = ?
                ORDER BY l.name ASC
            ");
            $stmt->execute([$therapistId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching therapist locations: " . $e->getMessage());
            return [];
        }
    }

    // Assign location to therapist
    public function assignLocationToTherapist($therapistId, $locationId) {
        try {
            // Check if assignment already exists
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM therapist_locations WHERE therapist_id = ? AND location_id = ?");
            $stmt->execute([$therapistId, $locationId]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Location already assigned to therapist'];
            }

            $stmt = $this->pdo->prepare("INSERT INTO therapist_locations (therapist_id, location_id) VALUES (?, ?)");
            return ['success' => $stmt->execute([$therapistId, $locationId])];
        } catch (PDOException $e) {
            error_log("Error assigning location to therapist: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error assigning location'];
        }
    }

    // Remove location from therapist
    public function removeLocationFromTherapist($therapistId, $locationId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM therapist_locations WHERE therapist_id = ? AND location_id = ?");
            return ['success' => $stmt->execute([$therapistId, $locationId])];
        } catch (PDOException $e) {
            error_log("Error removing location from therapist: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error removing location'];
        }
    }

    public function getActiveLocations() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, address 
                FROM locations 
                WHERE status = 'active' 
                ORDER BY name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching locations: " . $e->getMessage());
            return [];
        }
    }

    public function isLocationActive($location_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT status 
                FROM locations 
                WHERE id = ?
            ");
            $stmt->execute([$location_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['status'] === 'active';
        } catch (PDOException $e) {
            error_log("Error checking location status: " . $e->getMessage());
            return false;
        }
    }
} 