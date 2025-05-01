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

    public function bookAppointment($userId, $therapistId, $date, $time) {
        try {
            $this->db->beginTransaction();  

            // Insert appointment
            $stmt = $this->db->prepare("
                INSERT INTO appointments (user_id, date, hour, status)
                VALUES (?, ?, ?, 'scheduled')
            ");
            $stmt->execute([$userId, $date, $time]);
            $appointmentId = $this->db->lastInsertId();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function cancelAppointment($appointmentId, $userId, $therapistId) {
        try {
            $this->db->beginTransaction();

            // Update appointment status
            $stmt = $this->db->prepare("
                UPDATE appointments
                SET status = 'cancelled'
                WHERE id = ?
            ");
            $stmt->execute([$appointmentId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function rescheduleAppointment($appointmentId, $userId, $therapistId, $newDate, $newTime) {
        try {
            $this->db->beginTransaction();

            // Update appointment
            $stmt = $this->db->prepare("
                UPDATE appointments
                SET date = ?, hour = ?, status = 'rescheduled'
                WHERE id = ?
            ");
            $stmt->execute([$newDate, $newTime, $appointmentId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Get all appointments
    public function getAllAppointments($filters = []) {
        try {
            $sql = "
                SELECT a.*, 
                       u.name as client_name,
                       l.name as location_name
                FROM appointments a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN locations l ON a.location_id = l.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filters['start_date'])) {
                $sql .= " AND a.appointment_date >= ?";
                $params[] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $sql .= " AND a.appointment_date <= ?";
                $params[] = $filters['end_date'];
            }

            if (!empty($filters['user_id'])) {
                $sql .= " AND a.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND a.status = ?";
                $params[] = $filters['status'];
            }

            $sql .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching appointments: " . $e->getMessage());
            return [];
        }
    }

    // Get appointment by ID
    public function getAppointmentById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       u.name as client_name,
                       l.name as location_name
                FROM appointments a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN locations l ON a.location_id = l.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching appointment: " . $e->getMessage());
            return null;
        }
    }

    // Create new appointment
    public function createAppointment($data) {
        try {
            $required_fields = ['client_id', 'date', 'hour', 'location_id'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Tous les champs obligatoires doivent être remplis."];
                }
            }

            if (!$this->locationController->isLocationActive($data['location_id'])) {
                return ['success' => false, 'message' => "Le lieu sélectionné n'est pas disponible."];
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE location_id = ? 
                AND date = ? 
                AND hour = ?
                AND status != 'cancelled'
            ");
            $stmt->execute([
                $data['location_id'],
                $data['date'],
                $data['hour']
            ]);
            
            if ($stmt->fetchColumn() >= 3) {
                return ['success' => false, 'message' => "Ce créneau horaire est complet pour ce lieu."];
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE user_id = ? 
                AND date = ? 
                AND hour = ?
                AND status != 'cancelled'
            ");
            $stmt->execute([
                $data['client_id'],
                $data['date'],
                $data['hour']
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => "Le client a déjà un rendez-vous à cette heure."];
            }

            $stmt = $this->db->prepare("
                INSERT INTO appointments (
                    user_id, 
                    location_id, 
                    date, 
                    hour, 
                    notes, 
                    status, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([
                $data['client_id'],
                $data['location_id'],
                $data['date'],
                $data['hour'],
                $data['notes'] ?? null
            ]);

            return ['success' => true, 'message' => "Le rendez-vous a été créé avec succès."];
        } catch (Exception $e) {
            error_log("Error creating appointment: " . $e->getMessage());
            return ['success' => false, 'message' => "Une erreur est survenue lors de la création du rendez-vous."];
        }
    }

    // Update appointment
    public function updateAppointment($id, $data) {
        try {
            // Check for conflicts if time is being changed
            if (isset($data['start_time']) || isset($data['end_time']) || isset($data['appointment_date'])) {
                $current = $this->getAppointmentById($id);
                $checkData = array_merge($current, $data);
                if (!$this->isTimeSlotAvailable($checkData, $id)) {
                    return ['success' => false, 'message' => 'Time slot is not available'];
                }
            }

            $sql = "UPDATE appointments SET ";
            $params = [];
            $updates = [];

            foreach ($data as $key => $value) {
                if (in_array($key, ['user_id', 'therapist_id', 'location_id', 'appointment_date', 
                                   'start_time', 'end_time', 'status', 'notes'])) {
                    $updates[] = "$key = ?";
                    $params[] = $value;
                }
            }

            $sql .= implode(', ', $updates);
            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            return ['success' => $result];
        } catch (PDOException $e) {
            error_log("Error updating appointment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error updating appointment'];
        }
    }

    // Delete appointment
    public function deleteAppointment($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM appointments WHERE id = ?");
            return ['success' => $stmt->execute([$id])];
        } catch (PDOException $e) {
            error_log("Error deleting appointment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error deleting appointment'];
        }
    }

    // Check if time slot is available
    private function isTimeSlotAvailable($data, $excludeId = null) {
        try {
            $sql = "
                SELECT COUNT(*) FROM appointments
                WHERE therapist_id = ?
                AND appointment_date = ?
                AND (
                    (start_time <= ? AND end_time > ?)
                    OR (start_time < ? AND end_time >= ?)
                    OR (start_time >= ? AND end_time <= ?)
                )
            ";
            $params = [
                $data['therapist_id'],
                $data['appointment_date'],
                $data['start_time'],
                $data['start_time'],
                $data['end_time'],
                $data['end_time'],
                $data['start_time'],
                $data['end_time']
            ];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() == 0;
        } catch (PDOException $e) {
            error_log("Error checking time slot: " . $e->getMessage());
            return false;
        }
    }

    // Get available time slots for a therapist on a specific date
    public function getAvailableTimeSlots($therapistId, $date, $duration = 60) {
        try {
            $stmt = $this->db->prepare("
                SELECT hour
                FROM appointments
                WHERE therapist_id = ?
                AND date = ?
                AND status != 'cancelled'
            ");
            $stmt->execute([$therapistId, $date]);
            $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $availableSlots = [];
            $startTime = strtotime('08:00');
            $endTime = strtotime('20:00');

            for ($time = $startTime; $time < $endTime; $time += $duration * 60) {
                $timeStr = date('H:i', $time);
                if (!in_array($timeStr, $bookedSlots)) {
                    $availableSlots[] = $timeStr;
                }
            }

            return $availableSlots;
        } catch (Exception $e) {
            error_log("Error getting available time slots: " . $e->getMessage());
            return [];
        }
    }
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Initialize controllers
$locationController = new LocationController();

// Get the action from the request
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        createAppointment();
        break;
    default:
        header('Location: /pfaa/kine_dashboard.php');
        exit();
}

function createAppointment() {
    global $pdo, $locationController;
    
    // Validate required fields
    $required_fields = ['client_id', 'date', 'hour', 'therapist_id', 'location_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
            header('Location: /pfaa/manage/bookFor.php');
            exit();
        }
    }

    try {
        // Check if location is active using LocationController
        if (!$locationController->isLocationActive($_POST['location_id'])) {
            $_SESSION['error'] = "Le lieu sélectionné n'est pas disponible.";
            header('Location: /pfaa/manage/bookFor.php');
            exit();
        }

        // Check if time slot is available (no more than 3 appointments per hour per location)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE location_id = ? 
            AND date = ? 
            AND hour = ?
        ");
        $stmt->execute([
            $_POST['location_id'],
            $_POST['date'],
            $_POST['hour']
        ]);
        
        if ($stmt->fetchColumn() >= 3) {
            $_SESSION['error'] = "Ce créneau horaire est complet pour ce lieu.";
            header('Location: /pfaa/manage/bookFor.php');
            exit();
        }

        // Check if client already has an appointment at this time
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE user_id = ? 
            AND date = ? 
            AND hour = ?
        ");
        $stmt->execute([
            $_POST['client_id'],
            $_POST['date'],
            $_POST['hour']
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Le client a déjà un rendez-vous à cette heure.";
            header('Location: /pfaa/manage/bookFor.php');
            exit();
        }

        // Prepare the SQL statement
        $stmt = $pdo->prepare("
            INSERT INTO appointments (
                user_id, 
                location_id, 
                date, 
                hour, 
                notes, 
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        // Execute the statement with the form data
        $stmt->execute([
            $_POST['client_id'],
            $_POST['location_id'],
            $_POST['date'],
            $_POST['hour'],
            $_POST['notes'] ?? null,
            'pending'
        ]);

        // Set success message
        $_SESSION['success'] = "Le rendez-vous a été créé avec succès.";
        
        // Redirect back to the appointments page
        header('Location: /pfaa/manage/appointments.php');
        exit();

    } catch (PDOException $e) {
        // Log the error and set error message
        error_log("Error creating appointment: " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la création du rendez-vous.";
        header('Location: /pfaa/manage/bookFor.php');
        exit();
    }
}

// Book appointment by client
if (isset($_POST['book_appointment'])) {
    $user_id = $_SESSION['user']['id'];
    $date = $_POST['date'];
    $hour = $_POST['hour'];
    $location_id = $_POST['location_id'];

    // Check if location is active
    $checkLocation = $pdo->prepare("SELECT status FROM locations WHERE id = ?");
    $checkLocation->execute([$location_id]);
    if ($checkLocation->fetchColumn() !== 'active') {
        header("Location: ../user_dashboard.php?error=location_closed");
        exit();
    }

    // Check if already booked that day
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $date]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: ../user_dashboard.php?error=already_booked");
        exit();
    }

    // Check if slot full (per location)
    $checkSlot = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE date = ? AND hour = ? AND location_id = ?");
    $checkSlot->execute([$date, $hour, $location_id]);
    if ($checkSlot->fetchColumn() >= 3) {
        header("Location: ../user_dashboard.php?error=slot_full");
        exit();
    }

    // Book it
    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, date, hour, location_id, status) VALUES (?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$user_id, $date, $hour, $location_id]);

    header("Location: ../user_dashboard.php?success=1");
    exit();
}

// Cancel appointment
if (isset($_POST['cancel_appointment'])) {
    $appt_id = $_POST['appointment_id'];
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ? AND TIMESTAMPDIFF(HOUR, NOW(), CONCAT(date, ' ', hour)) >= 6");
    $stmt->execute([$appt_id]);
    header("Location: ../user_dashboard.php?cancelled=1");
    exit();
}

// Book appointment by therapist (kine)
if (isset($_POST['book_for_patient']) || isset($_POST['book_for_patient_from_grid'])) {
    $user_id = $_POST['user_id'];
    $location_id = $_POST['location_id'];

    if (isset($_POST['datetime'])) {
        [$date, $hour] = explode('|', $_POST['datetime']);
    } else {
        $date = $_POST['date'];
        $hour = $_POST['hour'];
    }

    // Check if location is active
    $checkLocation = $pdo->prepare("SELECT status FROM locations WHERE id = ?");
    $checkLocation->execute([$location_id]);
    if ($checkLocation->fetchColumn() !== 'active') {
        header("Location: ../kine_dashboard.php?error=location_closed");
        exit();
    }

    // Check if user already has appointment that day
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $date]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: ../kine_dashboard.php?error=already_booked");
        exit();
    }

    // Check if the hour is not fully booked (max 3 per hour per location)
    $checkSlot = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE date = ? AND hour = ? AND location_id = ?");
    $checkSlot->execute([$date, $hour, $location_id]);
    if ($checkSlot->fetchColumn() >= 3) {
        header("Location: ../kine_dashboard.php?error=slot_full");
        exit();
    }
    
    // Book it
    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, date, hour, location_id, status) VALUES (?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$user_id, $date, $hour, $location_id]);

    header("Location: ../kine_dashboard.php?added=1");
    exit();
}
?>
