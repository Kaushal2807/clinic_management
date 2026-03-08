<?php
/**
 * Clinic Dashboard
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

Auth::requireClinic();

$clinicId = Session::getClinicId();
$db = Database::getInstance();

// Get clinic information
$stmt = $db->query("SELECT * FROM clinics WHERE id = ?", [$clinicId], 'i');
$clinic = $stmt->get_result()->fetch_assoc();

if (!$clinic) {
    die("Clinic not found");
}

// Switch to clinic database
$db->switchDatabase($clinic['database_name']);
$conn = $db->getConnection();

// Get statistics
$stats = [];
$stats['total_patients'] = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'] ?? 0;
$stats['today_patients'] = $conn->query("SELECT COUNT(*) as count FROM patients WHERE DATE(date_of_visit) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$stats['pending_payments'] = $conn->query("SELECT COUNT(*) as count FROM patients WHERE payment_status != 'paid'")->fetch_assoc()['count'] ?? 0;
$stats['total_revenue'] = $conn->query("SELECT SUM(total_amount) as total FROM patients")->fetch_assoc()['total'] ?? 0;

// Recent patients
$recentPatients = $conn->query("SELECT * FROM patients ORDER BY date_of_visit DESC, created_at DESC LIMIT 10");

// Switch back to master database
$db->switchToMaster();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($clinic['clinic_name']) ?> - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header with Dynamic Logo -->
    <header class="bg-white shadow-md border-b-2 border-indigo-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- Logo & Clinic Name -->
                <div class="flex items-center gap-4">
                    <?php if ($clinic['logo_path']): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($clinic['logo_path']) ?>" 
                             alt="<?= htmlspecialchars($clinic['clinic_name']) ?>"
                             class="h-16 w-16 rounded-xl object-cover shadow-lg ring-2 ring-indigo-100">
                    <?php else: ?>
                        <div class="h-16 w-16 gradient-bg rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-2xl">
                                <?= strtoupper(substr($clinic['clinic_name'], 0, 1)) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="text-2xl font-bold text-indigo-600">
                            <?= htmlspecialchars($clinic['clinic_name']) ?>
                        </h1>
                        <p class="text-sm text-gray-600 font-medium">Management System</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex space-x-1">
                    <a href="dashboard.php" class="px-4 py-2 rounded-lg text-sm font-semibold text-indigo-600 bg-indigo-50">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="patients/index.php" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">
                        <i class="fas fa-user-injured mr-2"></i>Patients
                    </a>
                    <a href="prescription/list.php" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">
                        <i class="fas fa-prescription mr-2"></i>Prescriptions
                    </a>
                    <a href="reports/index.php" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">
                        <i class="fas fa-chart-bar mr-2"></i>Reports
                    </a>
                </nav>

                <!-- User Menu -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars(Session::getUserName()) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars(Session::getEmail()) ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>/public/logout.php" 
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Welcome Banner -->
        <div class="gradient-bg rounded-2xl shadow-xl p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-2">Welcome back! 👋</h2>
                    <p class="text-lg text-indigo-100">Here's what's happening with your clinic today.</p>
                </div>
                <div class="hidden md:block">
                    <svg class="w-24 h-24 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total Patients -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500 hover:shadow-lg transition transform hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Total Patients</p>
                        <p class="text-4xl font-bold text-gray-900 mt-2"><?= number_format($stats['total_patients']) ?></p>
                        <p class="text-sm text-gray-500 mt-2">All registered</p>
                    </div>
                    <div class="bg-indigo-100 rounded-full p-4">
                        <i class="fas fa-users text-3xl text-indigo-600"></i>
                    </div>
                </div>
            </div>

            <!-- Today's Visits -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition transform hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Today's Visits</p>
                        <p class="text-4xl font-bold text-gray-900 mt-2"><?= number_format($stats['today_patients']) ?></p>
                        <p class="text-sm text-gray-500 mt-2"><?= date('M d, Y') ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-4">
                        <i class="fas fa-calendar-check text-3xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500 hover:shadow-lg transition transform hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Pending Payments</p>
                        <p class="text-4xl font-bold text-gray-900 mt-2"><?= number_format($stats['pending_payments']) ?></p>
                        <p class="text-sm text-gray-500 mt-2">Needs attention</p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-4">
                        <i class="fas fa-exclamation-triangle text-3xl text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition transform hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Total Revenue</p>
                        <p class="text-4xl font-bold text-gray-900 mt-2">₹<?= number_format($stats['total_revenue'], 2) ?></p>
                        <p class="text-sm text-gray-500 mt-2">All time</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-4">
                        <i class="fas fa-dollar-sign text-3xl text-blue-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="patients/index.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition group">
                <div class="flex items-center">
                    <div class="bg-indigo-100 rounded-full p-4 group-hover:bg-indigo-200 transition">
                        <i class="fas fa-user-plus text-2xl text-indigo-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition">Add Patient</h3>
                        <p class="text-sm text-gray-600">Register new patient</p>
                    </div>
                </div>
            </a>

            <a href="prescription/index.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition group">
                <div class="flex items-center">
                    <div class="bg-green-100 rounded-full p-4 group-hover:bg-green-200 transition">
                        <i class="fas fa-file-medical text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-green-600 transition">New Prescription</h3>
                        <p class="text-sm text-gray-600">Create prescription</p>
                    </div>
                </div>
            </a>

            <a href="reports/index.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition group">
                <div class="flex items-center">
                    <div class="bg-purple-100 rounded-full p-4 group-hover:bg-purple-200 transition">
                        <i class="fas fa-chart-line text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 transition">View Reports</h3>
                        <p class="text-sm text-gray-600">Analytics & insights</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Patients -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-clock mr-2 text-indigo-600"></i>Recent Patients
                    </h3>
                    <a href="patients/index.php" class="text-indigo-600 hover:text-indigo-700 font-semibold text-sm">
                        View All →
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Patient ID</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Visit Date</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($recentPatients->num_rows === 0): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-users text-5xl mb-4"></i>
                                    <p class="text-lg font-semibold text-gray-900 mb-1">No patients yet</p>
                                    <p class="text-sm text-gray-600">Start by adding your first patient</p>
                                    <a href="patients/index.php" class="inline-block mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition">
                                        Add Patient
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($patient = $recentPatients->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-semibold text-gray-900"><?= htmlspecialchars($patient['patient_uid']) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($patient['name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($patient['age']) ?> years</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($patient['contact_number']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= date('M d, Y', strtotime($patient['date_of_visit'])) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900">₹<?= number_format($patient['total_amount'], 2) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusColors = [
                                        'paid' => 'bg-green-100 text-green-700',
                                        'partial' => 'bg-yellow-100 text-yellow-700',
                                        'pending' => 'bg-red-100 text-red-700'
                                    ];
                                    $statusColor = $statusColors[$patient['payment_status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full <?= $statusColor ?>">
                                        <?= ucfirst($patient['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="patients/view.php?id=<?= $patient['id'] ?>" 
                                       class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="text-center text-sm text-gray-600">
                <p>&copy; <?= APP_YEAR ?> <?= htmlspecialchars($clinic['clinic_name']) ?>. All rights reserved.</p>
                <p class="mt-1">Powered by <span class="font-semibold text-indigo-600"><?= APP_NAME ?></span> v<?= APP_VERSION ?></p>
            </div>
        </div>
    </footer>

</body>
</html>
