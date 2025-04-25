<?php
session_start();
require '../config/db.php';

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
    //reschedule_appointment
    if (isset($_POST['reschedule_appointment'])) {
        $id = $_POST['appointment_id'];
        $newDate = $_POST['new_date'];
        $newHour = $_POST['new_hour'];
    
        $stmt = $pdo->prepare("UPDATE appointments SET date = ?, hour = ? WHERE id = ?");
        $stmt->execute([$newDate, $newHour, $id]);
    
        echo "success";
        exit();
    }
    
    // Book it
    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, date, hour, location_id, status) VALUES (?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$user_id, $date, $hour, $location_id]);

    header("Location: ../kine_dashboard.php?added=1");
    exit();
}
?>
