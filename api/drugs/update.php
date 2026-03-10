<?php
/**
 * API: Update Medicine
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    $id = (int)($_POST['id'] ?? 0);
    $drug_name = trim($_POST['medicine_name'] ?? '');
    
    if (!$id || empty($drug_name)) {
        throw new Exception('Invalid data');
    }
    
    $composition = trim($_POST['composition'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    $clinicId = ClinicContext::getClinicId();
    
    $sql = "UPDATE medicine SET medicine_name = ?, composition = ?, category = ?, quantity = ?, description = ? WHERE id = ? AND clinic_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssisii', $drug_name, $composition, $category, $quantity, $description, $id, $clinicId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Medicine updated successfully']);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
