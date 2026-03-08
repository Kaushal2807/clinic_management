<?php
/**
 * Patient Detail View
 * Location: clinic/patients/view.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Patient Details';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

if (!isset($_GET['patient_uid']) || empty($_GET['patient_uid'])) {
    header('Location: index.php');
    exit;
}

$patient_uid = $_GET['patient_uid'];

// Get patient details
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_uid = ?");
$stmt->bind_param("s", $patient_uid);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header('Location: index.php');
    exit;
}

// Get prescriptions count
$rxCount = $conn->query("SELECT COUNT(*) as count FROM prescriptions WHERE patient_id = (SELECT id FROM patients WHERE patient_uid = '$patient_uid')")->fetch_assoc()['count'];

// Get treatments count
$treatmentCount = $conn->query("SELECT COUNT(*) as count FROM treatments WHERE patient_id = (SELECT id FROM patients WHERE patient_uid = '$patient_uid')")->fetch_assoc()['count'];

// Get work done count
$workCount = $conn->query("SELECT COUNT(*) as count FROM patient_work_done WHERE patient_id = (SELECT id FROM patients WHERE patient_uid = '$patient_uid')")->fetch_assoc()['count'];

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Back Button -->
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i> Back to Patients
            </a>
        </div>

        <!-- Patient Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <div class="bg-white bg-opacity-20 h-24 w-24 rounded-full flex items-center justify-center">
                        <span class="text-4xl font-bold"><?= strtoupper(substr($patient['name'], 0, 1)) ?></span>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold mb-2"><?= htmlspecialchars($patient['name']) ?></h1>
                        <div class="flex items-center gap-4 text-lg">
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-lg">
                                <i class="fas fa-id-card mr-2"></i><?= $patient['patient_uid'] ?>
                            </span>
                            <span><i class="fas fa-birthday-cake mr-2"></i>Age: <?= $patient['age'] ?></span>
                            <span><i class="fas fa-phone mr-2"></i><?= htmlspecialchars($patient['contact_number']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <a href="print.php?patient_uid=<?= urlencode($patient_uid) ?>" target="_blank"
                       class="bg-green-600 text-white hover:bg-green-700 px-6 py-3 rounded-lg font-semibold shadow-md transition mb-2 inline-block">
                        <i class="fas fa-file-pdf mr-2"></i>Download Report
                    </a>
                    <br>
                    <a href="<?= BASE_URL ?>/clinic/prescription/index.php?patient_uid=<?= urlencode($patient_uid) ?>&name=<?= urlencode($patient['name']) ?>"
                       class="bg-white text-indigo-600 hover:bg-gray-100 px-6 py-3 rounded-lg font-semibold shadow-md transition mb-2 inline-block">
                        <i class="fas fa-prescription mr-2"></i>Add Prescription
                    </a>
                    <button onclick="editPatient()" 
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-edit mr-2"></i>Edit Patient
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Prescriptions</p>
                        <h3 class="text-3xl font-bold text-gray-900"><?= $rxCount ?></h3>
                    </div>
                    <div class="bg-green-100 p-4 rounded-lg">
                        <i class="fas fa-prescription text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Treatments</p>
                        <h3 class="text-3xl font-bold text-gray-900"><?= $treatmentCount ?></h3>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-lg">
                        <i class="fas fa-tooth text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Work Done</p>
                        <h3 class="text-3xl font-bold text-gray-900"><?= $workCount ?></h3>
                    </div>
                    <div class="bg-purple-100 p-4 rounded-lg">
                        <i class="fas fa-briefcase-medical text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user text-indigo-600 mr-3"></i>Personal Information
                </h2>
                <div class="space-y-3">
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Patient ID:</span>
                        <span class="text-gray-900"><?= $patient['patient_uid'] ?></span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Name:</span>
                        <span class="text-gray-900"><?= htmlspecialchars($patient['name']) ?></span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Age:</span>
                        <span class="text-gray-900"><?= $patient['age'] ?> years</span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Contact:</span>
                        <span class="text-gray-900"><?= htmlspecialchars($patient['contact_number']) ?></span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Address:</span>
                        <span class="text-gray-900"><?= htmlspecialchars($patient['address']) ?></span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Date of Visit:</span>
                        <span class="text-gray-900"><?= date('d M Y', strtotime($patient['date_of_visit'])) ?></span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Total Visits:</span>
                        <span class="text-gray-900"><?= $patient['total_visit'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-wallet text-green-600 mr-3"></i>Payment Information
                </h2>
                <div class="space-y-3">
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Total Amount:</span>
                        <span class="text-gray-900 font-bold">₹<?= number_format($patient['total_amount'], 2) ?></span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Paid Amount:</span>
                        <span class="text-green-600 font-bold">₹<?= number_format($patient['payment_received'], 2) ?></span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Pending:</span>
                        <span class="text-red-600 font-bold">₹<?= number_format($patient['payment_pending'], 2) ?></span>
                    </div>
                    <div class="flex border-b pb-2">
                        <span class="font-semibold text-gray-700 w-40">Payment Status:</span>
                        <span class="font-semibold <?= $patient['payment_status'] === 'paid' ? 'text-green-600' : 'text-orange-600' ?>">
                            <?= ucfirst($patient['payment_status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Medical History -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-notes-medical text-red-600 mr-3"></i>Medical History
                </h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Chief Complaint:</h3>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-lg"><?= htmlspecialchars($patient['chief_complain'] ?? 'Not provided') ?></p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Medical History:</h3>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-lg"><?= htmlspecialchars($patient['medical_history'] ?? 'Not provided') ?></p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Family History:</h3>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-lg"><?= htmlspecialchars($patient['family_history'] ?? 'Not provided') ?></p>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-3"></i>Additional Information
                </h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Oral & Diet Habits:</h3>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-lg"><?= htmlspecialchars($patient['oral_diet_habit'] ?? 'Not provided') ?></p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">X-Ray Remarks:</h3>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-lg"><?= htmlspecialchars($patient['xray_remark'] ?? 'Not provided') ?></p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Notes:</h3>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-lg"><?= htmlspecialchars($patient['notes'] ?? 'No notes') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white rounded-xl shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="<?= BASE_URL ?>/clinic/prescription/list.php?patient_uid=<?= urlencode($patient_uid) ?>"
                   class="bg-green-500 hover:bg-green-600 text-white px-6 py-4 rounded-lg font-semibold text-center transition">
                    <i class="fas fa-prescription text-2xl mb-2"></i>
                    <div>View Prescriptions</div>
                </a>
                <a href="<?= BASE_URL ?>/clinic/treatment/view.php?patient_uid=<?= urlencode($patient_uid) ?>"
                   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-4 rounded-lg font-semibold text-center transition">
                    <i class="fas fa-tooth text-2xl mb-2"></i>
                    <div>View Treatments</div>
                </a>
                <a href="<?= BASE_URL ?>/clinic/work_done/view.php?patient_uid=<?= urlencode($patient_uid) ?>"
                   class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-4 rounded-lg font-semibold text-center transition">
                    <i class="fas fa-briefcase-medical text-2xl mb-2"></i>
                    <div>View Work Done</div>
                </a>
                <button onclick="window.print()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-4 rounded-lg font-semibold text-center transition">
                    <i class="fas fa-print text-2xl mb-2"></i>
                    <div>Print Details</div>
                </button>
            </div>
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
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Edit Patient function
        function editPatient() {
            const patient = <?= json_encode($patient) ?>;
            
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

        // Edit Patient Form Submission
        document.getElementById('editPatientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('<?= BASE_URL ?>/api/patients/update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Patient updated successfully',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to update patient', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to update patient', 'error');
            });
        });
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
