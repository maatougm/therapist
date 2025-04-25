<?php
require '../config/db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $client_id = $_GET['id'];

    // Fetch client details
    $stmtClient = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
    $stmtClient->execute([$client_id]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);

    // Fetch past appointments for the client
    $stmtPastAppointments = $pdo->prepare("
        SELECT a.date, TIME_FORMAT(a.hour, '%H:%i') as time, l.name as location_name
        FROM appointments a
        JOIN locations l ON a.location_id = l.id
        WHERE a.user_id = ? AND a.date < CURRENT_DATE()
        ORDER BY a.date DESC, a.hour DESC
    ");
    $stmtPastAppointments->execute([$client_id]);
    $pastAppointments = $stmtPastAppointments->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the last report for the client
    $stmtLastReport = $pdo->prepare("
        SELECT report_text, created_at, title, therapist_id, report_date
        FROM reports
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmtLastReport->execute([$client_id]);
    $lastReport = $stmtLastReport->fetch(PDO::FETCH_ASSOC);

    $response = [
        'client' => $client,
        'pastAppointments' => $pastAppointments,
        'lastReport' => $lastReport
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} else {
    // Invalid client ID
    http_response_code(400);
    echo json_encode(['error' => 'Invalid client ID.']);
}
?>