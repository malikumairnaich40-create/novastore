<?php
/**
 * Admin Panel Authentication Middleware
 */
require_once __DIR__ . '/../../config/config.php';

// Enforce Admin Access
if (!is_logged_in() || !is_admin()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/admin/index.php');
    set_flash('error', 'Administrator login required to access the admin portal.');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$currentAdmin = current_user();
