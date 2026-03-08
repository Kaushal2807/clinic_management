<?php
/**
 * API: Add Dose
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    $dose_name = trim($_POST['dose_name'] ?? '');
    if (empty($dose_name)) {
        throw new Exception('Dose name is required');
    }
    
    $sql = "INSERT INTO doses (dose_name, created_at) VALUES (?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $dose_name);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Dose added successfully']);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
