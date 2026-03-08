<?php
/**
 * Treatment Management - Main Page
 * Location: clinic/treatment/index.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Treatment Management';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Get patient UID if provided
$patient_uid = $_GET['patient_uid'] ?? '';

// Get all patients
$patients = $conn->query("SELECT patient_uid, name FROM patients ORDER BY name");

// Get treatment categories
$categories = $conn->query("SELECT * FROM treatment_categories ORDER BY category_name");

// If patient_uid provided, get patient details
$patient = null;
if ($patient_uid) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_uid = ?");
    $stmt->bind_param('s', $patient_uid);
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
                <i class="fas fa-tooth text-blue-600 mr-3"></i>Treatment Management
            </h1>
            <p class="text-gray-600">Create and manage patient treatment plans</p>
        </div>

        <!-- Treatment Form -->
        <div class="bg-white rounded-xl shadow-md p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                <i class="fas fa-teeth-open mr-2 text-blue-600"></i>New Treatment Plan
            </h2>

            <form id="treatmentForm" class="space-y-6">
                
                <!-- Patient Selection -->
                <div class="bg-indigo-50 rounded-xl p-6">
                    <h3 class="font-semibold text-indigo-900 mb-4">Patient Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Select Patient *</label>
                            <select name="patient_uid" id="patient_uid" required onchange="loadPatientInfo(this.value)"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
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
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Treatment Date *</label>
                            <input type="date" name="treatment_date" value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
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

                <!-- Tooth Selection Chart -->
                <div class="bg-blue-50 rounded-xl p-6">
                    <h3 class="font-semibold text-blue-900 mb-4">Tooth Selection Chart (Universal Numbering - 32 Adult Teeth)</h3>
                    
                    <!-- Upper Jaw -->
                    <div class="mb-6">
                        <p class="text-xs text-center font-semibold text-gray-600 mb-3">UPPER JAW (Maxillary)</p>
                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <!-- Upper Right Quadrant -->
                            <div>
                                <p class="text-xs font-semibold text-center text-blue-700 mb-2">Upper Right (1-8)</p>
                                <div class="grid grid-cols-8 gap-1">
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <button type="button" onclick="toggleTooth(<?= $i ?>)" 
                                            class="tooth-btn bg-white hover:bg-blue-100 border-2 border-gray-300 rounded-lg p-2 text-center transition cursor-pointer"
                                            id="tooth-<?= $i ?>">
                                        <div class="text-sm font-bold"><?= $i ?></div>
                                        <i class="fas fa-tooth text-gray-400 text-xs"></i>
                                    </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <!-- Upper Left Quadrant -->
                            <div>
                                <p class="text-xs font-semibold text-center text-blue-700 mb-2">Upper Left (9-16)</p>
                                <div class="grid grid-cols-8 gap-1">
                                    <?php for ($i = 9; $i <= 16; $i++): ?>
                                    <button type="button" onclick="toggleTooth(<?= $i ?>)" 
                                            class="tooth-btn bg-white hover:bg-blue-100 border-2 border-gray-300 rounded-lg p-2 text-center transition cursor-pointer"
                                            id="tooth-<?= $i ?>">
                                        <div class="text-sm font-bold"><?= $i ?></div>
                                        <i class="fas fa-tooth text-gray-400 text-xs"></i>
                                    </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lower Jaw -->
                    <div class="border-t-2 border-gray-400 pt-6">
                        <p class="text-xs text-center font-semibold text-gray-600 mb-3">LOWER JAW (Mandibular)</p>
                        <div class="grid grid-cols-2 gap-6">
                            <!-- Lower Left Quadrant -->
                            <div>
                                <p class="text-xs font-semibold text-center text-blue-700 mb-2">Lower Left (17-24)</p>
                                <div class="grid grid-cols-8 gap-1">
                                    <?php for ($i = 17; $i <= 24; $i++): ?>
                                    <button type="button" onclick="toggleTooth(<?= $i ?>)" 
                                            class="tooth-btn bg-white hover:bg-blue-100 border-2 border-gray-300 rounded-lg p-2 text-center transition cursor-pointer"
                                            id="tooth-<?= $i ?>">
                                        <div class="text-sm font-bold"><?= $i ?></div>
                                        <i class="fas fa-tooth text-gray-400 text-xs"></i>
                                    </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <!-- Lower Right Quadrant -->
                            <div>
                                <p class="text-xs font-semibold text-center text-blue-700 mb-2">Lower Right (25-32)</p>
                                <div class="grid grid-cols-8 gap-1">
                                    <?php for ($i = 25; $i <= 32; $i++): ?>
                                    <button type="button" onclick="toggleTooth(<?= $i ?>)" 
                                            class="tooth-btn bg-white hover:bg-blue-100 border-2 border-gray-300 rounded-lg p-2 text-center transition cursor-pointer"
                                            id="tooth-<?= $i ?>">
                                        <div class="text-sm font-bold"><?= $i ?></div>
                                        <i class="fas fa-tooth text-gray-400 text-xs"></i>
                                    </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Click on teeth to select. Selected teeth will be highlighted in blue.
                        </p>
                    </div>

                    <input type="hidden" name="selected_teeth" id="selected_teeth" value="">
                    <div id="selected_teeth_display" class="mt-4"></div>
                </div>

                <!-- Treatment Details -->
                <div class="bg-green-50 rounded-xl p-6">
                    <h3 class="font-semibold text-green-900 mb-4">Treatment Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Treatment Category *</label>
                            <select name="category" required
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($cat = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?= htmlspecialchars($cat['category_name']) ?>">
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Treatment Name *</label>
                            <input type="text" name="treatment_name" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                   placeholder="e.g., Root Canal, Filling, Extraction">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Treatment Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                  placeholder="Detailed treatment plan and procedures"></textarea>
                    </div>
                </div>

                <!-- Cost Details -->
                <div class="bg-yellow-50 rounded-xl p-6">
                    <h3 class="font-semibold text-yellow-900 mb-4">Cost Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Treatment Cost (₹) *</label>
                            <input type="number" name="cost" step="0.01" required value="0"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                            <select name="status"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500">
                                <option value="planned">Planned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Next Visit Date</label>
                            <input type="date" name="next_visit"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-purple-50 rounded-xl p-6">
                    <h3 class="font-semibold text-purple-900 mb-4">Additional Notes</h3>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                              placeholder="Any special instructions, precautions, or observations"></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4">
                    <button type="submit" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold shadow-md transition">
                        <i class="fas fa-save mr-2"></i>Save Treatment Plan
                    </button>
                    <button type="button" onclick="window.location.href='list.php'"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-list mr-2"></i>View All Treatments
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedTeeth = new Set();

        function toggleTooth(toothNumber) {
            const toothBtn = document.getElementById('tooth-' + toothNumber);
            
            if (selectedTeeth.has(toothNumber)) {
                selectedTeeth.delete(toothNumber);
                toothBtn.classList.remove('bg-blue-500', 'text-white', 'border-blue-600');
                toothBtn.classList.add('bg-white', 'border-gray-300');
                toothBtn.querySelector('i').classList.remove('text-white');
                toothBtn.querySelector('i').classList.add('text-gray-400');
            } else {
                selectedTeeth.add(toothNumber);
                toothBtn.classList.remove('bg-white', 'border-gray-300');
                toothBtn.classList.add('bg-blue-500', 'text-white', 'border-blue-600');
                toothBtn.querySelector('i').classList.remove('text-gray-400');
                toothBtn.querySelector('i').classList.add('text-white');
            }
            
            updateSelectedTeethField();
        }

        function updateSelectedTeethField() {
            const teethArray = Array.from(selectedTeeth).sort((a, b) => a - b);
            document.getElementById('selected_teeth').value = teethArray.join(',');
            
            if (teethArray.length > 0) {
                document.getElementById('selected_teeth_display').innerHTML = `
                    <div class="flex items-center gap-2 p-3 bg-white border border-blue-200 rounded-lg">
                        <span class="font-semibold text-blue-900">Selected Teeth:</span>
                        <div class="flex flex-wrap gap-2">
                            ${teethArray.map(t => `<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm font-semibold">${t}</span>`).join('')}
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('selected_teeth_display').innerHTML = '';
            }
        }

        function loadPatientInfo(patientUid) {
            if (patientUid) {
                window.location.href = '?patient_uid=' + encodeURIComponent(patientUid);
            }
        }

        // Form submission
        document.getElementById('treatmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            if (selectedTeeth.size === 0) {
                Swal.fire('Warning', 'Please select at least one tooth', 'warning');
                return;
            }
            
            fetch('../../api/treatment/add.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Treatment plan saved successfully',
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'An error occurred while saving treatment', 'error');
            });
        });
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
