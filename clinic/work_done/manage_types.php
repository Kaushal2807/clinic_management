<?php
/**
 * Work Types Management
 * Location: clinic/work_done/manage_types.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Manage Work Types';
$conn = ClinicContext::getConnection();

$workTypes = $conn->query("SELECT * FROM work_types ORDER BY work_name");

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-cog text-purple-600 mr-3"></i>Work Types Management
                </h1>
                <p class="text-gray-600">Manage treatment and procedure types</p>
            </div>
            <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Work Done
            </a>
        </div>

        <!-- Add Type Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-plus-circle text-green-600 mr-2"></i>Add New Work Type
            </h2>
            <form id="addTypeForm" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <input type="text" name="work_name" placeholder="Work type name..." required
                       class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <input type="number" name="cost" placeholder="Cost (₹)" step="0.01" min="0" required
                       class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <input type="text" name="description" placeholder="Description (optional)"
                       class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <button type="submit" 
                        class="md:col-span-3 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Add Type
                </button>
            </form>
        </div>

        <!-- Work Types Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">ID</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Work Type Name</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Cost</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Description</th>
                            <th class="px-6 py-4 text-center text-sm font-bold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($workTypes->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-cog text-gray-300 text-5xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-900">No work types found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($type = $workTypes->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-semibold text-gray-900"><?= $type['id'] ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($type['work_name']) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-green-600">₹<?= number_format($type['cost'], 2) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-600 text-sm"><?= htmlspecialchars($type['description'] ?? '-') ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button onclick='editType(<?= json_encode($type) ?>)'
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <button onclick="deleteType(<?= $type['id'] ?>)"
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

    <!-- Edit Modal -->
    <div id="editTypeModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-edit mr-2"></i>Edit Work Type
                </h3>
                <button onclick="closeModal('editTypeModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editTypeForm" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_type_id">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Work Type Name *</label>
                    <input type="text" name="work_name" id="edit_work_name" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Cost (₹) *</label>
                    <input type="number" name="cost" id="edit_cost" step="0.01" min="0" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="edit_description" rows="2"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Update
                    </button>
                    <button type="button" onclick="closeModal('editTypeModal')"
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

        // Add Type
        document.getElementById('addTypeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/work_done/add_type.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Work type added successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Edit Type
        function editType(type) {
            document.getElementById('edit_type_id').value = type.id;
            document.getElementById('edit_work_name').value = type.work_name;
            document.getElementById('edit_cost').value = type.cost;
            document.getElementById('edit_description').value = type.description || '';
            document.getElementById('editTypeModal').classList.add('active');
        }

        document.getElementById('editTypeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/work_done/update_type.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Work type updated successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Delete Type
        function deleteType(id) {
            Swal.fire({
                title: 'Delete Work Type?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../api/work_done/delete_type.php', {
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
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
