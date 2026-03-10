<?php
/**
 * API: Add Work Type
 * Location: api/work_done/add_type.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $work_name = $_POST['work_name'] ?? '';
    $cost = $_POST['cost'] ?? 0;
    $description = $_POST['description'] ?? '';

    if (empty($work_name)) {
        echo json_encode(['success' => false, 'message' => 'Work type name is required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    $stmt = $conn->prepare("INSERT INTO work_types (clinic_id, work_name, cost, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $clinicId, $work_name, $cost, $description);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Work type added successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add work type']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
