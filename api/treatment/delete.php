<?php
/**
 * API: Delete Treatment
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
        throw new Exception('Invalid treatment ID');
    }
    
    $stmt = $conn->prepare("DELETE FROM treatments WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param('ii', $id, $clinicId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Treatment deleted successfully']);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
