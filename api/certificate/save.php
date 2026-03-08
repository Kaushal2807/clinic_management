<?php
/**
 * API: Save Certificate
 * Location: api/certificate/save.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $patient_uid = $_POST['patient_uid'] ?? '';
    $patient_name = $_POST['patient_name'] ?? '';
    $certificate_date = $_POST['certificate_date'] ?? date('Y-m-d');
    $complaints = $_POST['complaints'] ?? '';
    $treatment_done = $_POST['treatment_done'] ?? '';
    $advise = $_POST['advise'] ?? '';

    if (empty($patient_uid) || empty($patient_name) || empty($complaints) || empty($treatment_done)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    
    // Get patient_id from patient_uid
    $patient_query = $conn->prepare("SELECT id FROM patients WHERE patient_uid = ?");
    $patient_query->bind_param('s', $patient_uid);
    $patient_query->execute();
    $patient_result = $patient_query->get_result();
    $patient_data = $patient_result->fetch_assoc();
    
    if (!$patient_data) {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }
    
    $patient_id = $patient_data['id'];
    
    // Certificates table already created with patient_id
    $stmt = $conn->prepare("INSERT INTO certificates 
        (patient_id, patient_uid, certificate_no, certificate_date, complaints, treatment_given, advice, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $certificate_no = 'CERT-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $stmt->bind_param("issssss", 
        $patient_id,
        $patient_uid, 
        $certificate_no,
        $certificate_date, 
        $complaints, 
        $treatment_done, 
        $advise
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Certificate generated successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to generate certificate']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
