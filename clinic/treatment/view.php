<?php
/**
 * View Treatment History for Patient
 * Location: clinic/treatment/view.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Treatment History';
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

// Get all treatments
$treatments = $conn->query("SELECT * FROM treatments WHERE patient_id = (SELECT id FROM patients WHERE patient_uid = '$patient_uid') ORDER BY treatment_date DESC");

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
                <i class="fas fa-tooth text-blue-600 mr-3"></i>Treatment History
            </h1>
            <p class="text-gray-600">Patient: <strong><?= htmlspecialchars($patient['name']) ?></strong> (<?= $patient_uid ?>)</p>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <div class="flex gap-3">
                <a href="index.php?patient_uid=<?= urlencode($patient_uid) ?>"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition inline-block">
                    <i class="fas fa-plus mr-2"></i>Add New Treatment
                </a>
                <a href="print.php?patient_uid=<?= urlencode($patient_uid) ?>" target="_blank"
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition inline-block">
                    <i class="fas fa-file-pdf mr-2"></i>Download Plan
                </a>
            </div>
        </div>

        <?php if ($treatments->num_rows === 0): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="fas fa-tooth text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Treatments Found</h3>
            <p class="text-gray-600 mb-6">This patient doesn't have any treatment records yet.</p>
            <a href="index.php?patient_uid=<?= urlencode($patient_uid) ?>"
               class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold shadow-md transition inline-block">
                <i class="fas fa-plus mr-2"></i>Create First Treatment
            </a>
        </div>
        <?php else: ?>
        
        <!-- Treatment Timeline -->
        <div class="space-y-6">
            <?php while ($treatment = $treatments->fetch_assoc()): ?>
            <?php
                $teeth = explode(',', $treatment['selected_teeth']);
                $statusColors = [
                    'planned' => 'bg-yellow-100 text-yellow-800',
                    'in_progress' => 'bg-blue-100 text-blue-800',
                    'completed' => 'bg-green-100 text-green-800'
                ];
                $statusColor = $statusColors[$treatment['status']] ?? 'bg-gray-100 text-gray-800';
            ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white px-6 py-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-bold"><?= htmlspecialchars($treatment['treatment_name']) ?></h3>
                            <p class="text-sm opacity-90 mt-1">
                                <i class="fas fa-calendar mr-2"></i><?= date('d M Y', strtotime($treatment['treatment_date'])) ?>
                            </p>
                            <?php if ($treatment['category']): ?>
                            <span class="inline-block bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-semibold mt-2">
                                <?= htmlspecialchars($treatment['category']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-2">
                            <span class="<?= $statusColor ?> px-4 py-2 rounded-lg font-semibold uppercase text-sm">
                                <?= str_replace('_', ' ', $treatment['status']) ?>
                            </span>
                            <button onclick="deleteTreatment(<?= $treatment['id'] ?>)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Selected Teeth -->
                        <div>
                            <h4 class="font-bold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-tooth text-blue-600 mr-2"></i>Selected Teeth
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($teeth as $tooth): ?>
                                    <?php if (trim($tooth)): ?>
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-lg font-bold">
                                        <?= trim($tooth) ?>
                                    </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Cost & Next Visit -->
                        <div>
                            <h4 class="font-bold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-info-circle text-indigo-600 mr-2"></i>Details
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                    <span class="text-gray-700 font-medium">Cost:</span>
                                    <span class="text-green-600 font-bold text-lg">₹<?= number_format($treatment['cost'], 2) ?></span>
                                </div>
                                <?php if ($treatment['next_visit']): ?>
                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                    <span class="text-gray-700 font-medium">Next Visit:</span>
                                    <span class="text-indigo-600 font-semibold"><?= date('d M Y', strtotime($treatment['next_visit'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <?php if ($treatment['description']): ?>
                    <div class="mb-4">
                        <h4 class="font-bold text-gray-900 mb-2">Description:</h4>
                        <p class="text-gray-700 bg-gray-50 p-4 rounded-lg"><?= nl2br(htmlspecialchars($treatment['description'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Notes -->
                    <?php if ($treatment['notes']): ?>
                    <div>
                        <h4 class="font-bold text-gray-900 mb-2">Notes:</h4>
                        <p class="text-gray-700 bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400"><?= nl2br(htmlspecialchars($treatment['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function deleteTreatment(id) {
            Swal.fire({
                title: 'Delete Treatment?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('<?= BASE_URL ?>/api/treatment/delete.php', {
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
