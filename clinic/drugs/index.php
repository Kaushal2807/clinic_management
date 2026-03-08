<?php
/**
 * Drug/Medicine Management - Main Page
 * Location: clinic/drugs/index.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Medicine Management';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';

if ($search) {
    $searchCondition = "WHERE medicine_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                        OR composition LIKE '%" . $conn->real_escape_string($search) . "%'";
}

// Get total count
$totalResult = $conn->query("SELECT COUNT(*) as total FROM medicine $searchCondition");
$totalMedicines = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalMedicines / $perPage);

// Fetch medicines
$query = "SELECT * FROM medicine $searchCondition ORDER BY medicine_name ASC LIMIT $perPage OFFSET $offset";
$medicines = $conn->query($query);

// Get categories, doses, and durations for dropdowns
$categories = $conn->query("SELECT DISTINCT category FROM medicine WHERE category IS NOT NULL AND category != '' ORDER BY category");
$doses = $conn->query("SELECT * FROM doses ORDER BY dose_name");
$durations = $conn->query("SELECT * FROM durations ORDER BY duration_value");

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-pills text-indigo-600 mr-3"></i>Medicine Management
            </h1>
            <p class="text-gray-600">Manage your medicine inventory, doses, and durations</p>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
                
                <!-- Search -->
                <div class="flex-1 w-full md:max-w-md">
                    <form method="GET" action="" class="relative">
                        <input type="text" 
                               name="search"
                               placeholder="Search medicines..." 
                               value="<?= htmlspecialchars($search) ?>"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                        <?php if ($search): ?>
                            <a href="index.php" class="absolute right-3 top-4 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3">
                    <button onclick="openAddMedicineModal()" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-plus mr-2"></i>Add Medicine
                    </button>
                    
                    <a href="doses.php" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-syringe mr-2"></i>Manage Doses
                    </a>
                    
                    <a href="durations.php" 
                       class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-clock mr-2"></i>Manage Durations
                    </a>
                    
                    <a href="suggest_drugs.php" 
                       class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-lightbulb mr-2"></i>Suggest Drugs
                    </a>
                </div>
            </div>
        </div>

        <!-- Medicines Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php if ($medicines->num_rows === 0): ?>
                <div class="col-span-full">
                    <div class="bg-white rounded-xl shadow-md p-12 text-center">
                        <i class="fas fa-pills text-gray-300 text-6xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No medicines found</h3>
                        <p class="text-gray-600 mb-6">Start by adding your first medicine to the inventory</p>
                        <button onclick="openAddMedicineModal()" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-plus mr-2"></i>Add First Medicine
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php while ($medicine = $medicines->fetch_assoc()): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">
                                    <?= htmlspecialchars($medicine['medicine_name']) ?>
                                </h3>
                                <?php if ($medicine['composition']): ?>
                                    <p class="text-sm text-gray-600 italic">
                                        <?= htmlspecialchars($medicine['composition']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php if ($medicine['quantity']): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    <?= $medicine['quantity'] > 50 ? 'bg-green-100 text-green-800' : 
                                        ($medicine['quantity'] > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                    Qty: <?= $medicine['quantity'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($medicine['category']): ?>
                            <div class="mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($medicine['category']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($medicine['description']): ?>
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                <?= htmlspecialchars($medicine['description']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="flex gap-2 pt-4 border-t border-gray-100">
                            <button onclick='editMedicine(<?= json_encode($medicine) ?>)' 
                                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            
                            <button onclick="addQuantity(<?= $medicine['id'] ?>, '<?= htmlspecialchars($medicine['medicine_name']) ?>')" 
                                    class="flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-plus mr-1"></i>Add Qty
                            </button>
                            
                            <button onclick="deleteMedicine(<?= $medicine['id'] ?>, '<?= htmlspecialchars($medicine['medicine_name']) ?>')" 
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-8 bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Showing page <span class="font-semibold"><?= $page ?></span> of 
                    <span class="font-semibold"><?= $totalPages ?></span>
                </div>
                
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-left mr-2"></i>Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            Next<i class="fas fa-chevron-right ml-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Medicine Modal -->
    <div id="addMedicineModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl sticky top-0">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-pills mr-2"></i>Add New Medicine
                </h3>
                <button onclick="closeModal('addMedicineModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addMedicineForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Medicine Name *</label>
                    <input type="text" name="medicine_name" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Composition</label>
                    <input type="text" name="composition"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="e.g., Paracetamol 500mg">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                        <input type="text" name="category" list="categories"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <datalist id="categories">
                            <?php 
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?= htmlspecialchars($cat['category']) ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Initial Quantity</label>
                        <input type="number" name="quantity" min="0" value="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                              placeholder="Additional information about this medicine"></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Save Medicine
                    </button>
                    <button type="button" onclick="closeModal('addMedicineModal')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Medicine Modal -->
    <div id="editMedicineModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl sticky top-0">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-edit mr-2"></i>Edit Medicine
                </h3>
                <button onclick="closeModal('editMedicineModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editMedicineForm" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Medicine Name *</label>
                    <input type="text" name="medicine_name" id="edit_drug_name" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Composition</label>
                    <input type="text" name="composition" id="edit_composition"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                        <input type="text" name="category" id="edit_category" list="categories"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                    <textarea name="description" id="edit_description" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Update Medicine
                    </button>
                    <button type="button" onclick="closeModal('editMedicineModal')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Quantity Modal -->
    <div id="addQuantityModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-plus-circle mr-2"></i>Add Quantity
                </h3>
                <button onclick="closeModal('addQuantityModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addQuantityForm" class="p-6 space-y-4">
                <input type="hidden" name="medicine_id" id="qty_medicine_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Medicine</label>
                    <input type="text" id="qty_medicine_name" readonly
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 font-semibold">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Add Quantity *</label>
                    <input type="number" name="quantity" min="1" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Add Quantity
                    </button>
                    <button type="button" onclick="closeModal('addQuantityModal')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open/Close Modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openAddMedicineModal() {
            document.getElementById('addMedicineForm').reset();
            document.getElementById('addMedicineModal').classList.add('active');
        }

        // Add Medicine
        document.getElementById('addMedicineForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/drugs/add.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Medicine added successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Edit Medicine
        function editMedicine(medicine) {
            document.getElementById('edit_id').value = medicine.id;
            document.getElementById('edit_drug_name').value = medicine.medicine_name;
            document.getElementById('edit_composition').value = medicine.composition || '';
            document.getElementById('edit_category').value = medicine.category || '';
            document.getElementById('edit_quantity').value = medicine.quantity || 0;
            document.getElementById('edit_description').value = medicine.description || '';
            document.getElementById('editMedicineModal').classList.add('active');
        }

        document.getElementById('editMedicineForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/drugs/update.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Medicine updated successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Add Quantity
        function addQuantity(id, name) {
            document.getElementById('qty_medicine_id').value = id;
            document.getElementById('qty_medicine_name').value = name;
            document.getElementById('addQuantityModal').classList.add('active');
        }

        document.getElementById('addQuantityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/drugs/add_quantity.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Quantity added successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Delete Medicine
        function deleteMedicine(id, name) {
            Swal.fire({
                title: 'Delete Medicine?',
                text: `Are you sure you want to delete "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../api/drugs/delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Medicine deleted successfully',
                                timer: 1500
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        }

        // Close modals with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => modal.classList.remove('active'));
            }
        });
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
