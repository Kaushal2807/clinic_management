<?php
/**
 * View Prescriptions for Patient
 * Location: clinic/prescription/view.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'View Prescriptions';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();
$clinicId = ClinicContext::getClinicId();

if (!isset($_GET['patient_uid']) || empty($_GET['patient_uid'])) {
    header('Location: ../patients/index.php');
    exit;
}

$patient_uid = $_GET['patient_uid'];

// Get patient info
$stmt = $conn->prepare("SELECT id, name FROM patients WHERE patient_uid = ? AND clinic_id = ?");
$stmt->bind_param("si", $patient_uid, $clinicId);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header('Location: ../patients/index.php');
    exit;
}

// Get all prescriptions
$s = $conn->prepare("SELECT * FROM prescriptions WHERE clinic_id = ? AND patient_id = ? ORDER BY created_at DESC");
$s->bind_param('ii', $clinicId, $patient['id']);
$s->execute();
$prescriptions = $s->get_result();

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
                <i class="fas fa-prescription text-green-600 mr-3"></i>Prescriptions
            </h1>
            <p class="text-gray-600">Patient: <strong><?= htmlspecialchars($patient['name']) ?></strong> (<?= $patient_uid ?>)</p>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <a href="index.php?patient_uid=<?= urlencode($patient_uid) ?>&name=<?= urlencode($patient['name']) ?>"
               class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition inline-block">
                <i class="fas fa-plus mr-2"></i>Add New Prescription
            </a>
        </div>

        <?php if ($prescriptions->num_rows === 0): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="fas fa-prescription text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Prescriptions Found</h3>
            <p class="text-gray-600 mb-6">This patient doesn't have any prescriptions yet.</p>
            <a href="index.php?patient_uid=<?= urlencode($patient_uid) ?>&name=<?= urlencode($patient['name']) ?>"
               class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold shadow-md transition inline-block">
                <i class="fas fa-plus mr-2"></i>Create First Prescription
            </a>
        </div>
        <?php else: ?>
        
        <!-- Prescriptions List -->
        <div class="space-y-4">
            <?php while ($rx = $prescriptions->fetch_assoc()): ?>
            <?php
                // Get medicines for this prescription
                $rxId = $rx['id'];
                $medicines = $conn->query("SELECT pm.*, m.name as medicine_name, d.dose_name, du.duration_name 
                    FROM prescription_medicines pm
                    JOIN medicines m ON pm.medicine_id = m.id
                    LEFT JOIN doses d ON pm.dose_id = d.id
                    LEFT JOIN durations du ON pm.duration_id = du.id
                    WHERE pm.prescription_id = $rxId AND pm.clinic_id = $clinicId");
            ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="bg-gradient-to-r from-green-500 to-teal-500 text-white px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold">RX-<?= str_pad($rx['id'], 5, '0', STR_PAD_LEFT) ?></h3>
                            <p class="text-sm opacity-90">
                                <i class="fas fa-calendar mr-2"></i><?= date('d M Y, h:i A', strtotime($rx['created_at'])) ?>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <a href="print.php?id=<?= $rx['id'] ?>" target="_blank"
                               class="bg-white text-green-600 hover:bg-gray-100 px-4 py-2 rounded-lg font-semibold transition">
                                <i class="fas fa-print mr-2"></i>Print
                            </a>
                            <button onclick="deletePrescription(<?= $rx['id'] ?>)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <h4 class="font-bold text-gray-900 mb-4 text-lg">Medicines:</h4>
                    <?php if ($medicines->num_rows === 0): ?>
                        <p class="text-gray-500 italic">No medicines prescribed</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php $count = 1; while ($med = $medicines->fetch_assoc()): ?>
                            <div class="flex items-start gap-4 bg-gray-50 p-4 rounded-lg">
                                <div class="bg-green-100 text-green-700 font-bold w-8 h-8 flex items-center justify-center rounded-full flex-shrink-0">
                                    <?= $count++ ?>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($med['medicine_name']) ?></div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <span class="inline-flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded mr-2">
                                            <i class="fas fa-pills mr-1"></i> <?= htmlspecialchars($med['dose_name'] ?? 'N/A') ?>
                                        </span>
                                        <span class="inline-flex items-center bg-purple-100 text-purple-800 px-2 py-1 rounded">
                                            <i class="fas fa-clock mr-1"></i> <?= htmlspecialchars($med['duration_name'] ?? 'N/A') ?>
                                        </span>
                                    </div>
                                    <?php if ($med['instructions']): ?>
                                    <div class="text-sm text-gray-700 mt-2 italic">
                                        <i class="fas fa-info-circle mr-1"></i><?= htmlspecialchars($med['instructions']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
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
                    fetch('<?= BASE_URL ?>/api/prescription/delete.php', {
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
