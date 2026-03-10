<?php
/**
 * Doses Management Page
 * Location: clinic/drugs/doses.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Manage Doses';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();
$clinicId = ClinicContext::getClinicId();

// Fetch all doses
$doses = $conn->query("SELECT * FROM doses WHERE clinic_id = $clinicId ORDER BY dose_name ASC");

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-syringe text-blue-600 mr-3"></i>Manage Doses
                    </h1>
                    <p class="text-gray-600">Add and manage medicine dosage options</p>
                </div>
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Medicines
                </a>
            </div>
        </div>

        <!-- Add Dose Form -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Add New Dose
            </h2>
            <form id="addDoseForm" class="flex gap-3">
                <input type="text" 
                       name="dose_name" 
                       placeholder="e.g., 1 Tab, 2 Tabs, 1 Teaspoon" 
                       required
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold shadow-md transition">
                    <i class="fas fa-plus mr-2"></i>Add
                </button>
            </form>
        </div>

        <!-- Doses List -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-list text-blue-600 mr-2"></i>Existing Doses
            </h2>

            <?php if ($doses->num_rows === 0): ?>
                <div class="text-center py-12">
                    <i class="fas fa-syringe text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-600">No doses added yet</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php while ($dose = $doses->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center">
                                <i class="fas fa-pills text-blue-600 mr-3"></i>
                                <span class="font-medium text-gray-900"><?= htmlspecialchars($dose['dose_name']) ?></span>
                            </div>
                            <button onclick="deleteDose(<?= $dose['id'] ?>, '<?= htmlspecialchars($dose['dose_name']) ?>')" 
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

<script>
// Add Dose
document.getElementById('addDoseForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../../api/drugs/add_dose.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire('Success!', data.message, 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error!', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error!', 'Failed to add dose', 'error');
    }
});

// Delete Dose
async function deleteDose(id, name) {
    const result = await Swal.fire({
        title: 'Delete Dose?',
        text: `Are you sure you want to delete "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!'
    });
    
    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            
            const response = await fetch('../../api/drugs/delete_dose.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire('Deleted!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'Failed to delete dose', 'error');
        }
    }
}
</script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
