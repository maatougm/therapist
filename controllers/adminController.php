<?php
require '../config/db.php';

// Assign therapist to multiple locations
if (isset($_POST['assign_location'])) {
    $therapist_id = $_POST['therapist_id'] ?? null;
    $access = $_POST['access'] ?? [];

    if ($therapist_id) {
        // Clear existing assignments
        $stmt = $pdo->prepare("DELETE FROM therapist_locations WHERE therapist_id = ?");
        $stmt->execute([$therapist_id]);

        // Insert new assignments
        if (!empty($access)) {
            $stmt = $pdo->prepare("INSERT INTO therapist_locations (therapist_id, location_id) VALUES (?, ?)");
            foreach ($access as $location_id) {
                $stmt->execute([$therapist_id, $location_id]);
            }
        }

        header("Location: ../admin/therapist_access.php?updated=1");
        exit();
    } else {
        echo "Erreur : ID du thérapeute manquant.";
        exit();
    }
}

// Freeze a cabinet (location)
if (isset($_POST['freeze_location'])) {
    $location_id = $_POST['location_id'] ?? null;

    if ($location_id) {
        $stmt = $pdo->prepare("UPDATE locations SET status = 'frozen' WHERE id = ?");
        $stmt->execute([$location_id]);

        // Also delete all future appointments
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE date >= CURDATE() AND location_id = ?");
        $stmt->execute([$location_id]);

        header("Location: ../admin/locations.php?frozen=1");
        exit();
    } else {
        echo "Erreur : aucun cabinet sélectionné.";
        exit();
    }
}
// Unfreeze a cabinet (location)
if (isset($_POST['unfreeze_location'])) {
    $location_id = $_POST['location_id'] ?? null;

    if ($location_id) {
        $stmt = $pdo->prepare("UPDATE locations SET status = 'active' WHERE id = ?");
        $stmt->execute([$location_id]);

        // Redirect
        header("Location: ../admin/locations.php?status=active");
        exit();
    } else {
        header("Location: ../admin/locations.php?error=missing_location");
        exit();
    }
}

// Update cancellation delay
if (isset($_POST['update_cancel_limit'])) {
    $limit = intval($_POST['cancel_limit'] ?? 6);

    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'cancel_limit_hours'");
    $stmt->execute([$limit]);

    header("Location: ../admin/cancel_settings.php?limit_updated=1");
    exit();
}
