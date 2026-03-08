<?php
/**
 * Unauthorized Access Page
 */

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../config/constants.php';

Session::init();
$flash = Session::getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-md w-full text-center">
        
        <!-- Error Icon -->
        <div class="inline-flex items-center justify-center w-24 h-24 bg-red-100 rounded-full mb-6">
            <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <!-- Error Message -->
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Access Denied</h1>
        
        <?php if ($flash && $flash['type'] === 'error'): ?>
            <p class="text-lg text-gray-600 mb-8"><?= htmlspecialchars($flash['message']) ?></p>
        <?php else: ?>
            <p class="text-lg text-gray-600 mb-8">
                You don't have permission to access this page.
            </p>
        <?php endif; ?>

        <!-- Actions -->
        <div class="space-y-3">
            <a href="javascript:history.back()" 
               class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg shadow-md transition">
                Go Back
            </a>
            <a href="<?= BASE_URL ?>/public/login.php" 
               class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition">
                Return to Login
            </a>
        </div>

        <!-- Help Text -->
        <p class="text-sm text-gray-500 mt-8">
            If you believe this is an error, please contact your system administrator.
        </p>
    </div>

</body>
</html>
