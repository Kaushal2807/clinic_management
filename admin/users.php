<?php
/**
 * Admin - User Management
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Handle user operations
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $user_type = $_POST['user_type'];
                $clinic_id = !empty($_POST['clinic_id']) ? $_POST['clinic_id'] : null;
                
                // Hash password
                $hashed_password = Auth::hashPassword($password);
                
                // Insert user
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password, user_type, clinic_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssi', $full_name, $email, $hashed_password, $user_type, $clinic_id);
                
                if ($stmt->execute()) {
                    $message = 'User created successfully!';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to create user: ' . $stmt->error);
                }
                break;
                
            case 'edit':
                $user_id = $_POST['user_id'];
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $user_type = $_POST['user_type'];
                $clinic_id = !empty($_POST['clinic_id']) ? $_POST['clinic_id'] : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, user_type = ?, clinic_id = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param('sssiii', $full_name, $email, $user_type, $clinic_id, $is_active, $user_id);
                
                if ($stmt->execute()) {
                    $message = 'User updated successfully!';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update user');
                }
                break;
                
            case 'change_password':
                $user_id = $_POST['user_id'];
                $new_password = $_POST['new_password'];
                
                $hashed_password = Auth::hashPassword($new_password);
                
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param('si', $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $message = 'Password changed successfully!';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to change password');
                }
                break;
                
            case 'delete':
                $user_id = $_POST['user_id'];
                
                // Don't allow deleting self
                if ($user_id == Session::getUserId()) {
                    throw new Exception('You cannot delete your own account!');
                }
                
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                
                if ($stmt->execute()) {
                    $message = 'User deleted successfully!';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to delete user');
                }
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get all users with clinic info
$users = $db->query("
    SELECT u.*, c.clinic_name 
    FROM users u
    LEFT JOIN clinics c ON u.clinic_id = c.id
    ORDER BY u.created_at DESC
");

// Get all clinics for dropdowns
$clinics = $db->query("SELECT id, clinic_name FROM clinics WHERE is_active = 1 ORDER BY clinic_name");

$clinicsList = [];
while ($clinic = $clinics->fetch_assoc()) {
    $clinicsList[] = $clinic;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../assets/favicon/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="../assets/favicon/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .modal { display: none; }
        .modal.active { display: flex; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1>
                    </div>
                    <nav class="ml-10 flex space-x-4">
                        <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Dashboard</a>
                        <a href="clinics.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Clinics</a>
                        <a href="users.php" class="px-3 py-2 rounded-md text-sm font-semibold text-indigo-600 bg-indigo-50">Users</a>
                        <a href="activity.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Activity</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-700">
                        <span class="font-semibold"><?= htmlspecialchars(Session::getUserName()) ?></span>
                    </span>
                    <a href="<?= BASE_URL ?>/public/logout.php" 
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Alert Messages -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
            <div class="flex items-center">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-3"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">User Management</h2>
                <p class="text-gray-600 mt-1">Manage system users and their access levels</p>
            </div>
            <button onclick="openAddModal()" class="gradient-bg text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>Add New User
            </button>
        </div>

        <!-- Search Bar -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="userSearch" 
                       class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition text-sm"
                       placeholder="Search by name, email, or clinic..." autocomplete="off">
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Clinic</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Registered</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition" data-searchable="<?= strtolower(htmlspecialchars($user['full_name'] . ' ' . $user['email'] . ' ' . $user['user_type'] . ' ' . ($user['clinic_name'] ?? ''))) ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold">
                                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['full_name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $user['user_type'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= ucfirst($user['user_type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?= $user['clinic_name'] ? htmlspecialchars($user['clinic_name']) : '<span class="text-gray-400">N/A</span>' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <button onclick='openEditModal(<?= json_encode($user) ?>)' 
                                        class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button onclick='openPasswordModal(<?= $user["id"] ?>, "<?= htmlspecialchars($user["full_name"]) ?>")' 
                                        class="text-blue-600 hover:text-blue-900 font-medium mr-3">
                                    <i class="fas fa-key"></i> Password
                                </button>
                                <?php if ($user['id'] != Session::getUserId()): ?>
                                <button onclick='confirmDelete(<?= $user["id"] ?>, "<?= htmlspecialchars($user["full_name"]) ?>")' 
                                        class="text-red-600 hover:text-red-900 font-medium">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full m-4 max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-xl">
                <h3 class="text-xl font-bold">Add New User</h3>
                <button onclick="closeModal('addModal')" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="add">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="full_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Password *</label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">User Type *</label>
                        <select name="user_type" id="add_user_type" required onchange="toggleClinicField('add')"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="admin">Admin</option>
                            <option value="clinic">Clinic User</option>
                        </select>
                    </div>
                    
                    <div id="add_clinic_field" style="display: none;">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Clinic *</label>
                        <select name="clinic_id" id="add_clinic_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Select Clinic</option>
                            <?php foreach ($clinicsList as $clinic): ?>
                            <option value="<?= $clinic['id'] ?>"><?= htmlspecialchars($clinic['clinic_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('addModal')" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="gradient-bg text-white px-6 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-plus mr-2"></i>Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full m-4 max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-xl">
                <h3 class="text-xl font-bold">Edit User</h3>
                <button onclick="closeModal('editModal')" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="full_name" id="edit_full_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" id="edit_email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">User Type *</label>
                        <select name="user_type" id="edit_user_type" required onchange="toggleClinicField('edit')"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="admin">Admin</option>
                            <option value="clinic">Clinic User</option>
                        </select>
                    </div>
                    
                    <div id="edit_clinic_field">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Clinic</label>
                        <select name="clinic_id" id="edit_clinic_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Select Clinic</option>
                            <?php foreach ($clinicsList as $clinic): ?>
                            <option value="<?= $clinic['id'] ?>"><?= htmlspecialchars($clinic['clinic_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active" class="w-4 h-4 text-indigo-600 rounded">
                        <label for="edit_is_active" class="ml-2 text-sm font-medium text-gray-700">Active User</label>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('editModal')" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="gradient-bg text-white px-6 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full m-4">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-xl">
                <h3 class="text-xl font-bold">Change Password</h3>
                <button onclick="closeModal('passwordModal')" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="user_id" id="password_user_id">
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-4">Changing password for: <span id="password_user_name" class="font-semibold text-gray-900"></span></p>
                    
                    <label class="block text-sm font-semibold text-gray-700 mb-2">New Password *</label>
                    <input type="password" name="new_password" required minlength="6"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('passwordModal')" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="delete_user_id">
    </form>

    <script>
        // User search filter
        const userSearch = document.getElementById('userSearch');
        userSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('tbody tr[data-searchable]');
            let visibleCount = 0;
            rows.forEach(row => {
                const text = row.getAttribute('data-searchable');
                const match = !query || text.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            let noResults = document.getElementById('noSearchResults');
            if (noResults) noResults.remove();
            if (visibleCount === 0 && query) {
                const tbody = document.querySelector('tbody');
                const tr = document.createElement('tr');
                tr.id = 'noSearchResults';
                tr.innerHTML = '<td colspan="7" class="px-6 py-8 text-center text-gray-500">No users match your search.</td>';
                tbody.appendChild(tr);
            }
        });

        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_user_type').value = user.user_type;
            document.getElementById('edit_clinic_id').value = user.clinic_id || '';
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            
            toggleClinicField('edit');
            document.getElementById('editModal').classList.add('active');
        }

        function openPasswordModal(userId, userName) {
            document.getElementById('password_user_id').value = userId;
            document.getElementById('password_user_name').textContent = userName;
            document.getElementById('passwordModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function toggleClinicField(prefix) {
            const userType = document.getElementById(prefix + '_user_type').value;
            const clinicField = document.getElementById(prefix + '_clinic_field');
            const clinicSelect = document.getElementById(prefix + '_clinic_id');
            
            if (userType === 'clinic') {
                clinicField.style.display = 'block';
                clinicSelect.required = true;
            } else {
                clinicField.style.display = 'none';
                clinicSelect.required = false;
                clinicSelect.value = '';
            }
        }

        function confirmDelete(userId, userName) {
            if (confirm('Are you sure you want to delete user "' + userName + '"? This action cannot be undone!')) {
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('addModal');
                closeModal('editModal');
                closeModal('passwordModal');
            }
        });

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
        });
    </script>

</body>
</html>
