<?php
/**
 * Expense Management - Main Page
 * Location: clinic/expense/index.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Expense Management';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();
$clinicId = ClinicContext::getClinicId();

// Get expense statistics
$today = date('Y-m-d');
$currentMonth = date('Y-m');

$todayExpense = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE clinic_id = $clinicId AND DATE(expense_date) = '$today'")->fetch_assoc()['total'];
$monthExpense = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE clinic_id = $clinicId AND DATE_FORMAT(expense_date, '%Y-%m') = '$currentMonth'")->fetch_assoc()['total'];
$totalExpenses = $conn->query("SELECT COUNT(*) as total FROM expenses WHERE clinic_id = $clinicId")->fetch_assoc()['total'];

// Get expenses with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$conditions = ["e.clinic_id = $clinicId"];
if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $conditions[] = "(e.description LIKE '%$search_escaped%' OR e.category LIKE '%$search_escaped%')";
}
if ($category) {
    $category_escaped = $conn->real_escape_string($category);
    $conditions[] = "e.category = '$category_escaped'";
}
if ($dateFrom) {
    $conditions[] = "DATE(e.expense_date) >= '$dateFrom'";
}
if ($dateTo) {
    $conditions[] = "DATE(e.expense_date) <= '$dateTo'";
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

$totalResult = $conn->query("SELECT COUNT(*) as total FROM expenses e $whereClause");
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

$query = "SELECT e.* 
          FROM expenses e
          $whereClause
          ORDER BY e.expense_date DESC 
          LIMIT $perPage OFFSET $offset";
$expenses = $conn->query($query);

// Get categories
$categories = $conn->query("SELECT * FROM expense_categories WHERE clinic_id = $clinicId ORDER BY category_name");

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-receipt text-indigo-600 mr-3"></i>Expense Management
            </h1>
            <p class="text-gray-600">Track and manage clinic expenses</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-sm font-medium mb-1">Today's Expenses</p>
                        <h3 class="text-3xl font-bold">₹<?= number_format($todayExpense, 2) ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                        <i class="fas fa-calendar-day text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium mb-1">This Month</p>
                        <h3 class="text-3xl font-bold">₹<?= number_format($monthExpense, 2) ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                        <i class="fas fa-calendar-alt text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium mb-1">Total Records</p>
                        <h3 class="text-3xl font-bold"><?= number_format($totalExpenses) ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                        <i class="fas fa-file-invoice text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Actions -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="text" name="search" placeholder="Search description/vendor..."
                           value="<?= htmlspecialchars($search) ?>"
                           class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    
                    <select name="category" class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?= htmlspecialchars($cat['category_name']) ?>" <?= $category === $cat['category_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <input type="date" name="date_from" placeholder="From Date"
                           value="<?= htmlspecialchars($dateFrom) ?>"
                           class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    
                    <input type="date" name="date_to" placeholder="To Date"
                           value="<?= htmlspecialchars($dateTo) ?>"
                           class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2.5 rounded-lg font-semibold transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                    <?php if ($dateFrom && $dateTo): ?>
                    <a href="print.php?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&category=<?= urlencode($category) ?>" 
                       target="_blank"
                       class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                        <i class="fas fa-file-pdf mr-2"></i>Download Report
                    </a>
                    <?php endif; ?>
                    <button type="button" onclick="openAddExpenseModal()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-semibold transition ml-auto">
                        <i class="fas fa-plus mr-2"></i>Add Expense
                    </button>
                    <a href="categories.php" 
                       class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                        <i class="fas fa-tags mr-2"></i>Manage Categories
                    </a>
                </div>
            </form>
        </div>

        <!-- Expenses Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Category</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Description</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Vendor</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Amount</th>
                            <th class="px-6 py-4 text-center text-sm font-bold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($expenses->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-receipt text-gray-300 text-5xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-900">No expenses found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($exp = $expenses->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?= date('d M Y', strtotime($exp['expense_date'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                        <?= htmlspecialchars($exp['category']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($exp['description']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <?= htmlspecialchars($exp['vendor'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-red-600">₹<?= number_format($exp['amount'], 2) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button onclick='editExpense(<?= json_encode($exp) ?>)'
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <button onclick="deleteExpense(<?= $exp['id'] ?>)"
                                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl sticky top-0">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-receipt mr-2"></i>Add Expense
                </h3>
                <button onclick="closeModal('addExpenseModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addExpenseForm" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                        <select name="category" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select Category</option>
                            <?php 
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?= htmlspecialchars($cat['category_name']) ?>">
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Expense Date *</label>
                        <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description *</label>
                    <textarea name="description" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Vendor/Supplier</label>
                        <input type="text" name="vendor"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Amount (₹) *</label>
                        <input type="number" name="amount" step="0.01" required value="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Save Expense
                    </button>
                    <button type="button" onclick="closeModal('addExpenseModal')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openAddExpenseModal() {
            document.getElementById('addExpenseForm').reset();
            document.getElementById('addExpenseModal').classList.add('active');
        }

        // Add Expense
        document.getElementById('addExpenseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/expense/add.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Expense added successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Delete Expense
        function deleteExpense(id) {
            Swal.fire({
                title: 'Delete Expense?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../api/expense/delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success')
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        }

        function editExpense(expense) {
            console.log('Edit expense:', expense);
        }
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
