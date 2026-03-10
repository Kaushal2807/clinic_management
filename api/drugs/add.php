<?php
/**
 * API: Add Medicine
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    $drug_name = trim($_POST['medicine_name'] ?? '');
    if (empty($drug_name)) {
        throw new Exception('Medicine name is required');
    }
    
    $composition = trim($_POST['composition'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    $clinicId = ClinicContext::getClinicId();
    
    $sql = "INSERT INTO medicine (clinic_id, medicine_name, composition, category, quantity, description, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isssis', $clinicId, $drug_name, $composition, $category, $quantity, $description);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Medicine added successfully', 'id' => $stmt->insert_id]);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
