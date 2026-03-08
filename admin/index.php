<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Get statistics
$totalClinics = $db->query("SELECT COUNT(*) as count FROM clinics")->fetch_assoc()['count'];
$activeClinics = $db->query("SELECT COUNT(*) as count FROM clinics WHERE is_active = 1")->fetch_assoc()['count'];
$totalUsers = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$recentLogins = $db->query("SELECT COUNT(*) as count FROM user_activity_logs WHERE action = 'login' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];

// Recent clinics
$recentClinics = $db->query("SELECT * FROM clinics ORDER BY created_at DESC LIMIT 5");

// Recent activity
$recentActivity = $db->query("
    SELECT ual.*, u.full_name, u.email 
    FROM user_activity_logs ual
    JOIN users u ON ual.user_id = u.id
    ORDER BY ual.created_at DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../assets/favicon/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="../assets/favicon/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1>
                    </div>
                    <nav class="ml-10 flex space-x-4">
                        <a href="index.php" class="px-3 py-2 rounded-md text-sm font-semibold text-indigo-600 bg-indigo-50">Dashboard</a>
                        <a href="clinics.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Clinics</a>
                        <a href="users.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Users</a>
                        <a href="activity.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Activity</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-700">
                        <span class="font-semibold"><?= htmlspecialchars(Session::getUserName()) ?></span>
                    </span>
                    <a href="<?= BASE_URL ?>/public/logout.php" 
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Dashboard Overview</h2>
            <p class="text-gray-600 mt-1">Monitor and manage your clinic management system</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total Clinics -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Total Clinics</p>
                        <p class="text-4xl font-bold text-gray-900 mt-2"><?= $totalClinics ?></p>
                        <p class="text-sm text-green-600 mt-2">
                            <span class="font-semibold"><?= $activeClinics ?></span> active
                        </p>
                    </div>
                    <div class="bg-indigo-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Total Users</p>
                        <p class="text-4xl font-bold text-gray-900 mt-2"><?= $totalUsers ?></p>
                        <p class="text-sm text-gray-500 mt-2">Across all clinics</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Recent Logins (24h) -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Logins (24h)</p>
                        <p class="text-4xl font-bold text-gray-900 mt-2"><?= $recentLogins ?></p>
                        <p class="text-sm text-gray-500 mt-2">User sessions</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="gradient-bg rounded-xl shadow-md p-6 text-white hover:shadow-lg transition">
                <p class="text-sm font-semibold mb-4 uppercase tracking-wide">Quick Actions</p>
                <div class="space-y-2">
                    <a href="clinics.php?action=add" class="block bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg px-4 py-2.5 text-sm font-medium transition">
                        ➕ Add New Clinic
                    </a>
                    <a href="users.php?action=add" class="block bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg px-4 py-2.5 text-sm font-medium transition">
                        👤 Add New User
                    </a>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Recent Clinics -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Recent Clinics</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php while ($clinic = $recentClinics->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center">
                                <?php if ($clinic['logo_path']): ?>
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($clinic['logo_path']) ?>" 
                                         class="w-12 h-12 rounded-lg object-cover mr-4">
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                                        <span class="text-indigo-600 font-bold text-xl">
                                            <?= strtoupper(substr($clinic['clinic_name'], 0, 1)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($clinic['clinic_name']) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($clinic['contact_email']) ?></p>
                                </div>
                            </div>
                            <a href="clinics.php?id=<?= $clinic['id'] ?>" 
                               class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                View →
                            </a>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="clinics.php" class="text-indigo-600 hover:text-indigo-700 font-semibold text-sm">
                            View All Clinics →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Recent Activity</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php while ($activity = $recentActivity->fetch_assoc()): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <?php if ($activity['action'] === 'login'): ?>
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                <?php else: ?>
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm text-gray-900">
                                    <span class="font-semibold"><?= htmlspecialchars($activity['full_name']) ?></span>
                                    <span class="text-gray-600"> <?= htmlspecialchars($activity['description']) ?></span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?= date('M d, Y \a\t h:i A', strtotime($activity['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="activity.php" class="text-indigo-600 hover:text-indigo-700 font-semibold text-sm">
                            View All Activity →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
