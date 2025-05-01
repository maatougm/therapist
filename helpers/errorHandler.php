<?php
function setError($message) {
    $_SESSION['error'] = $message;
}

function setSuccess($message) {
    $_SESSION['success'] = $message;
}

function redirectWithError($url, $message) {
    setError($message);
    header("Location: $url");
    exit();
}

function redirectWithSuccess($url, $message) {
    setSuccess($message);
    header("Location: $url");
    exit();
}

function handleException($e, $redirectUrl) {
    error_log("Error: " . $e->getMessage());
    redirectWithError($redirectUrl, "Une erreur est survenue. Veuillez réessayer.");
}
?> 