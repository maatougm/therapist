<?php
/**
 * Configuration File
 * 
 * Contains global configuration settings for the application.
 */

// Base URL configuration
$base_url = '/pfaa/'; // Change this to match your project's base URL

// Function to generate absolute URLs
function url($path = '') {
    global $base_url;
    return $base_url . ltrim($path, '/');
} 