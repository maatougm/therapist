<?php
session_start();

if (isset($_POST['locations'])) {
    $_SESSION['selected_locations'] = explode(',', $_POST['locations']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?> 