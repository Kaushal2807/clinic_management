<?php
/**
 * API: Add Quantity to Medicine
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    $medicine_id = (int)($_POST['medicine_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    
    if (!$medicine_id || $quantity <= 0) {
        throw new Exception('Invalid data');
    }
    
    $sql = "UPDATE medicine SET quantity = quantity + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $quantity, $medicine_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Quantity added successfully']);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
