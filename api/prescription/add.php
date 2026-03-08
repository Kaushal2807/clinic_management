<?php
/**
 * API: Add Prescription
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    $patient_uid = $_POST['patient_uid'] ?? '';
    $prescription_date = $_POST['prescription_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    $medicines = $_POST['medicines'] ?? [];
    
    if (empty($patient_uid)) {
        throw new Exception('Patient is required');
    }
    
    if (empty($medicines)) {
        throw new Exception('At least one medicine is required');
    }
    
    // Get patient_id from patient_uid
    $patient_query = $conn->prepare("SELECT id FROM patients WHERE patient_uid = ?");
    $patient_query->bind_param('s', $patient_uid);
    $patient_query->execute();
    $patient_result = $patient_query->get_result();
    $patient_data = $patient_result->fetch_assoc();
    
    if (!$patient_data) {
        throw new Exception('Patient not found');
    }
    
    $patient_id = $patient_data['id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert prescription
    $sql = "INSERT INTO prescriptions (patient_id, prescription_date, notes, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $patient_id, $prescription_date, $notes);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create prescription');
    }
    
    $prescription_id = $stmt->insert_id;
    
    // Insert medicines
    $sql = "INSERT INTO prescription_medicines (prescription_id, medicine_id, dose, duration, instructions) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($medicines as $medicine) {
        if (empty($medicine['medicine_id']) || empty($medicine['dose']) || empty($medicine['duration'])) {
            continue;
        }
        
        $medicine_id = (int)$medicine['medicine_id'];
        $dose = $medicine['dose'];
        $duration = $medicine['duration'];
        $instructions = $medicine['instructions'] ?? '';
        
        $stmt->bind_param('iisss', $prescription_id, $medicine_id, $dose, $duration, $instructions);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add medicine');
        }
        
        // Update medicine quantity (reduce by 1)
        $conn->query("UPDATE medicine SET quantity = GREATEST(quantity - 1, 0) WHERE id = $medicine_id");
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Prescription saved successfully',
        'prescription_id' => $prescription_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
