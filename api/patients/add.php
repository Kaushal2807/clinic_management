<?php
/**
 * API: Add Patient
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    // Validate required fields
    $required = ['patient_uid', 'name', 'age', 'contact_number', 'date_of_visit'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field $field is required");
        }
    }
    
    // Prepare SQL
    $sql = "INSERT INTO patients (
        patient_uid, name, age, gender, contact_number, email, address, date_of_visit,
        total_visit, notes, total_amount, payment_status, payment_pending,
        chief_complain, medical_history, oral_diet_habit, family_history, xray_remark,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    // Get values
    $patient_uid = $_POST['patient_uid'];
    $name = $_POST['name'];
    $age = (int)$_POST['age'];
    $gender = $_POST['gender'] ?? null;
    $contact = $_POST['contact_number'];
    $email = $_POST['email'] ?? null;
    $address = $_POST['address'] ?? '';
    $date_visit = $_POST['date_of_visit'];
    $total_visit = (int)($_POST['total_visit'] ?? 1);
    $notes = $_POST['notes'] ?? '';
    $total_amount = (float)($_POST['total_amount'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? 'pending';
    $payment_pending = (float)($_POST['payment_pending'] ?? 0);
    $chief_complain = $_POST['chief_complain'] ?? '';
    $medical_history = $_POST['medical_history'] ?? '';
    $oral_diet = $_POST['oral_diet_habit'] ?? '';
    $family_history = $_POST['family_history'] ?? '';
    $xray = $_POST['xray_remark'] ?? '';
    
    $stmt->bind_param(
        'ssisssssisdsdsssss',
        $patient_uid, $name, $age, $gender, $contact, $email, $address, $date_visit,
        $total_visit, $notes, $total_amount, $payment_status, $payment_pending,
        $chief_complain, $medical_history, $oral_diet, $family_history, $xray
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Patient added successfully',
            'patient_uid' => $patient_uid
        ]);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
