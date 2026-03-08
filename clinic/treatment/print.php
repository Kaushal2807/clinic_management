<?php
/**
 * Print Treatment Details as PDF
 * Location: clinic/treatment/print.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/PDFHelper.php';

Auth::requireClinic();
ClinicContext::init();

$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

if (!isset($_GET['patient_uid']) || empty($_GET['patient_uid'])) {
    die("Patient UID is required");
}

$patient_uid = $_GET['patient_uid'];

// Get patient details
$patient = $conn->query("SELECT * FROM patients WHERE patient_uid = '$patient_uid' LIMIT 1")->fetch_assoc();

if (!$patient) {
    die("Patient not found");
}

// Get treatments
$treatments = $conn->query("SELECT * FROM treatments WHERE patient_id = (SELECT id FROM patients WHERE patient_uid = '$patient_uid') ORDER BY treatment_date DESC");

// Create PDF
$pdf = new PDFHelper($clinic, 'P', 'mm', 'A4');
$pdf->SetTitle('Treatment Plan - ' . $patient['name']);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 10, 'TREATMENT PLAN', 0, 1, 'C');
$pdf->Ln(3);

// Patient Information
$patientData = [
    'Name' => $patient['name'],
    'Patient ID' => $patient['patient_uid'],
    'Age/Gender' => $patient['age'] . ' years / ' . ($patient['gender'] ?? 'N/A'),
    'Contact' => $patient['contact_number'] ?? 'N/A',
    'Date' => date('d M Y')
];
$pdf->addInfoBox('Patient Information', $patientData);

// Treatments
if ($treatments && $treatments->num_rows > 0) {
    $pdf->addSectionHeader('Treatment Details');
    
    $totalCost = 0;
    $counter = 1;
    
    while ($t = $treatments->fetch_assoc()) {
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $counter . '. ' . $t['treatment_name'], 0, 1, 'L', true);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 5, 'Date:', 0, 0);
        $pdf->Cell(60, 5, date('d M Y', strtotime($t['treatment_date'])), 0, 0);
        $pdf->Cell(30, 5, 'Cost:', 0, 0);
        $pdf->Cell(50, 5, '₹ ' . number_format($t['cost'] ?? 0, 2), 0, 1);
        
        $pdf->Cell(40, 5, 'Status:', 0, 0);
        $pdf->Cell(0, 5, ucfirst($t['status'] ?? 'planned'), 0, 1);
        
        if (!empty($t['notes'])) {
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->MultiCell(0, 4, 'Notes: ' . $t['notes'], 0, 'L');
        }
        
        $pdf->Ln(3);
        $totalCost += $t['cost'] ?? 0;
        $counter++;
    }
    
    // Total Cost
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(67, 56, 202);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(130, 8, 'TOTAL TREATMENT COST', 1, 0, 'R', true);
    $pdf->Cell(0, 8, '₹ ' . number_format($totalCost, 2), 1, 1, 'R', true);
} else {
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'No treatments found for this patient', 0, 1);
}

// Signature
$pdf->addSignatureSection();

// Output PDF
$pdf->Output('Treatment_Plan_' . $patient['patient_uid'] . '.pdf', 'I');
