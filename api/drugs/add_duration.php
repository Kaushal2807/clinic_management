<?php
/**
 * API: Add Duration
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    $duration_value = trim($_POST['duration_value'] ?? '');
    if (empty($duration_value)) {
        throw new Exception('Duration value is required');
    }
    
    $sql = "INSERT INTO durations (duration_value, created_at) VALUES (?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $duration_value);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Duration added successfully']);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
