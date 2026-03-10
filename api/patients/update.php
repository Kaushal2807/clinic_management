<?php
/**
 * API: Update Patient
 * Location: api/patients/update.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $patient_uid = $_POST['patient_uid'] ?? '';
    $name = $_POST['name'] ?? '';
    $age = $_POST['age'] ?? '';
    $gender = $_POST['gender'] ?? null;
    $contact_number = $_POST['contact_number'] ?? '';
    $email = $_POST['email'] ?? null;
    $address = $_POST['address'] ?? '';
    $date_of_visit = $_POST['date_of_visit'] ?? date('Y-m-d');
    $blood_group = $_POST['blood_group'] ?? null;
    $total_visit = $_POST['total_visit'] ?? 1;
    $notes = $_POST['notes'] ?? '';
    $total_amount = $_POST['total_amount'] ?? 0;
    $payment_status = $_POST['payment_status'] ?? 'pending';
    $payment_pending = $_POST['payment_pending'] ?? 0;
    $chief_complain = $_POST['chief_complain'] ?? '';
    $medical_history = $_POST['medical_history'] ?? '';
    $oral_diet_habit = $_POST['oral_diet_habit'] ?? '';
    $family_history = $_POST['family_history'] ?? '';
    $xray_remark = $_POST['xray_remark'] ?? '';

    if (empty($patient_uid) || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Patient ID and name are required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    
    $stmt = $conn->prepare("UPDATE patients SET 
        name = ?, 
        age = ?, 
        gender = ?,
        contact_number = ?, 
        email = ?,
        address = ?, 
        date_of_visit = ?, 
        blood_group = ?,
        total_visit = ?, 
        notes = ?, 
        total_amount = ?, 
        payment_status = ?, 
        payment_pending = ?,
        chief_complain = ?, 
        medical_history = ?, 
        oral_diet_habit = ?, 
        family_history = ?, 
        xray_remark = ?
        WHERE patient_uid = ? AND clinic_id = ?");
    
    $stmt->bind_param(
        "sisssssssissdsssssi",
        $name,
        $age,
        $gender,
        $contact_number,
        $email,
        $address,
        $date_of_visit,
        $blood_group,
        $total_visit,
        $notes,
        $total_amount,
        $payment_status,
        $payment_pending,
        $chief_complain,
        $medical_history,
        $oral_diet_habit,
        $family_history,
        $xray_remark,
        $patient_uid,
        $clinicId
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Patient updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update patient']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
