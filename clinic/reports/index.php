<?php
/**
 * Reports Dashboard - Main Page
 * Location: clinic/reports/index.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Reports & Analytics';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();
$clinicId = ClinicContext::getClinicId();

// Date filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Key Performance Indicators (KPIs)
$totalPatients = $conn->query("SELECT COUNT(*) as total FROM patients WHERE clinic_id = $clinicId")->fetch_assoc()['total'];
$newPatientsThisMonth = $conn->query("SELECT COUNT(*) as total FROM patients 
    WHERE clinic_id = $clinicId AND DATE_FORMAT(created_at, '%Y-%m') = '" . date('Y-m') . "'")->fetch_assoc()['total'];

$totalRevenue = $conn->query("SELECT COALESCE(SUM(total_amount - payment_pending), 0) as total FROM patients 
    WHERE clinic_id = $clinicId AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];
    
$totalExpenses = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
    WHERE clinic_id = $clinicId AND DATE(expense_date) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

$pendingPayments = $conn->query("SELECT COALESCE(SUM(payment_pending), 0) as total FROM patients 
    WHERE clinic_id = $clinicId AND payment_status IN ('pending', 'partial')")->fetch_assoc()['total'];

$totalPrescriptions = $conn->query("SELECT COUNT(*) as total FROM prescriptions 
    WHERE clinic_id = $clinicId AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

$totalTreatments = $conn->query("SELECT COUNT(*) as total FROM treatments 
    WHERE clinic_id = $clinicId AND DATE(treatment_date) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

$totalWorkDone = $conn->query("SELECT COALESCE(SUM(total_cost), 0) as total FROM patient_work_done 
    WHERE clinic_id = $clinicId AND DATE(work_date) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

// Recent activity trends
$lastSixMonths = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));
    
    $revenue = $conn->query("SELECT COALESCE(SUM(total_amount - payment_pending), 0) as total FROM patients 
        WHERE clinic_id = $clinicId AND DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch_assoc()['total'];
    
    $expenses = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
        WHERE clinic_id = $clinicId AND DATE_FORMAT(expense_date, '%Y-%m') = '$month'")->fetch_assoc()['total'];
    
    $lastSixMonths[] = [
        'month' => $monthName,
        'revenue' => $revenue,
        'expenses' => $expenses,
        'profit' => $revenue - $expenses
    ];
}

// Payment status breakdown
$paymentStats = $conn->query("SELECT payment_status, COUNT(*) as count FROM patients WHERE clinic_id = $clinicId GROUP BY payment_status")->fetch_all(MYSQLI_ASSOC);

// Top expense categories
$topExpenses = $conn->query("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses 
    WHERE clinic_id = $clinicId AND DATE(expense_date) BETWEEN '$dateFrom' AND '$dateTo' 
    GROUP BY category ORDER BY total DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-chart-line text-blue-600 mr-3"></i>Reports & Analytics
            </h1>
            <p class="text-gray-600">Overview of clinic performance and trends</p>
        </div>

        <!-- Date Filter -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                    <i class="fas fa-filter mr-2"></i>Apply
                </button>
                <a href="index.php" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2.5 rounded-lg font-semibold transition">
                    <i class="fas fa-times mr-2"></i>Reset
                </a>
                <a href="print.php?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" 
                   target="_blank"
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                    <i class="fas fa-file-pdf mr-2"></i>Download Report
                </a>
            </form>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <span class="text-sm font-medium">Total Patients</span>
                </div>
                <h3 class="text-3xl font-bold"><?= number_format($totalPatients) ?></h3>
                <p class="text-blue-100 text-sm mt-2">+<?= $newPatientsThisMonth ?> this month</p>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                        <i class="fas fa-dollar-sign text-2xl"></i>
                    </div>
                    <span class="text-sm font-medium">Revenue</span>
                </div>
                <h3 class="text-3xl font-bold">₹<?= number_format($totalRevenue, 2) ?></h3>
                <p class="text-green-100 text-sm mt-2">Selected period</p>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                        <i class="fas fa-receipt text-2xl"></i>
                    </div>
                    <span class="text-sm font-medium">Expenses</span>
                </div>
                <h3 class="text-3xl font-bold">₹<?= number_format($totalExpenses, 2) ?></h3>
                <p class="text-red-100 text-sm mt-2">Selected period</p>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <span class="text-sm font-medium">Net Profit</span>
                </div>
                <h3 class="text-3xl font-bold">₹<?= number_format($totalRevenue - $totalExpenses, 2) ?></h3>
                <p class="text-purple-100 text-sm mt-2">Revenue - Expenses</p>
            </div>
        </div>

        <!-- Additional Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-orange-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-hourglass-half text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Pending Payments</p>
                        <h4 class="text-2xl font-bold text-gray-900">₹<?= number_format($pendingPayments, 2) ?></h4>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-pink-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-prescription text-pink-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Prescriptions</p>
                        <h4 class="text-2xl font-bold text-gray-900"><?= number_format($totalPrescriptions) ?></h4>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-cyan-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-tooth text-cyan-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Treatments Done</p>
                        <h4 class="text-2xl font-bold text-gray-900"><?= number_format($totalTreatments) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            <!-- Revenue vs Expenses Trend -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-chart-area text-blue-600 mr-2"></i>Last 6 Months Trend
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Month</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Revenue</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Expenses</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($lastSixMonths as $data): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= $data['month'] ?></td>
                                <td class="px-4 py-3 text-sm text-right text-green-600 font-semibold">
                                    ₹<?= number_format($data['revenue'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-red-600 font-semibold">
                                    ₹<?= number_format($data['expenses'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-bold <?= $data['profit'] >= 0 ? 'text-blue-600' : 'text-red-600' ?>">
                                    ₹<?= number_format($data['profit'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Expense Categories -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-chart-pie text-indigo-600 mr-2"></i>Top Expense Categories
                </h3>
                <div class="space-y-4">
                    <?php if (empty($topExpenses)): ?>
                        <p class="text-gray-500 text-center py-8">No expense data available</p>
                    <?php else: ?>
                        <?php 
                        $maxExpense = max(array_column($topExpenses, 'total'));
                        foreach ($topExpenses as $expense): 
                            $percentage = $maxExpense > 0 ? ($expense['total'] / $maxExpense) * 100 : 0;
                        ?>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($expense['category']) ?></span>
                                <span class="text-sm font-bold text-gray-900">₹<?= number_format($expense['total'], 2) ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2.5 rounded-full" 
                                     style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment Status Breakdown -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-coins text-green-600 mr-2"></i>Payment Status Breakdown
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($paymentStats as $stat): 
                    $statusColor = [
                        'paid' => 'green',
                        'pending' => 'orange',
                        'partial' => 'yellow'
                    ][$stat['payment_status']] ?? 'gray';
                ?>
                <div class="bg-<?= $statusColor ?>-50 border border-<?= $statusColor ?>-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-<?= $statusColor ?>-600 font-semibold uppercase text-sm">
                                <?= htmlspecialchars($stat['payment_status']) ?>
                            </p>
                            <h4 class="text-2xl font-bold text-<?= $statusColor ?>-700">
                                <?= number_format($stat['count']) ?>
                            </h4>
                        </div>
                        <div class="bg-<?= $statusColor ?>-100 p-3 rounded-full">
                            <i class="fas fa-wallet text-<?= $statusColor ?>-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
