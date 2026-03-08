<?php
/**
 * API: Delete Work Done Record
 * Location: api/work_done/delete.php
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
        echo json_encode(['success' => false, 'message' => 'Work Done ID is required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $stmt = $conn->prepare("DELETE FROM patient_work_done WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Work record deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete work record']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
