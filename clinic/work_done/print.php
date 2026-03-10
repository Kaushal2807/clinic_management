<?php
/**
 * Print Work Done Invoice as PDF
 * Location: clinic/work_done/print.php
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
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_uid = ? AND clinic_id = ? LIMIT 1");
$stmt->bind_param("si", $patient_uid, $clinicId);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    die("Patient not found");
}

$patientId = $patient['id'];

// Get work done
$stmt2 = $conn->prepare("SELECT pwd.*, w.work_name FROM patient_work_done pwd JOIN work_done w ON pwd.work_done_id = w.id WHERE pwd.clinic_id = ? AND pwd.patient_id = ? ORDER BY pwd.work_date DESC");
$stmt2->bind_param("ii", $clinicId, $patientId);
$stmt2->execute();
$workDone = $stmt2->get_result();

// Create PDF
$pdf = new PDFHelper($clinic, 'P', 'mm', 'A4');
$pdf->SetTitle('Invoice - ' . $patient['name']);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 10, 'DENTAL WORK INVOICE', 0, 1, 'C');
$pdf->Ln(3);

// Invoice Number and Date
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(100, 6, 'Invoice No: INV-' . str_pad($patient['id'], 5, '0', STR_PAD_LEFT), 0, 0, 'L');
$pdf->Cell(0, 6, 'Date: ' . date('d M Y'), 0, 1, 'R');
$pdf->Ln(3);

// Patient Information
$patientData = [
    'Name' => $patient['name'],
    'Patient ID' => $patient['patient_uid'],
    'Contact' => $patient['contact_number'] ?? 'N/A',
    'Address' => $patient['address'] ?? 'N/A'
];
$pdf->addInfoBox('Bill To', $patientData);

// Work Done Table
if ($workDone && $workDone->num_rows > 0) {
    $pdf->addSectionHeader('Work Done Details');
    
    $workList = [];
    $totalCost = 0;
    $counter = 1;
    
    while ($w = $workDone->fetch_assoc()) {
        $cost = $w['cost'] ?? 0;
        $workList[] = [
            $counter++,
            date('d/m/Y', strtotime($w['work_date'])),
            $w['work_name'],
            $w['tooth_number'] ?? '-',
            '₹ ' . number_format($cost, 2)
        ];
        $totalCost += $cost;
    }
    
    $pdf->addTable(
        ['#', 'Date', 'Description', 'Tooth', 'Amount'],
        $workList,
        [10, 25, 80, 20, 45]
    );
    
    // Summary
    $pdf->Ln(5);
    
    // Subtotal
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(135, 6, 'Subtotal:', 0, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, '₹ ' . number_format($totalCost, 2), 0, 1, 'R');
    
    // Tax (if applicable)
    $tax = 0; // Add tax calculation if needed
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(135, 6, 'Tax (0%):', 0, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, '₹ ' . number_format($tax, 2), 0, 1, 'R');
    
 // Divider
    $pdf->Line(135, $pdf->GetY(), $pdf->getPageWidth() - 15, $pdf->GetY());
    $pdf->Ln(2);
    
    // Total
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(67, 56, 202);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(135, 8, 'TOTAL AMOUNT:', 1, 0, 'R', true);
    $pdf->Cell(0, 8, '₹ ' . number_format($totalCost + $tax, 2), 1, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);
    
    // Payment Status
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(40, 6, 'Payment Status:', 0, 0);
    
    $status = $patient['payment_status'] ?? 'pending';
    $statusColor = $status == 'paid' ? [34, 197, 94] : ($status == 'partial' ? [251, 191, 36] : [239, 68, 68]);
    $pdf->SetTextColor($statusColor[0], $statusColor[1], $statusColor[2]);
    $pdf->Cell(0, 6, strtoupper($status), 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    
    if (($patient['payment_pending'] ?? 0) > 0) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'Pending Amount:', 0, 0);
        $pdf->SetTextColor(239, 68, 68);
        $pdf->Cell(0, 6, '₹ ' . number_format($patient['payment_pending'], 2), 0, 1);
        $pdf->SetTextColor(0, 0, 0);
    }
} else {
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'No work done records found for this patient', 0, 1);
}

// Terms and Conditions
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'Terms & Conditions:', 0, 1);
$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(0, 4, "1. Payment is due within 30 days of invoice date.\n2. Please retain this invoice for your records.\n3. For any queries, please contact us at " . ($clinic['contact_phone'] ?? 'N/A'), 0, 'L');

// Thank you note
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 6, 'Thank you for choosing ' . $clinic['clinic_name'] . '!', 0, 1, 'C');

// Output PDF
$pdf->Output('Invoice_' . $patient['patient_uid'] . '.pdf', 'I');
