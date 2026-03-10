<?php
/**
 * API: Add Treatment
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    
    $patient_uid = $_POST['patient_uid'] ?? '';
    $selected_teeth = $_POST['selected_teeth'] ?? '';
    $treatment_date = $_POST['treatment_date'] ?? date('Y-m-d');
    $category = $_POST['category'] ?? '';
    $treatment_name = $_POST['treatment_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $cost = (float)($_POST['cost'] ?? 0);
    $status = $_POST['status'] ?? 'planned';
    $next_visit = !empty($_POST['next_visit']) ? $_POST['next_visit'] : null;
    $notes = $_POST['notes'] ?? '';
    
    if (empty($patient_uid) || empty($treatment_name) || empty($selected_teeth)) {
        throw new Exception('Required fields are missing');
    }
    
    // Get patient_id from patient_uid
    $patient_query = $conn->prepare("SELECT id FROM patients WHERE patient_uid = ? AND clinic_id = ?");
    $patient_query->bind_param('si', $patient_uid, $clinicId);
    $patient_query->execute();
    $patient_result = $patient_query->get_result();
    $patient_data = $patient_result->fetch_assoc();
    
    if (!$patient_data) {
        throw new Exception('Patient not found');
    }
    
    $patient_id = $patient_data['id'];
    
    $sql = "INSERT INTO treatments (
        clinic_id, patient_id, selected_teeth, treatment_name, category, description,
        treatment_date, cost, status, next_visit, notes, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'iisssssdsss',
        $clinicId, $patient_id, $selected_teeth, $treatment_name, $category, $description,
        $treatment_date, $cost, $status, $next_visit, $notes
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Treatment plan saved successfully',
            'treatment_id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
