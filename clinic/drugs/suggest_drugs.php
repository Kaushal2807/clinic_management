<?php
/**
 * Suggest Drugs Page
 * Location: clinic/drugs/suggest_drugs.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Drug Suggestions';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Get recently added medicines
$recentMedicines = $conn->query("
    SELECT medicine_name, composition, category, quantity, created_at
    FROM medicine
    ORDER BY created_at DESC
    LIMIT 10
");

// Get low stock medicines
$lowStock = $conn->query("
    SELECT * FROM medicine 
    WHERE quantity < 20 
    ORDER BY quantity ASC
");

// Get medicines by category
$byCategory = $conn->query("
    SELECT category, COUNT(*) as count 
    FROM medicine 
    WHERE category IS NOT NULL AND category != ''
    GROUP BY category 
    ORDER BY count DESC
");

// Get total inventory stats
$totalMeds = $conn->query("SELECT COUNT(*) as total FROM medicine")->fetch_assoc()['total'];
$totalStock = $conn->query("SELECT SUM(quantity) as total FROM medicine")->fetch_assoc()['total'] ?? 0;

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-lightbulb text-green-600 mr-3"></i>Drug Suggestions
                    </h1>
                    <p class="text-gray-600">Insights and suggestions based on your medicine inventory</p>
                </div>
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Medicines
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Inventory Overview -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-chart-bar text-indigo-600 mr-2"></i>Inventory Overview
                </h2>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-indigo-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-indigo-600"><?= $totalMeds ?></div>
                        <div class="text-sm text-gray-600 mt-1">Total Medicines</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-green-600"><?= $totalStock ?></div>
                        <div class="text-sm text-gray-600 mt-1">Total Stock Units</div>
                    </div>
                </div>
                
                <h3 class="font-semibold text-gray-900 mb-3">Recently Added Medicines</h3>
                <?php if ($recentMedicines->num_rows === 0): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-pills text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-600">No medicines added yet</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php while ($med = $recentMedicines->fetch_assoc()): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($med['medicine_name']) ?></div>
                                    <?php if ($med['composition']): ?>
                                        <div class="text-xs text-gray-600"><?= htmlspecialchars(substr($med['composition'], 0, 40)) ?><?= strlen($med['composition']) > 40 ? '...' : '' ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right ml-2">
                                    <div class="text-xs text-gray-500"><?= date('d M', strtotime($med['created_at'])) ?></div>
                                    <div class="text-xs font-medium text-indigo-600">Qty: <?= $med['quantity'] ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Low Stock Alert
                </h2>
                
                <?php if ($lowStock->num_rows === 0): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-green-300 text-4xl mb-3"></i>
                        <p class="text-gray-600">All medicines are well stocked!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php while ($med = $lowStock->fetch_assoc()): ?>
                            <div class="p-3 bg-red-50 rounded-lg border border-red-200">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($med['medicine_name']) ?></div>
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-bold">
                                        Only <?= $med['quantity'] ?> left
                                    </span>
                                </div>
                                <?php if ($med['composition']): ?>
                                    <div class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($med['composition']) ?></div>
                                <?php endif; ?>
                                <button onclick="addQuantity(<?= $med['id'] ?>, '<?= htmlspecialchars($med['medicine_name']) ?>')" 
                                        class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg transition">
                                    <i class="fas fa-plus mr-1"></i>Restock Now
                                </button>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Category Breakdown -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-indigo-600 mr-2"></i>Category Breakdown
                </h2>
                
                <?php if ($byCategory->num_rows === 0): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-tags text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-600">No categories defined yet</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php while ($cat = $byCategory->fetch_assoc()): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex items-center">
                                    <i class="fas fa-tag text-indigo-600 mr-2"></i>
                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($cat['category']) ?></span>
                                </div>
                                <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    <?= $cat['count'] ?> items
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
                </h2>
                
                <div class="space-y-3">
                    <a href="index.php" 
                       class="block p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition">
                        <div class="flex items-center">
                            <i class="fas fa-plus-circle text-indigo-600 text-2xl mr-3"></i>
                            <div>
                                <div class="font-semibold text-gray-900">Add New Medicine</div>
                                <div class="text-sm text-gray-600">Add a medicine to your inventory</div>
                            </div>
                        </div>
                    </a>
                    
                    <a href="../prescription/index.php" 
                       class="block p-4 bg-green-50 hover:bg-green-100 rounded-lg border border-green-200 transition">
                        <div class="flex items-center">
                            <i class="fas fa-prescription text-green-600 text-2xl mr-3"></i>
                            <div>
                                <div class="font-semibold text-gray-900">Create Prescription</div>
                                <div class="text-sm text-gray-600">Write a new prescription</div>
                            </div>
                        </div>
                    </a>
                    
                    <a href="doses.php" 
                       class="block p-4 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition">
                        <div class="flex items-center">
                            <i class="fas fa-syringe text-blue-600 text-2xl mr-3"></i>
                            <div>
                                <div class="font-semibold text-gray-900">Manage Doses</div>
                                <div class="text-sm text-gray-600">Configure dosage options</div>
                            </div>
                        </div>
                    </a>
                    
                    <a href="durations.php" 
                       class="block p-4 bg-purple-50 hover:bg-purple-100 rounded-lg border border-purple-200 transition">
                        <div class="flex items-center">
                            <i class="fas fa-clock text-purple-600 text-2xl mr-3"></i>
                            <div>
                                <div class="font-semibold text-gray-900">Manage Durations</div>
                                <div class="text-sm text-gray-600">Configure duration options</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

        </div>

    </div>

<script>
// Add Quantity Function
async function addQuantity(id, name) {
    const { value: quantity } = await Swal.fire({
        title: `Add Stock for ${name}`,
        input: 'number',
        inputLabel: 'Quantity to add',
        inputPlaceholder: 'Enter quantity',
        showCancelButton: true,
        inputValidator: (value) => {
            if (!value || value <= 0) {
                return 'Please enter a valid quantity!';
            }
        }
    });
    
    if (quantity) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('quantity', quantity);
            
            const response = await fetch('../../api/drugs/add_quantity.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire('Success!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'Failed to add quantity', 'error');
        }
    }
}
</script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
