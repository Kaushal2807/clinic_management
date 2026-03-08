<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/constants.php';

// Perform logout
Auth::logout();

// Redirect to login page
header('Location: ' . BASE_URL . '/public/login.php?logged_out=1');
exit;
