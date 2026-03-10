<?php
/**
 * API: Update Work Type
 * Location: api/work_done/update_type.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? 0;
    $work_name = $_POST['work_name'] ?? '';
    $cost = $_POST['cost'] ?? 0;
    $description = $_POST['description'] ?? '';

    if (empty($id) || empty($work_name)) {
        echo json_encode(['success' => false, 'message' => 'ID and work type name are required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    $stmt = $conn->prepare("UPDATE work_types SET work_name = ?, cost = ?, description = ? WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param("sdsii", $work_name, $cost, $description, $id, $clinicId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Work type updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update work type']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
