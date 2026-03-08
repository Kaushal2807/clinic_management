<?php
/**
 * Work Done Management - Main Page
 * Location: clinic/work_done/index.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Work Done Management';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Get all work done records
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$searchCondition = '';

if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $searchCondition = "WHERE p.name LIKE '%$search_escaped%' OR p.patient_uid LIKE '%$search_escaped%' OR wd.work_name LIKE '%$search_escaped%'";
}

$totalResult = $conn->query("SELECT COUNT(*) as total FROM patient_work_done pwd 
                             JOIN patients p ON pwd.patient_id = p.id 
                             JOIN work_done wd ON pwd.work_done_id = wd.id
                             $searchCondition");
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

$query = "SELECT pwd.*, p.name, p.age, p.patient_uid, wd.work_name, wd.cost as unit_cost
          FROM patient_work_done pwd
          JOIN patients p ON pwd.patient_id = p.id
          JOIN work_done wd ON pwd.work_done_id = wd.id
          $searchCondition
          ORDER BY pwd.created_at DESC 
          LIMIT $perPage OFFSET $offset";
$workRecords = $conn->query($query);

// Get patients and work types
$patients = $conn->query("SELECT id, patient_uid, name FROM patients ORDER BY name");
$workTypes = $conn->query("SELECT * FROM work_done WHERE is_active = 1 ORDER BY work_name");

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-briefcase-medical text-purple-600 mr-3"></i>Work Done Management
            </h1>
            <p class="text-gray-600">Track completed work and procedures</p>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
                <div class="flex-1 w-full md:max-w-md">
                    <form method="GET" class="relative">
                        <input type="text" name="search" placeholder="Search by Patient ID or Name..."
                               value="<?= htmlspecialchars($search) ?>"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                    </form>
                </div>

                <div class="flex gap-3">
                    <button onclick="openAddWorkModal()" 
                            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-plus mr-2"></i>Add Work Done
                    </button>
                    <a href="manage_types.php" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-cog mr-2"></i>Manage Work Types
                    </a>
                </div>
            </div>
        </div>

        <!-- Work Done Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Patient Info</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Work Details</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Cost</th>
                            <th class="px-6 py-4 text-center text-sm font-bold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($workRecords->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-briefcase text-gray-300 text-5xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-900">No work records found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($work = $workRecords->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($work['name']) ?></div>
                                    <div class="text-sm text-gray-500">ID: <?= $work['patient_uid'] ?> | Age: <?= $work['age'] ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($work['work_name']) ?></div>
                                    <?php if (!empty($work['notes'])): ?>
                                    <div class="text-sm text-gray-600 mt-1"><?= htmlspecialchars(substr($work['notes'], 0, 50)) ?><?= strlen($work['notes']) > 50 ? '...' : '' ?></div>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-500 mt-1">Qty: <?= $work['quantity'] ?> × ₹<?= number_format($work['unit_cost'], 2) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= date('d M Y', strtotime($work['work_date'])) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-green-600">₹<?= number_format($work['total_cost'], 2) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button onclick='editWork(<?= json_encode($work) ?>)'
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <button onclick="deleteWork(<?= $work['id'] ?>)"
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

    <!-- Add Work Modal -->
    <div id="addWorkModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl sticky top-0">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-briefcase-medical mr-2"></i>Add Work Done
                </h3>
                <button onclick="closeModal('addWorkModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addWorkForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Patient *</label>
                    <select name="patient_uid" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Select Patient --</option>
                        <?php 
                        $patients->data_seek(0);
                        while ($p = $patients->fetch_assoc()): 
                        ?>
                            <option value="<?= $p['patient_uid'] ?>">
                                <?= htmlspecialchars($p['patient_uid']) ?> - <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Work Type *</label>
                        <select name="work_done_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">Select Type</option>
                            <?php 
                            $workTypes->data_seek(0);
                            while ($wt = $workTypes->fetch_assoc()): 
                            ?>
                                <option value="<?= $wt['id'] ?>">
                                    <?= htmlspecialchars($wt['work_name']) ?> - ₹<?= number_format($wt['cost'], 2) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Work Date *</label>
                        <input type="date" name="work_date" value="<?= date('Y-m-d') ?>" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity *</label>
                    <input type="number" name="quantity" min="1" required value="1"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Save Work
                    </button>
                    <button type="button" onclick="closeModal('addWorkModal')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Work Modal -->
    <div id="editWorkModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl sticky top-0">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-edit mr-2"></i>Edit Work Done
                </h3>
                <button onclick="closeModal('editWorkModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editWorkForm" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Patient *</label>
                    <input type="text" id="edit_patient_display" disabled
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100">
                    <input type="hidden" name="patient_uid" id="edit_patient_uid">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Work Type *</label>
                        <select name="work_done_id" id="edit_work_done_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">Select Type</option>
                            <?php 
                            $workTypes->data_seek(0);
                            while ($wt = $workTypes->fetch_assoc()): 
                            ?>
                                <option value="<?= $wt['id'] ?>">
                                    <?= htmlspecialchars($wt['work_name']) ?> - ₹<?= number_format($wt['cost'], 2) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Work Date *</label>
                        <input type="date" name="work_date" id="edit_work_date" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="edit_notes" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity *</label>
                    <input type="number" name="quantity" id="edit_quantity" min="1" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Update Work
                    </button>
                    <button type="button" onclick="closeModal('editWorkModal')"
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

        function openAddWorkModal() {
            document.getElementById('addWorkForm').reset();
            document.getElementById('addWorkModal').classList.add('active');
        }

        // Add Work
        document.getElementById('addWorkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/work_done/add.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Work done record saved successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Delete Work
        function deleteWork(id) {
            Swal.fire({
                title: 'Delete Record?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../api/work_done/delete.php', {
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

        function editWork(work) {
            // Populate edit form with work data
            document.getElementById('edit_id').value = work.id;
            document.getElementById('edit_patient_uid').value = work.patient_uid;
            document.getElementById('edit_patient_display').value = work.patient_uid + ' - ' + work.name;
            document.getElementById('edit_work_done_id').value = work.work_done_id;
            document.getElementById('edit_work_date').value = work.work_date;
            document.getElementById('edit_notes').value = work.notes || '';
            document.getElementById('edit_quantity').value = work.quantity;
            
            // Open edit modal
            document.getElementById('editWorkModal').classList.add('active');
        }

        // Edit Work Form Submit
        document.getElementById('editWorkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/work_done/update.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Work done record updated successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
