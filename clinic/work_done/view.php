<?php
/**
 * View Work Done History for Patient
 * Location: clinic/work_done/view.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Work Done History';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

if (!isset($_GET['patient_uid']) || empty($_GET['patient_uid'])) {
    header('Location: ../patients/index.php');
    exit;
}

$patient_uid = $_GET['patient_uid'];

// Get patient info
$stmt = $conn->prepare("SELECT name FROM patients WHERE patient_uid = ?");
$stmt->bind_param("s", $patient_uid);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header('Location: ../patients/index.php');
    exit;
}

// Get all work done
$workDone = $conn->query("SELECT pwd.*, w.work_name FROM patient_work_done pwd JOIN work_done w ON pwd.work_done_id = w.id WHERE pwd.patient_id = (SELECT id FROM patients WHERE patient_uid = '$patient_uid') ORDER BY pwd.work_date DESC");

// Calculate total cost
$totalCost = $conn->query("SELECT COALESCE(SUM(total_cost), 0) as total FROM patient_work_done WHERE patient_id = (SELECT id FROM patients WHERE patient_uid = '$patient_uid')")->fetch_assoc()['total'];

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Back Button -->
        <div class="mb-6">
            <a href="../patients/view.php?patient_uid=<?= urlencode($patient_uid) ?>" 
               class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i> Back to Patient Details
            </a>
        </div>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-briefcase-medical text-purple-600 mr-3"></i>Work Done History
            </h1>
            <p class="text-gray-600">Patient: <strong><?= htmlspecialchars($patient['name']) ?></strong> (<?= $patient_uid ?>)</p>
        </div>

        <!-- Total Cost Card -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">Total Work Done Cost</p>
                    <h2 class="text-4xl font-bold">₹<?= number_format($totalCost, 2) ?></h2>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                    <i class="fas fa-calculator text-4xl"></i>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <div class="flex gap-3">
                <a href="index.php?patient_uid=<?= urlencode($patient_uid) ?>"
                   class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition inline-block">
                    <i class="fas fa-plus mr-2"></i>Add New Work
                </a>
                <a href="print.php?patient_uid=<?= urlencode($patient_uid) ?>" target="_blank"
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition inline-block">
                    <i class="fas fa-receipt mr-2"></i>Download Invoice
                </a>
            </div>
        </div>

        <?php if ($workDone->num_rows === 0): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="fas fa-briefcase-medical text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Work Records Found</h3>
            <p class="text-gray-600 mb-6">This patient doesn't have any work done records yet.</p>
            <a href="index.php?patient_uid=<?= urlencode($patient_uid) ?>"
               class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-semibold shadow-md transition inline-block">
                <i class="fas fa-plus mr-2"></i>Add First Work
            </a>
        </div>
        <?php else: ?>
        
        <!-- Work Done List -->
        <div class="space-y-4">
            <?php while ($work = $workDone->fetch_assoc()): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold"><?= htmlspecialchars($work['work_name']) ?></h3>
                            <p class="text-sm opacity-90">
                                <i class="fas fa-calendar mr-2"></i><?= date('d M Y', strtotime($work['work_date'])) ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-sm opacity-90">Cost</p>
                                <p class="text-2xl font-bold">₹<?= number_format($work['cost'], 2) ?></p>
                            </div>
                            <button onclick="deleteWork(<?= $work['id'] ?>)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php if ($work['description']): ?>
                <div class="p-6">
                    <h4 class="font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fas fa-sticky-note text-purple-600 mr-2"></i>Description
                    </h4>
                    <p class="text-gray-700 bg-gray-50 p-4 rounded-lg"><?= nl2br(htmlspecialchars($work['description'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function deleteWork(id) {
            Swal.fire({
                title: 'Delete Work Record?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('<?= BASE_URL ?>/api/work_done/delete.php', {
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
