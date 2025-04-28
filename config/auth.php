<?php
/**
 * Authentication Functions
 * 
 * Contains functions for user authentication and authorization.
 */

session_start();

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

/**
 * Require a specific role for access
 * @param string $role Required role (admin, therapist, client)
 * @return void
 */
function requireRole($role) {
    if (!isLoggedIn()) {
        header('Location: /pfaa/login.php');
        exit();
    }
    
    if ($_SESSION['role'] !== $role) {
        header('Location: /pfaa/login.php');
        exit();
    }
}

/**
 * Get current user's ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['id'] ?? null;
}

/**
 * Get current user's role
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
} 