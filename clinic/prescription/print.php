<?php
/**
 * Print Prescription as PDF
 * Location: clinic/prescription/print.php
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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Prescription ID is required");
}

$prescriptionId = (int)$_GET['id'];

// Get prescription details with patient info
$stmt = $conn->prepare("SELECT p.*, pt.name, pt.age, pt.gender, pt.contact_number, pt.address, pt.patient_uid
                        FROM prescriptions p
                        JOIN patients pt ON p.patient_id = pt.id
                        WHERE p.id = ? AND p.clinic_id = ?");
$stmt->bind_param("ii", $prescriptionId, $clinicId);
$stmt->execute();
$prescription = $stmt->get_result()->fetch_assoc();

if (!$prescription) {
    die("Prescription not found");
}

// Create PDF
$pdf = new PDFHelper($clinic, 'P', 'mm', 'A4');
$pdf->SetTitle('Prescription - RX' . str_pad($prescriptionId, 5, '0', STR_PAD_LEFT));
$pdf->AddPage();

// Prescription Number and Date
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 8, 'PRESCRIPTION', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(100, 6, 'RX-' . str_pad($prescriptionId, 5, '0', STR_PAD_LEFT), 0, 0, 'L');
$pdf->Cell(0, 6, 'Date: ' . date('d M Y', strtotime($prescription['prescription_date'] ?? $prescription['created_at'])), 0, 1, 'R');
$pdf->Ln(3);

// Patient Info
$patientData = [
    'Name' => $prescription['name'],
    'Age/Gender' => $prescription['age'] . ' years / ' . ($prescription['gender'] ?? 'N/A'),
    'Patient ID' => $prescription['patient_uid'],
    'Contact' => $prescription['contact_number'] ?? 'N/A'
];
$pdf->addInfoBox('Patient Information', $patientData);

// Medicines
$pdf->addSectionHeader('Prescribed Medicines');

// Get medicines - try different table structures
$medStmt = $conn->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ? AND clinic_id = ?");
$medStmt->bind_param('ii', $prescriptionId, $clinicId);
$medStmt->execute();
$medicines = $medStmt->get_result();

if ($medicines && $medicines->num_rows > 0) {
    $medicineList = [];
    $counter = 1;
    while ($med = $medicines->fetch_assoc()) {
        $medicineName = $med['medicine_name'] ?? $med['drug_name'] ?? 'N/A';
        $dose = $med['dose'] ?? $med['dosage'] ?? 'As directed';
        $duration = $med['duration'] ?? 'As prescribed';
        $instructions = $med['instructions'] ?? $med['remarks'] ?? '-';
        
        $medicineList[] = [
            $counter++,
            $medicineName,
            $dose,
            $duration,
            $instructions
        ];
    }
    
    $pdf->addTable(
        ['#', 'Medicine', 'Dosage', 'Duration', 'Instructions'],
        $medicineList,
        [10, 50, 35, 30, 55]
    );
} else {
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->Cell(0, 6, 'No medicines prescribed', 0, 1);
    $pdf->Ln(5);
}

// Additional Notes
if (!empty($prescription['notes'])) {
    $pdf->addSectionHeader('Additional Notes', [240, 240, 255], [67, 56, 202]);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $prescription['notes'], 0, 'L');
    $pdf->Ln(5);
}

// Signature
$pdf->addSignatureSection($prescription['created_by'] ?? null);

// Output PDF
$pdf->Output('Prescription_RX' . str_pad($prescriptionId, 5, '0', STR_PAD_LEFT) . '.pdf', 'I');
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 6, 'Prescribed Medicines', 0, 1);
$pdf->Ln(2);

$count = 1;
while ($med = $medicines->fetch_assoc()) {
    // Medicine name with number
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(10, 6, $count++ . '.', 0, 0);
    $pdf->Cell(0, 6, $med['medicine_name'], 0, 1);
    
    // Details
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Cell(10, 5, '', 0, 0);
    $details = [];
    if ($med['dose_name']) $details[] = 'Dose: ' . $med['dose_name'];
    if ($med['duration_name']) $details[] = 'Duration: ' . $med['duration_name'];
    $pdf->Cell(0, 5, implode(' | ', $details), 0, 1);
    
    // Instructions
    if ($med['instructions']) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(10, 5, '', 0, 0);
        $pdf->MultiCell(0, 5, 'Instructions: ' . $med['instructions'], 0, 'L');
    }
    
    $pdf->Ln(2);
}

$pdf->Ln(10);

// Footer note
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'This is a computer generated prescription', 0, 1, 'C');
$pdf->Cell(0, 5, 'For any queries, please contact the clinic', 0, 1, 'C');

// Output PDF
$pdf->Output('Prescription_RX' . str_pad($prescriptionId, 5, '0', STR_PAD_LEFT) . '.pdf', 'I');
