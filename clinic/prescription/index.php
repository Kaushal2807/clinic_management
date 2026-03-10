<?php
/**
 * Prescription Management - Main Page
 * Location: clinic/prescription/index.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Prescription Management';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();
$clinicId = ClinicContext::getClinicId();

// Get patient UID if provided
$patient_uid = $_GET['patient_uid'] ?? '';

// Get all patients for dropdown
$patients = $conn->query("SELECT patient_uid, name FROM patients WHERE clinic_id = $clinicId ORDER BY name");

// Get medicines for prescription
$medicines = $conn->query("SELECT id, medicine_name, composition FROM medicine WHERE clinic_id = $clinicId ORDER BY medicine_name");

// Get doses and durations
$doses = $conn->query("SELECT * FROM doses WHERE clinic_id = $clinicId ORDER BY dose_name");
$durations = $conn->query("SELECT * FROM durations WHERE clinic_id = $clinicId ORDER BY duration_value");

// If patient_uid provided, get patient details
$patient = null;
if ($patient_uid) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_uid = ? AND clinic_id = ?");
    $stmt->bind_param('si', $patient_uid, $clinicId);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
}

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-prescription text-green-600 mr-3"></i>Prescription Management
            </h1>
            <p class="text-gray-600">Create and manage patient prescriptions</p>
        </div>

        <!-- Prescription Form -->
        <div class="bg-white rounded-xl shadow-md p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                <i class="fas fa-file-prescription mr-2 text-green-600"></i>New Prescription
            </h2>

            <form id="prescriptionForm" class="space-y-6">
                
                <!-- Patient Selection -->
                <div class="bg-indigo-50 rounded-xl p-6">
                    <h3 class="font-semibold text-indigo-900 mb-4">Patient Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Select Patient *</label>
                            <select name="patient_uid" id="patient_uid" required onchange="loadPatientInfo(this.value)"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="">-- Select Patient --</option>
                                <?php 
                                $patients->data_seek(0);
                                while ($p = $patients->fetch_assoc()): 
                                ?>
                                    <option value="<?= $p['patient_uid'] ?>" <?= $patient_uid === $p['patient_uid'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['patient_uid']) ?> - <?= htmlspecialchars($p['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date *</label>
                            <input type="date" name="prescription_date" value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <?php if ($patient): ?>
                    <div class="mt-4 p-4 bg-white rounded-lg border border-indigo-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-semibold text-gray-600">Name:</span>
                                <p class="text-gray-900"><?= htmlspecialchars($patient['name']) ?></p>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Age:</span>
                                <p class="text-gray-900"><?= $patient['age'] ?> years</p>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Contact:</span>
                                <p class="text-gray-900"><?= htmlspecialchars($patient['contact_number']) ?></p>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Last Visit:</span>
                                <p class="text-gray-900"><?= date('d-m-Y', strtotime($patient['date_of_visit'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Medicines Section -->
                <div class="bg-green-50 rounded-xl p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-green-900">Medicines</h3>
                        <button type="button" onclick="addMedicineRow()" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            <i class="fas fa-plus mr-2"></i>Add Medicine
                        </button>
                    </div>
                    
                    <div id="medicineRows" class="space-y-3">
                        <!-- Medicine rows will be added here -->
                    </div>
                </div>

                <!-- Additional Instructions -->
                <div class="bg-blue-50 rounded-xl p-6">
                    <h3 class="font-semibold text-blue-900 mb-4">Additional Notes</h3>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="Special instructions, dietary advice, follow-up notes, etc."></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4">
                    <button type="submit" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-save mr-2"></i>Save Prescription
                    </button>
                    <button type="button" onclick="window.location.href='list.php'"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-list mr-2"></i>View All Prescriptions
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let medicineCounter = 0;
        const medicines = <?= json_encode($medicines->fetch_all(MYSQLI_ASSOC)) ?>;
        const doses = <?= json_encode($doses->fetch_all(MYSQLI_ASSOC)) ?>;
        const durations = <?= json_encode($durations->fetch_all(MYSQLI_ASSOC)) ?>;

        // Add medicine row
        function addMedicineRow() {
            medicineCounter++;
            const row = document.createElement('div');
            row.className = 'bg-white rounded-lg p-4 border border-gray-200';
            row.id = 'medicine-row-' + medicineCounter;
            
            row.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Medicine *</label>
                        <select name="medicines[${medicineCounter}][medicine_id]" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                            <option value="">Select Medicine</option>
                            ${medicines.map(m => `<option value="${m.id}">${m.medicine_name}${m.composition ? ' - ' + m.composition : ''}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Dosage *</label>
                        <select name="medicines[${medicineCounter}][dose]" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                            <option value="">Select Dose</option>
                            ${doses.map(d => `<option value="${d.dose_name}">${d.dose_name}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Duration *</label>
                        <select name="medicines[${medicineCounter}][duration]" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                            <option value="">Duration</option>
                            ${durations.map(d => `<option value="${d.duration_value}">${d.duration_value}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="button" onclick="removeMedicineRow(${medicineCounter})"
                                class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                
                <div class="mt-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Instructions</label>
                    <input type="text" name="medicines[${medicineCounter}][instructions]"
                           placeholder="e.g., Take after meals, With milk, Before bedtime"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                </div>
            `;
            
            document.getElementById('medicineRows').appendChild(row);
        }

        // Remove medicine row
        function removeMedicineRow(id) {
            document.getElementById('medicine-row-' + id).remove();
        }

        // Load patient info
        function loadPatientInfo(patientUid) {
            if (patientUid) {
                window.location.href = '?patient_uid=' + encodeURIComponent(patientUid);
            }
        }

        // Initialize with one medicine row
        addMedicineRow();

        // Form submission
        document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Validate at least one medicine
            const medicineRows = document.querySelectorAll('#medicineRows > div');
            if (medicineRows.length === 0) {
                Swal.fire('Error', 'Please add at least one medicine', 'error');
                return;
            }
            
            fetch('../../api/prescription/add.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Prescription saved successfully',
                        showCancelButton: true,
                        confirmButtonText: 'Print Prescription',
                        cancelButtonText: 'Add Another'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('print.php?id=' + data.prescription_id, '_blank');
                        }
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'An error occurred while saving prescription', 'error');
            });
        });
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
