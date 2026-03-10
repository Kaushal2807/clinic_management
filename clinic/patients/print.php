<?php
/**
 * Print Patient Report as PDF
 * Location: clinic/patients/print.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/PDFHelper.php';

Auth::requireClinic();
ClinicContext::init();

$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();
$clinicId = ClinicContext::getClinicId();

if (!isset($_GET['patient_uid']) || empty($_GET['patient_uid'])) {
    die("Patient UID is required");
}

$patient_uid = $_GET['patient_uid'];

// Get patient details
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_uid = ? AND clinic_id = ?");
$stmt->bind_param("si", $patient_uid, $clinicId);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    die("Patient not found");
}

$patientId = $patient['id'];

// Get treatment history
$s = $conn->prepare("SELECT * FROM treatments WHERE clinic_id = ? AND patient_id = ? ORDER BY treatment_date DESC LIMIT 10");
$s->bind_param('ii', $clinicId, $patientId);
$s->execute();
$treatments = $s->get_result();

// Get work done history
$s = $conn->prepare("SELECT pwd.*, w.work_name FROM patient_work_done pwd JOIN work_done w ON pwd.work_done_id = w.id WHERE pwd.clinic_id = ? AND pwd.patient_id = ? ORDER BY pwd.work_date DESC LIMIT 10");
$s->bind_param('ii', $clinicId, $patientId);
$s->execute();
$workDone = $s->get_result();

// Get prescriptions
$s = $conn->prepare("SELECT * FROM prescriptions WHERE clinic_id = ? AND patient_id = ? ORDER BY created_at DESC LIMIT 5");
$s->bind_param('ii', $clinicId, $patientId);
$s->execute();
$prescriptions = $s->get_result();

// Create PDF
$pdf = new PDFHelper($clinic, 'P', 'mm', 'A4');
$pdf->SetTitle('Patient Report - ' . $patient['name']);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 10, 'PATIENT REPORT', 0, 1, 'C');
$pdf->Ln(3);

// Patient Information
$patientData = [
    'Name' => $patient['name'],
    'Patient ID' => $patient['patient_uid'],
    'Age/Gender' => $patient['age'] . ' years / ' . ($patient['gender'] ?? 'N/A'),
    'Contact' => $patient['contact_number'] ?? 'N/A',
    'Email' => $patient['email'] ?? 'N/A',
    'Address' => $patient['address'] ?? 'N/A',
    'Registration Date' => date('d M Y', strtotime($patient['created_at'])),
    'Total Visits' => $patient['total_visit'] ?? '0'
];
$pdf->addInfoBox('Patient Information', $patientData);

// Medical History
if (!empty($patient['medical_history'])) {
    $pdf->addSectionHeader('Medical History');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $patient['medical_history'], 0, 'L');
    $pdf->Ln(3);
}

// Chief Complaints
if (!empty($patient['chief_complain'])) {
    $pdf->addSectionHeader('Chief Complaints');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $patient['chief_complain'], 0, 'L');
    $pdf->Ln(3);
}

// Payment Summary
$pdf->addSectionHeader('Payment Summary', [34, 197, 94], [255, 255, 255]);
$paymentData = [
    'Total Amount' => '₹ ' . number_format($patient['total_amount'] ?? 0, 2),
    'Payment Status' => ucfirst($patient['payment_status'] ?? 'pending'),
    'Pending Amount' => '₹ ' . number_format($patient['payment_pending'] ?? 0, 2)
];
$pdf->addInfoBox('', $paymentData, [240, 255, 240]);

// Treatment History
if ($treatments && $treatments->num_rows > 0) {
    $pdf->AddPage();
    $pdf->addSectionHeader('Recent Treatments');
    
    $treatmentList = [];
    while ($t = $treatments->fetch_assoc()) {
        $treatmentList[] = [
            date('d/m/Y', strtotime($t['treatment_date'])),
            $t['treatment_name'],
            '₹ ' . number_format($t['cost'] ?? 0, 2),
            ucfirst($t['status'] ?? 'N/A')
        ];
    }
    
    $pdf->addTable(
        ['Date', 'Treatment', 'Cost', 'Status'],
        $treatmentList,
        [30, 80, 35, 35]
    );
}

// Work Done History
if ($workDone && $workDone->num_rows > 0) {
    $pdf->addSectionHeader('Recent Work Done');
    
    $workList = [];
    while ($w = $workDone->fetch_assoc()) {
        $workList[] = [
            date('d/m/Y', strtotime($w['work_date'])),
            $w['work_name'],
            $w['tooth_number'] ?? 'N/A',
            '₹ ' . number_format($w['cost'] ?? 0, 2)
        ];
    }
    
    $pdf->addTable(
        ['Date', 'Work', 'Tooth', 'Cost'],
        $workList,
        [30, 70, 30, 50]
    );
}

// Prescription History
if ($prescriptions && $prescriptions->num_rows > 0) {
    $pdf->addSectionHeader('Recent Prescriptions');
    
    $rxList = [];
    while ($rx = $prescriptions->fetch_assoc()) {
        $rxList[] = [
            'RX-' . str_pad($rx['id'], 5, '0', STR_PAD_LEFT),
            date('d/m/Y', strtotime($rx['created_at'])),
            substr($rx['notes'] ?? 'N/A', 0, 50)
        ];
    }
    
    $pdf->addTable(
        ['Prescription ID', 'Date', 'Notes'],
        $rxList,
        [35, 30, 115]
    );
}

// Additional Notes
if (!empty($patient['notes'])) {
    $pdf->addSectionHeader('Additional Notes');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $patient['notes'], 0, 'L');
}

// Output PDF
$pdf->Output('Patient_Report_' . $patient['patient_uid'] . '.pdf', 'I');
