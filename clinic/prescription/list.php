<?php
/**
 * Prescription List Page
 * Location: clinic/prescription/list.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'All Prescriptions';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Search and patient filter
$search = $_GET['search'] ?? '';
$patient_uid_filter = $_GET['patient_uid'] ?? '';
$searchCondition = '';
$conditions = [];

if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $conditions[] = "(p.patient_uid LIKE '%$search_escaped%' OR pt.name LIKE '%$search_escaped%')";
}

if ($patient_uid_filter) {
    $patient_uid_escaped = $conn->real_escape_string($patient_uid_filter);
    $conditions[] = "pt.patient_uid = '$patient_uid_escaped'";
}

if (!empty($conditions)) {
    $searchCondition = "WHERE " . implode(' AND ', $conditions);
}

// Get total count
$totalResult = $conn->query("SELECT COUNT(*) as total FROM prescriptions p 
                             JOIN patients pt ON p.patient_id = pt.id $searchCondition");
$totalPrescriptions = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalPrescriptions / $perPage);

// Fetch prescriptions
$query = "SELECT p.*, pt.name, pt.age, pt.contact_number, pt.patient_uid
          FROM prescriptions p
          JOIN patients pt ON p.patient_id = pt.id
          $searchCondition
          ORDER BY p.created_at DESC 
          LIMIT $perPage OFFSET $offset";
$prescriptions = $conn->query($query);

// Get patient info if filtering by patient
$patientInfo = null;
if ($patient_uid_filter) {
    $stmt = $conn->prepare("SELECT name, patient_uid FROM patients WHERE patient_uid = ?");
    $stmt->bind_param('s', $patient_uid_filter);
    $stmt->execute();
    $patientInfo = $stmt->get_result()->fetch_assoc();
}

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if ($patientInfo): ?>
        <!-- Back Button -->
        <div class="mb-6">
            <a href="../patients/view.php?patient_uid=<?= urlencode($patient_uid_filter) ?>" 
               class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i> Back to Patient Details
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-file-prescription text-green-600 mr-3"></i>
                <?php if ($patientInfo): ?>
                    Prescriptions for <?= htmlspecialchars($patientInfo['name']) ?>
                <?php else: ?>
                    All Prescriptions
                <?php endif; ?>
            </h1>
            <p class="text-gray-600">
                <?php if ($patientInfo): ?>
                    Patient ID: <?= htmlspecialchars($patientInfo['patient_uid']) ?>
                <?php else: ?>
                    View and manage all patient prescriptions
                <?php endif; ?>
            </p>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
                <div class="flex-1 w-full md:max-w-md">
                    <form method="GET" class="relative">
                        <input type="text" name="search" placeholder="Search by Patient ID or Name..."
                               value="<?= htmlspecialchars($search) ?>"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                    </form>
                </div>

                <a href="index.php" 
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                    <i class="fas fa-plus mr-2"></i>New Prescription
                </a>
            </div>
        </div>

        <!-- Prescriptions Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-green-600 to-emerald-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Prescription ID</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Patient Info</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Medicines</th>
                            <th class="px-6 py-4 text-center text-sm font-bold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($prescriptions->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-prescription text-gray-300 text-5xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-900">No prescriptions found</p>
                                <a href="index.php" class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold transition">
                                    Create First Prescription
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($rx = $prescriptions->fetch_assoc()): ?>
                            <?php
                            // Get medicine count
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescription_medicines WHERE prescription_id = ?");
                            $stmt->bind_param('i', $rx['id']);
                            $stmt->execute();
                            $medicineCount = $stmt->get_result()->fetch_assoc()['count'];
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-bold text-green-600">
                                        RX-<?= str_pad($rx['id'], 5, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($rx['name']) ?></div>
                                    <div class="text-sm text-gray-500">
                                        ID: <?= $rx['patient_uid'] ?> | Age: <?= $rx['age'] ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= date('d M Y', strtotime($rx['prescription_date'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= date('h:i A', strtotime($rx['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-pills mr-1"></i><?= $medicineCount ?> Medicine(s)
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <a href="view.php?id=<?= $rx['id'] ?>"
                                           class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                        <a href="print.php?id=<?= $rx['id'] ?>" target="_blank"
                                           class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-print mr-1"></i>Print
                                        </a>
                                        <button onclick="deletePrescription(<?= $rx['id'] ?>)"
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

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 border-t flex justify-between items-center">
                <span class="text-sm text-gray-700">
                    Page <?= $page ?> of <?= $totalPages ?> (<?= $totalPrescriptions ?> total)
                </span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $patient_uid_filter ? '&patient_uid=' . urlencode($patient_uid_filter) : '' ?>"
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $patient_uid_filter ? '&patient_uid=' . urlencode($patient_uid_filter) : '' ?>"
                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deletePrescription(id) {
            Swal.fire({
                title: 'Delete Prescription?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../api/prescription/delete.php', {
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
