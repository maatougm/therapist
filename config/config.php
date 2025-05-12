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

// Function to get the current page URL
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Function to get the base path
function base_path() {
    return dirname(__DIR__);
}

// Function to get the assets path
function assets_path() {
    return base_path() . '/assets';
} 