<?php
/**
 * API: Get Single Patient
 * Location: api/patients/get.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $patient_uid = $_GET['patient_uid'] ?? '';

    if (empty($patient_uid)) {
        echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_uid = ?");
    $stmt->bind_param("s", $patient_uid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }

    $patient = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'data' => $patient
    ]);

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
