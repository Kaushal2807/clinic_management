<?php
/**
 * API: Delete Medicine
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('Invalid medicine ID');
    }
    
    $clinicId = ClinicContext::getClinicId();
    
    $sql = "DELETE FROM medicine WHERE id = ? AND clinic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $id, $clinicId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Medicine deleted successfully']);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
