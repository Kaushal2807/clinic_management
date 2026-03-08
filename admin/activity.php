<?php
/**
 * Admin - User Activity Logs
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filters
$filterUser = isset($_GET['user']) ? trim($_GET['user']) : '';
$filterAction = isset($_GET['action']) ? trim($_GET['action']) : '';

// Build query
$whereConditions = [];
$params = [];
$types = '';

if ($filterUser) {
    $whereConditions[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$filterUser%";
    $params[] = "%$filterUser%";
    $types .= 'ss';
}

if ($filterAction) {
    $whereConditions[] = "ual.action = ?";
    $params[] = $filterAction;
    $types .= 's';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total 
               FROM user_activity_logs ual
               JOIN users u ON ual.user_id = u.id
               $whereClause";

if ($params) {
    $stmt = $db->prepare($countQuery);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalLogs = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $totalLogs = $db->query($countQuery)->fetch_assoc()['total'];
}

$totalPages = ceil($totalLogs / $perPage);

// Fetch activity logs
$query = "SELECT ual.*, u.full_name, u.email, u.user_type, c.clinic_name
          FROM user_activity_logs ual
          JOIN users u ON ual.user_id = u.id
          LEFT JOIN clinics c ON u.clinic_id = c.id
          $whereClause
          ORDER BY ual.created_at DESC 
          LIMIT $perPage OFFSET $offset";

if ($params) {
    $stmt = $db->prepare($query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $activities = $stmt->get_result();
} else {
    $activities = $db->query($query);
}

// Get unique actions for filter
$actions = $db->query("SELECT DISTINCT action FROM user_activity_logs ORDER BY action");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity - <?= APP_NAME ?></title>
    
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
                <div class="flex items-center gap-8">
                    <h1 class="text-2xl font-bold gradient-bg bg-clip-text text-transparent">Admin Panel</h1>
                    <nav class="flex space-x-4">
                        <a href="index.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="clinics.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Clinics</a>
                        <a href="users.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Users</a>
                        <a href="activity.php" class="text-indigo-600 bg-indigo-50 px-3 py-2 rounded-md text-sm font-medium">Activity</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">System Administrator</span>
                    <a href="../public/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium">
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-history text-indigo-600 mr-3"></i>User Activity Logs
            </h1>
            <p class="text-gray-600">Monitor all user actions and system activity</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <?php
            $todayLogins = $db->query("SELECT COUNT(*) as count FROM user_activity_logs WHERE action = 'login' AND DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
            $todayActivity = $db->query("SELECT COUNT(*) as count FROM user_activity_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
            $uniqueUsers = $db->query("SELECT COUNT(DISTINCT user_id) as count FROM user_activity_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
            ?>
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Logs</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($totalLogs) ?></p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-lg">
                        <i class="fas fa-list text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Today's Logins</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?= $todayLogins ?></p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-lg">
                        <i class="fas fa-sign-in-alt text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Today's Activity</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?= $todayActivity ?></p>
                    </div>
                    <div class="bg-purple-100 p-4 rounded-lg">
                        <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Active Users Today</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?= $uniqueUsers ?></p>
                    </div>
                    <div class="bg-orange-100 p-4 rounded-lg">
                        <i class="fas fa-users text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Search User</label>
                    <input type="text" name="user" value="<?= htmlspecialchars($filterUser) ?>"
                           placeholder="Name or email..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Action Type</label>
                    <select name="action" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Actions</option>
                        <?php while ($action = $actions->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($action['action']) ?>" 
                                    <?= $filterAction === $action['action'] ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($action['action'])) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold">
                        Filter
                    </button>
                    <a href="activity.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-semibold">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Activity Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Time</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">User</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Action</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Description</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($activities->num_rows === 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            No activity logs found
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php while ($log = $activities->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?= date('M d, Y', strtotime($log['created_at'])) ?></div>
                                <div class="text-xs text-gray-500"><?= date('h:i A', strtotime($log['created_at'])) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900"><?= htmlspecialchars($log['full_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($log['email']) ?></div>
                                <?php if ($log['clinic_name']): ?>
                                    <div class="text-xs text-blue-600 mt-1"><?= htmlspecialchars($log['clinic_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $actionColors = [
                                    'login' => 'bg-green-100 text-green-700',
                                    'logout' => 'bg-gray-100 text-gray-700',
                                    'create' => 'bg-blue-100 text-blue-700',
                                    'update' => 'bg-yellow-100 text-yellow-700',
                                    'delete' => 'bg-red-100 text-red-700',
                                    'view' => 'bg-purple-100 text-purple-700',
                                ];
                                $colorClass = $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?= $colorClass ?>">
                                    <?= ucfirst(htmlspecialchars($log['action'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($log['description'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4">
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $filterUser ? '&user=' . urlencode($filterUser) : '' ?><?= $filterAction ? '&action=' . urlencode($filterAction) : '' ?>" 
                       class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?><?= $filterUser ? '&user=' . urlencode($filterUser) : '' ?><?= $filterAction ? '&action=' . urlencode($filterAction) : '' ?>" 
                       class="px-4 py-2 <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $filterUser ? '&user=' . urlencode($filterUser) : '' ?><?= $filterAction ? '&action=' . urlencode($filterAction) : '' ?>" 
                       class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

    </div>

</body>
</html>
