<?php
/**
 * Certificate Generation Module
 * Location: clinic/certificate/index.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Medical Certificates';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Get patient_uid from URL if provided
$selectedPatientUid = $_GET['patient_uid'] ?? '';
$selectedPatientName = $_GET['name'] ?? '';

// Get all patients for dropdown
$patients = $conn->query("SELECT patient_uid, name FROM patients ORDER BY name");

// Get all certificates
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$certificates = $conn->query("SELECT c.*, p.name as patient_name 
    FROM certificates c
    JOIN patients p ON c.patient_uid = p.patient_uid
    ORDER BY c.certificate_date DESC
    LIMIT $perPage OFFSET $offset");

$totalCertificates = $conn->query("SELECT COUNT(*) as total FROM certificates")->fetch_assoc()['total'];
$totalPages = ceil($totalCertificates / $perPage);

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-certificate text-amber-600 mr-3"></i>Medical Certificates
            </h1>
            <p class="text-gray-600">Generate and manage medical certificates for patients</p>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <button onclick="openCertificateModal()" 
                    class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                <i class="fas fa-plus mr-2"></i>Generate Certificate
            </button>
        </div>

        <!-- Certificates Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-amber-600 to-orange-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Certificate ID</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Patient</th>
                            <th class="px-6 py-4 text-left text-sm font-bold uppercase">Date</th>
                            <th class="px-6 py-4 text-center text-sm font-bold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($certificates->num_rows === 0): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <i class="fas fa-certificate text-gray-300 text-5xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-900">No certificates generated yet</p>
                                <button onclick="openCertificateModal()" 
                                        class="mt-4 bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                                    <i class="fas fa-plus mr-2"></i>Generate First Certificate
                                </button>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($cert = $certificates->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-indigo-600">CERT-<?= str_pad($cert['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($cert['patient_name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= $cert['patient_uid'] ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900"><?= date('d M Y', strtotime($cert['certificate_date'])) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <a href="print.php?id=<?= $cert['id'] ?>" target="_blank"
                                           class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-print mr-1"></i>Print
                                        </a>
                                        <button onclick="viewCertificate(<?= $cert['id'] ?>)"
                                                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </button>
                                        <button onclick="deleteCertificate(<?= $cert['id'] ?>)"
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

    <!-- Certificate Modal -->
    <div id="certificateModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="gradient-bg text-white px-6 py-4 flex justify-between items-center rounded-t-2xl sticky top-0">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-certificate mr-2"></i>Generate Medical Certificate
                </h3>
                <button onclick="closeModal('certificateModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="certificateForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Patient *</label>
                    <select name="patient_uid" required id="cert_patient_uid"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">-- Select Patient --</option>
                        <?php 
                        $patients->data_seek(0);
                        while ($p = $patients->fetch_assoc()): 
                        ?>
                            <option value="<?= $p['patient_uid'] ?>" <?= $selectedPatientUid === $p['patient_uid'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['patient_uid']) ?> - <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Patient Name *</label>
                    <input type="text" name="patient_name" required id="cert_patient_name" 
                           value="<?= htmlspecialchars($selectedPatientName) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Certificate Date *</label>
                    <input type="date" name="certificate_date" required value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Complaints *</label>
                    <textarea name="complaints" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
                              placeholder="Patient's chief complaints..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Treatment Done *</label>
                    <textarea name="treatment_done" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
                              placeholder="Treatment procedures performed..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Advice</label>
                    <textarea name="advise" rows="2"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
                              placeholder="Medical advice and recommendations..."></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Generate Certificate
                    </button>
                    <button type="button" onclick="closeModal('certificateModal')"
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

        function openCertificateModal() {
            document.getElementById('certificateModal').classList.add('active');
        }

        // Auto-fill patient name when patient is selected
        document.getElementById('cert_patient_uid').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const name = selectedOption.text.split(' - ')[1];
                document.getElementById('cert_patient_name').value = name;
            } else {
                document.getElementById('cert_patient_name').value = '';
            }
        });

        // Generate Certificate
        document.getElementById('certificateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('<?= BASE_URL ?>/api/certificate/save.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Certificate Generated!',
                        text: 'Certificate saved successfully',
                        showCancelButton: true,
                        confirmButtonText: 'Print Certificate',
                        cancelButtonText: 'Close'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open(`print.php?id=${data.id}`, '_blank');
                        }
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });

        // Delete Certificate
        function deleteCertificate(id) {
            Swal.fire({
                title: 'Delete Certificate?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('<?= BASE_URL ?>/api/certificate/delete.php', {
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

        // View Certificate  
        function viewCertificate(id) {
            window.open(`print.php?id=${id}`, '_blank');
        }
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
