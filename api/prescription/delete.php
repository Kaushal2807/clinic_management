<?php
/**
 * API: Delete Prescription
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('Invalid prescription ID');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Delete prescription medicines first
    $stmt = $conn->prepare("DELETE FROM prescription_medicines WHERE prescription_id = ? AND clinic_id = ?");
    $stmt->bind_param('ii', $id, $clinicId);
    $stmt->execute();
    
    // Delete prescription
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param('ii', $id, $clinicId);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Prescription deleted successfully']);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
