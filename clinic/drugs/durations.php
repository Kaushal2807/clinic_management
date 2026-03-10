<?php
/**
 * Durations Management Page
 * Location: clinic/drugs/durations.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'Manage Durations';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();
$clinicId = ClinicContext::getClinicId();

// Fetch all durations
$durations = $conn->query("SELECT * FROM durations WHERE clinic_id = $clinicId ORDER BY id ASC");

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-clock text-purple-600 mr-3"></i>Manage Durations
                    </h1>
                    <p class="text-gray-600">Add and manage medicine duration options</p>
                </div>
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Medicines
                </a>
            </div>
        </div>

        <!-- Add Duration Form -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-plus-circle text-purple-600 mr-2"></i>Add New Duration
            </h2>
            <form id="addDurationForm" class="flex gap-3">
                <input type="text" 
                       name="duration_value" 
                       placeholder="e.g., 5 Days, 10 Days, 1 Week" 
                       required
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <button type="submit" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-semibold shadow-md transition">
                    <i class="fas fa-plus mr-2"></i>Add
                </button>
            </form>
        </div>

        <!-- Durations List -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-list text-purple-600 mr-2"></i>Existing Durations
            </h2>

            <?php if ($durations->num_rows === 0): ?>
                <div class="text-center py-12">
                    <i class="fas fa-clock text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-600">No durations added yet</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php while ($duration = $durations->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-alt text-purple-600 mr-3"></i>
                                <span class="font-medium text-gray-900"><?= htmlspecialchars($duration['duration_value']) ?></span>
                            </div>
                            <button onclick="deleteDuration(<?= $duration['id'] ?>, '<?= htmlspecialchars($duration['duration_value']) ?>')" 
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
// Add Duration
document.getElementById('addDurationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../../api/drugs/add_duration.php', {
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
        Swal.fire('Error!', 'Failed to add duration', 'error');
    }
});

// Delete Duration
async function deleteDuration(id, value) {
    const result = await Swal.fire({
        title: 'Delete Duration?',
        text: `Are you sure you want to delete "${value}"?`,
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
            
            const response = await fetch('../../api/drugs/delete_duration.php', {
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
            Swal.fire('Error!', 'Failed to delete duration', 'error');
        }
    }
}
</script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
