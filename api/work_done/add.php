<?php
/**
 * API: Add Work Done Record
 * Location: api/work_done/add.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $patient_uid = $_POST['patient_uid'] ?? '';
    $work_done_id = $_POST['work_done_id'] ?? 0;
    $work_date = $_POST['work_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    $quantity = $_POST['quantity'] ?? 1;

    if (empty($patient_uid) || empty($work_done_id)) {
        echo json_encode(['success' => false, 'message' => 'Patient and Work Type are required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    
    // Get patient_id from patient_uid
    $stmt = $conn->prepare("SELECT id FROM patients WHERE patient_uid = ? AND clinic_id = ? LIMIT 1");
    $stmt->bind_param("si", $patient_uid, $clinicId);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    if (!$patient) {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }
    $patient_id = $patient['id'];
    
    // Get work cost from work_done table
    $stmt2 = $conn->prepare("SELECT cost FROM work_done WHERE id = ? AND clinic_id = ? LIMIT 1");
    $stmt2->bind_param("ii", $work_done_id, $clinicId);
    $stmt2->execute();
    $work = $stmt2->get_result()->fetch_assoc();
    if (!$work) {
        echo json_encode(['success' => false, 'message' => 'Work type not found']);
        exit;
    }
    $total_cost = $work['cost'] * $quantity;
    
    $stmt = $conn->prepare("INSERT INTO patient_work_done (clinic_id, patient_id, work_done_id, quantity, total_cost, work_date, notes, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiiidss", $clinicId, $patient_id, $work_done_id, $quantity, $total_cost, $work_date, $notes);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Work done record added successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add work record']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
