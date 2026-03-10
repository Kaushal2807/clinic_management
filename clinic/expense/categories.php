<?php
/**
 * Expense Categories Management
 * Location: clinic/expense/categories.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Expense Categories';
$conn = ClinicContext::getConnection();
$clinicId = ClinicContext::getClinicId();

$categories = $conn->query("SELECT * FROM expense_categories WHERE clinic_id = $clinicId ORDER BY category_name");

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-tags text-indigo-600 mr-3"></i>Expense Categories
                </h1>
                <p class="text-gray-600">Manage expense categories and types</p>
            </div>
            <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Expenses
            </a>
        </div>

        <!-- Add Category Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-plus-circle text-green-600 mr-2"></i>Add New Category
            </h2>
            <form id="addCategoryForm" class="flex gap-3">
                <input type="text" name="category_name" placeholder="Category name..." required
                       class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <button type="submit" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Add Category
                </button>
            </form>
        </div>

        <!-- Categories Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">ID</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Category Name</th>
                            <th class="px-6 py-4 text-center text-sm font-bold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($categories->num_rows === 0): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center">
                                <i class="fas fa-tags text-gray-300 text-5xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-900">No categories found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-semibold text-gray-900"><?= $cat['id'] ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['category_name'], ENT_QUOTES) ?>')"
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <button onclick="deleteCategory(<?= $cat['id'] ?>)"
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
    <div id="editCategoryModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-edit mr-2"></i>Edit Category
                </h3>
                <button onclick="closeModal('editCategoryModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editCategoryForm" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_category_id">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category Name *</label>
                    <input type="text" name="category_name" id="edit_category_name" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Update
                    </button>
                    <button type="button" onclick="closeModal('editCategoryModal')"
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

        // Add Category
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/expense/add_category.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Category added successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Edit Category
        function editCategory(id, name) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('editCategoryModal').classList.add('active');
        }

        document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/expense/update_category.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Category updated successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Delete Category
        function deleteCategory(id) {
            Swal.fire({
                title: 'Delete Category?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../api/expense/delete_category.php', {
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
