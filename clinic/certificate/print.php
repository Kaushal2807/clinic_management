<?php
/**
 * Print Certificate as PDF
 * Location: clinic/certificate/print.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/PDFHelper.php';

Auth::requireClinic();
ClinicContext::init();

$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Certificate ID is required");
}

$certId = (int)$_GET['id'];

// Get certificate details
$stmt = $conn->prepare("SELECT * FROM certificates WHERE id = ?");
$stmt->bind_param("i", $certId);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc();

if (!$cert) {
    die("Certificate not found");
}

// Create PDF
$pdf = new PDFHelper($clinic, 'P', 'mm', 'A4');
$pdf->SetTitle('Medical Certificate');
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 10, 'MEDICAL CERTIFICATE', 0, 1, 'C');
$pdf->Ln(3);

// Certificate Number and Date
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(100, 6, 'Certificate No: CERT-' . str_pad($certId, 4, '0', STR_PAD_LEFT), 0, 0, 'L');
$pdf->Cell(0, 6, 'Date: ' . date('d M Y', strtotime($cert['certificate_date'])), 0, 1, 'R');
$pdf->Ln(5);

// Patient Information
$patientData = [
    'Patient Name' => $cert['patient_name'],
    'Patient ID' => $cert['patient_uid']
];
$pdf->addInfoBox('Patient Information', $patientData);

// Complaints
$pdf->addSectionHeader('Chief Complaints');
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 5, $cert['complaints'], 0, 'L');
$pdf->Ln(5);

// Treatment
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 8, 'Treatment Done', 0, 1);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 6, $cert['treatment_done'], 0, 'L');
$pdf->Ln(5);

// Advice
if ($cert['advise']) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(67, 56, 202);
    $pdf->Cell(0, 8, 'Medical Advice', 0, 1);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(0, 0, 0);
$pdf->addSectionHeader('Treatment Given');
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 5, $cert['treatment'], 0, 'L');
$pdf->Ln(5);

// Advice (if exists)
if (!empty($cert['advise'])) {
    $pdf->addSectionHeader('Advice', [240, 255, 240], [34, 139, 34]);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $cert['advise'], 0, 'L');
    $pdf->Ln(5);
}

// Rest Period (if exists)
if (!empty($cert['rest_days'])) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Recommended Rest: ' . $cert['rest_days'] . ' days', 0, 1);
    $pdf->Ln(3);
}

// Signature
$pdf->addSignatureSection();

// Footer note
$pdf->SetY(-25);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'This is a computer-generated medical certificate', 0, 1, 'C');

// Output PDF
$pdf->Output('Medical_Certificate_' . str_pad($certId, 4, '0', STR_PAD_LEFT) . '.pdf', 'I');
