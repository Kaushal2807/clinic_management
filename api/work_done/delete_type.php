<?php
/**
 * API: Delete Work Type
 * Location: api/work_done/delete_type.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Work type ID is required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    $stmt = $conn->prepare("DELETE FROM work_types WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param("ii", $id, $clinicId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Work type deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete work type']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
