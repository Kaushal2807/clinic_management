<?php
/**
 * Patient Management - Main Index
 * Location: clinic/patients/index.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Patient Management';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';

if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $searchCondition = "WHERE patient_uid LIKE '%$search_escaped%' OR name LIKE '%$search_escaped%' OR contact_number LIKE '%$search_escaped%'";
}

// Get total count
$totalResult = $conn->query("SELECT COUNT(*) as total FROM patients $searchCondition");
$totalPatients = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalPatients / $perPage);

// Fetch patients
$query = "SELECT * FROM patients $searchCondition ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$patients = $conn->query($query);

// Get next patient ID
$lastPatient = $conn->query("SELECT patient_uid FROM patients ORDER BY id DESC LIMIT 1")->fetch_assoc();
$nextPatientId = 'P' . str_pad((int)substr($lastPatient['patient_uid'] ?? 'P0', 1) + 1, 4, '0', STR_PAD_LEFT);

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-user-injured text-indigo-600 mr-3"></i>Patient Management
            </h1>
            <p class="text-gray-600">Manage patient records, treatments, and prescriptions</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <?php
            $stats = [
                'total' => $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'],
                'today' => $conn->query("SELECT COUNT(*) as count FROM patients WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'],
                'pending' => $conn->query("SELECT COUNT(*) as count FROM patients WHERE payment_status = 'pending'")->fetch_assoc()['count'],
                'paid' => $conn->query("SELECT COUNT(*) as count FROM patients WHERE payment_status = 'paid'")->fetch_assoc()['count'],
            ];
            ?>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Patients</p>
                        <p class="text-3xl font-bold mt-2"><?= $stats['total'] ?></p>
                    </div>
                    <i class="fas fa-users text-4xl text-blue-200"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Today's Patients</p>
                        <p class="text-3xl font-bold mt-2"><?= $stats['today'] ?></p>
                    </div>
                    <i class="fas fa-calendar-day text-4xl text-green-200"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">Pending Payment</p>
                        <p class="text-3xl font-bold mt-2"><?= $stats['pending'] ?></p>
                    </div>
                    <i class="fas fa-clock text-4xl text-yellow-200"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Paid Patients</p>
                        <p class="text-3xl font-bold mt-2"><?= $stats['paid'] ?></p>
                    </div>
                    <i class="fas fa-check-circle text-4xl text-purple-200"></i>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
                <div class="flex-1 w-full md:max-w-md">
                    <form method="GET" action="" class="relative">
                        <input type="text" 
                               name="search"
                               placeholder="Search Patient ID, Name, or Phone..." 
                               value="<?= htmlspecialchars($search) ?>"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                    </form>
                </div>

                <button onclick="openAddPatientModal()" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                    <i class="fas fa-user-plus mr-2"></i>Add New Patient
                </button>
            </div>
        </div>

        <!-- Patients Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Patient Info</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Contact</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Payment Status</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($patients->num_rows === 0): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <i class="fas fa-user-friends text-gray-300 text-5xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-900">No patients found</p>
                                <button onclick="openAddPatientModal()" 
                                        class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition">
                                    Add First Patient
                                </button>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($patient = $patients->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-indigo-600 font-bold text-lg">
                                                <?= strtoupper(substr($patient['name'], 0, 1)) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900"><?= htmlspecialchars($patient['name']) ?></div>
                                            <div class="text-sm text-gray-500">
                                                ID: <span class="font-mono font-bold text-indigo-600"><?= $patient['patient_uid'] ?></span> | 
                                                Age: <?= $patient['age'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <i class="fas fa-phone text-gray-400 mr-2"></i><?= htmlspecialchars($patient['contact_number']) ?>
                                    </div>
                                    <?php if ($patient['address']): ?>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <i class="fas fa-map-marker-alt text-gray-400 mr-2"></i><?= htmlspecialchars(substr($patient['address'], 0, 30)) ?>...
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        <?= $patient['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                            ($patient['payment_status'] === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                        <?= ucfirst($patient['payment_status']) ?>
                                    </span>
                                    <div class="text-sm text-gray-600 mt-1">
                                        Amount: ₹<?= number_format($patient['total_amount'], 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="view.php?patient_uid=<?= $patient['patient_uid'] ?>"
                                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-medium transition">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                        <button onclick='editPatient(<?= json_encode($patient) ?>)'
                                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded text-xs font-medium transition">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <a href="../prescription/index.php?patient_uid=<?= $patient['patient_uid'] ?>&name=<?= urlencode($patient['name']) ?>"
                                           class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-xs font-medium transition">
                                            <i class="fas fa-prescription mr-1"></i>Rx
                                        </a>
                                        <a href="../treatment/index.php?patient_uid=<?= $patient['patient_uid'] ?>&name=<?= urlencode($patient['name']) ?>"
                                           class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1.5 rounded text-xs font-medium transition">
                                            <i class="fas fa-tooth mr-1"></i>Treat
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
                    Page <?= $page ?> of <?= $totalPages ?> (<?= $totalPatients ?> total patients)
                </span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 transition">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full my-8 max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl sticky top-0">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-user-plus mr-2"></i>Add New Patient
                </h3>
                <button onclick="closeModal('addPatientModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addPatientForm" class="p-6 space-y-6">
                <!-- Basic Info -->
                <div class="bg-indigo-50 rounded-xl p-6">
                    <h4 class="font-semibold text-indigo-900 mb-4">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Patient ID *</label>
                            <input type="text" name="patient_uid" value="<?= $nextPatientId ?>" readonly
                                   class="w-full px-4 py-2.5 border bg-gray-100 rounded-lg font-mono font-bold text-indigo-600">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="name" required
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Age *</label>
                            <input type="number" name="age" required min="0"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Number *</label>
                            <input type="text" name="contact_number" required
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date of Visit *</label>
                            <input type="date" name="date_of_visit" value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                        <textarea name="address" rows="2"
                                  class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <!-- Medical Info -->
                <div class="bg-green-50 rounded-xl p-6">
                    <h4 class="font-semibold text-green-900 mb-4">Medical Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Chief Complaint</label>
                            <textarea name="chief_complain" rows="2"
                                      class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Medical History</label>
                            <textarea name="medical_history" rows="2"
                                      class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="bg-yellow-50 rounded-xl p-6">
                    <h4 class="font-semibold text-yellow-900 mb-4">Payment Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Total Amount (₹)</label>
                            <input type="number" name="total_amount" step="0.01" value="0"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Status</label>
                            <select name="payment_status"
                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-yellow-500">
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pending Amount (₹)</label>
                            <input type="number" name="payment_pending" step="0.01" value="0"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-yellow-500">
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit"  
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Save Patient
                    </button>
                    <button type="button" onclick="closeModal('addPatientModal')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div id="editPatientModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full my-8 max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl sticky top-0">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-user-edit mr-2"></i>Edit Patient
                </h3>
                <button onclick="closeModal('editPatientModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editPatientForm" class="p-6 space-y-6">
                <input type="hidden" name="patient_uid" id="edit_patient_uid">
                
                <!-- Basic Info -->
                <div class="bg-indigo-50 rounded-xl p-6">
                    <h4 class="font-semibold text-indigo-900 mb-4">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Patient ID</label>
                            <input type="text" id="edit_patient_uid_display" readonly
                                   class="w-full px-4 py-2.5 border bg-gray-100 rounded-lg font-mono font-bold text-indigo-600">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="name" id="edit_name" required
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Age *</label>
                            <input type="number" name="age" id="edit_age" required min="0"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Gender</label>
                            <select name="gender" id="edit_gender"
                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Number *</label>
                            <input type="text" name="contact_number" id="edit_contact_number" required
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" id="edit_email"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date of Visit</label>
                            <input type="date" name="date_of_visit" id="edit_date_of_visit"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Blood Group</label>
                            <input type="text" name="blood_group" id="edit_blood_group"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                        <textarea name="address" id="edit_address" rows="2"
                                  class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <!-- Medical Info -->
                <div class="bg-green-50 rounded-xl p-6">
                    <h4 class="font-semibold text-green-900 mb-4">Medical Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Chief Complaint</label>
                            <textarea name="chief_complain" id="edit_chief_complain" rows="2"
                                      class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Medical History</label>
                            <textarea name="medical_history" id="edit_medical_history" rows="2"
                                      class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Family History</label>
                            <textarea name="family_history" id="edit_family_history" rows="2"
                                      class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Oral & Diet Habit</label>
                            <textarea name="oral_diet_habit" id="edit_oral_diet_habit" rows="2"
                                      class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">X-Ray Remarks</label>
                        <textarea name="xray_remark" id="edit_xray_remark" rows="2"
                                  class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="bg-yellow-50 rounded-xl p-6">
                    <h4 class="font-semibold text-yellow-900 mb-4">Payment Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Total Amount (₹)</label>
                            <input type="number" name="total_amount" id="edit_total_amount" step="0.01" value="0"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Status</label>
                            <select name="payment_status" id="edit_payment_status"
                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-yellow-500">
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pending Amount (₹)</label>
                            <input type="number" name="payment_pending" id="edit_payment_pending" step="0.01" value="0"
                                   class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-yellow-500">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <h4 class="font-semibold text-gray-900 mb-4">Additional Notes</h4>
                    <textarea name="notes" id="edit_notes" rows="3"
                              class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-gray-500"
                              placeholder="Any additional notes or remarks..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit"  
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Update Patient
                    </button>
                    <button type="button" onclick="closeModal('editPatientModal')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function openAddPatientModal() {
            document.getElementById('addPatientModal').classList.add('active');
        }

        // Add Patient
        document.getElementById('addPatientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/patients/add.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Patient added successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Edit Patient
        document.getElementById('editPatientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/patients/update.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Patient updated successfully',
                        timer: 1500
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to update patient', 'error');
            });
        });

        // Edit Patient function
        function editPatient(patient) {
            // Populate the edit form with patient data
            document.getElementById('edit_patient_uid').value = patient.patient_uid;
            document.getElementById('edit_patient_uid_display').value = patient.patient_uid;
            document.getElementById('edit_name').value = patient.name || '';
            document.getElementById('edit_age').value = patient.age || '';
            document.getElementById('edit_gender').value = patient.gender || 'Male';
            document.getElementById('edit_contact_number').value = patient.contact_number || '';
            document.getElementById('edit_email').value = patient.email || '';
            document.getElementById('edit_date_of_visit').value = patient.date_of_visit || '';
            document.getElementById('edit_blood_group').value = patient.blood_group || '';
            document.getElementById('edit_address').value = patient.address || '';
            document.getElementById('edit_chief_complain').value = patient.chief_complain || '';
            document.getElementById('edit_medical_history').value = patient.medical_history || '';
            document.getElementById('edit_family_history').value = patient.family_history || '';
            document.getElementById('edit_oral_diet_habit').value = patient.oral_diet_habit || '';
            document.getElementById('edit_xray_remark').value = patient.xray_remark || '';
            document.getElementById('edit_total_amount').value = patient.total_amount || 0;
            document.getElementById('edit_payment_status').value = patient.payment_status || 'pending';
            document.getElementById('edit_payment_pending').value = patient.payment_pending || 0;
            document.getElementById('edit_notes').value = patient.notes || '';
            
            // Show the modal
            openModal('editPatientModal');
        }
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
