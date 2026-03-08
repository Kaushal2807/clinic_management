<?php
/**
 * Root Index - Redirect Handler
 * Automatically redirects to setup or login based on installation status
 */

// Check if system is installed
$isInstalled = file_exists(__DIR__ . '/.installed');

if ($isInstalled) {
    // System installed - redirect to login
    header('Location: public/login.php');
} else {
    // Not installed - redirect to setup wizard
    header('Location: setup.php');
}
exit;
