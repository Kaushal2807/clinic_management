<?php
/**
 * Treatment List - All Treatments
 * Location: clinic/treatment/list.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'All Treatments';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Search filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build WHERE clause
$where_conditions = [];
$where_params = [];
$where_types = '';

if ($search) {
    $where_conditions[] = "(p.patient_uid LIKE ? OR p.name LIKE ? OR t.treatment_name LIKE ?)";
    $search_term = "%$search%";
    $where_params[] = $search_term;
    $where_params[] = $search_term;
    $where_params[] = $search_term;
    $where_types .= 'sss';
}

if ($status_filter) {
    $where_conditions[] = "t.status = ?";
    $where_params[] = $status_filter;
    $where_types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_query = "SELECT COUNT(*) as total 
                FROM treatments t 
                INNER JOIN patients p ON t.patient_id = p.id 
                $where_clause";

if (!empty($where_params)) {
    $count_stmt = $conn->prepare($count_query);
    if (!empty($where_types)) {
        $count_stmt->bind_param($where_types, ...$where_params);
    }
    $count_stmt->execute();
    $totalTreatments = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $totalTreatments = $conn->query($count_query)->fetch_assoc()['total'];
}

$totalPages = ceil($totalTreatments / $perPage);

// Fetch treatments
$query = "SELECT t.*, p.name as patient_name, p.patient_uid, p.contact_number, p.age
          FROM treatments t 
          INNER JOIN patients p ON t.patient_id = p.id 
          $where_clause
          ORDER BY t.treatment_date DESC, t.created_at DESC 
          LIMIT $perPage OFFSET $offset";

if (!empty($where_params)) {
    $stmt = $conn->prepare($query);
    if (!empty($where_types)) {
        $stmt->bind_param($where_types, ...$where_params);
    }
    $stmt->execute();
    $treatments = $stmt->get_result();
} else {
    $treatments = $conn->query($query);
}

// Get statistics
$stats_planned = $conn->query("SELECT COUNT(*) as count FROM treatments WHERE status = 'planned'")->fetch_assoc()['count'];
$stats_completed = $conn->query("SELECT COUNT(*) as count FROM treatments WHERE status = 'completed'")->fetch_assoc()['count'];
$stats_cancelled = $conn->query("SELECT COUNT(*) as count FROM treatments WHERE status = 'cancelled'")->fetch_assoc()['count'];
$stats_total = $conn->query("SELECT COUNT(*) as count FROM treatments")->fetch_assoc()['count'];

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-tooth text-purple-600 mr-3"></i>All Treatments
                    </h1>
                    <p class="text-gray-600">View and manage all treatment records</p>
                </div>
                <a href="index.php" 
                   class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                    <i class="fas fa-plus mr-2"></i>Add Treatment
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Treatments</p>
                        <p class="text-3xl font-bold mt-2"><?= $stats_total ?></p>
                    </div>
                    <i class="fas fa-procedures text-4xl text-blue-200"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">Planned</p>
                        <p class="text-3xl font-bold mt-2"><?= $stats_planned ?></p>
                    </div>
                    <i class="fas fa-calendar-check text-4xl text-yellow-200"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Completed</p>
                        <p class="text-3xl font-bold mt-2"><?= $stats_completed ?></p>
                    </div>
                    <i class="fas fa-check-circle text-4xl text-green-200"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-sm font-medium">Cancelled</p>
                        <p class="text-3xl font-bold mt-2"><?= $stats_cancelled ?></p>
                    </div>
                    <i class="fas fa-times-circle text-4xl text-red-200"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search"
                           placeholder="Search by Patient ID, Name, or Treatment..." 
                           value="<?= htmlspecialchars($search) ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="w-full md:w-48">
                    <select name="status" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">All Status</option>
                        <option value="planned" <?= $status_filter === 'planned' ? 'selected' : '' ?>>Planned</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <?php if ($search || $status_filter): ?>
                <a href="list.php" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition text-center">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Treatments Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Patient Info</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Treatment Details</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Date & Status</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Cost</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($treatments->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-tooth text-gray-300 text-5xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-900">No treatments found</p>
                                <p class="text-gray-600 mt-2">
                                    <?= $search || $status_filter ? 'Try adjusting your filters' : 'Add your first treatment plan' ?>
                                </p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($treatment = $treatments->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($treatment['patient_name']) ?></div>
                                        <div class="text-sm text-gray-500">
                                            ID: <span class="font-mono font-bold text-purple-600"><?= $treatment['patient_uid'] ?></span>
                                        </div>
                                        <?php if ($treatment['age']): ?>
                                        <div class="text-sm text-gray-500">Age: <?= $treatment['age'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($treatment['treatment_name']) ?></div>
                                        <?php if ($treatment['category']): ?>
                                        <span class="inline-block mt-1 px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 rounded">
                                            <?= htmlspecialchars($treatment['category']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($treatment['selected_teeth']): ?>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <i class="fas fa-tooth mr-1"></i><?= htmlspecialchars($treatment['selected_teeth']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm text-gray-900">
                                            <i class="fas fa-calendar text-gray-400 mr-1"></i>
                                            <?= date('M d, Y', strtotime($treatment['treatment_date'])) ?>
                                        </div>
                                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold
                                            <?= $treatment['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                ($treatment['status'] === 'planned' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                            <?= ucfirst($treatment['status']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">₹<?= number_format($treatment['cost'], 2) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="view.php?patient_uid=<?= $treatment['patient_uid'] ?>"
                                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-medium transition"
                                           title="View Patient Treatments">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../patients/view.php?id=<?= $treatment['patient_uid'] ?>"
                                           class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1.5 rounded text-xs font-medium transition"
                                           title="View Patient">
                                            <i class="fas fa-user"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 border-t flex justify-between items-center">
                <span class="text-sm text-gray-700">
                    Page <?= $page ?> of <?= $totalPages ?> (<?= $totalTreatments ?> total treatments)
                </span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>"
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 transition">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>"
                           class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
