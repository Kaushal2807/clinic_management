<?php
/**
 * Print Financial Report as PDF
 * Location: clinic/reports/print.php
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

// Get date filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Get statistics
$totalPatients = $conn->query("SELECT COUNT(*) as total FROM patients WHERE clinic_id = $clinicId")->fetch_assoc()['total'];

$newPatients = $conn->query("SELECT COUNT(*) as total FROM patients 
    WHERE clinic_id = $clinicId AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

$totalRevenue = $conn->query("SELECT COALESCE(SUM(total_amount - payment_pending), 0) as total FROM patients 
    WHERE clinic_id = $clinicId AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

$totalExpenses = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
    WHERE clinic_id = $clinicId AND DATE(expense_date) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

$pendingPayments = $conn->query("SELECT COALESCE(SUM(payment_pending), 0) as total FROM patients 
    WHERE clinic_id = $clinicId AND payment_status IN ('pending', 'partial')")->fetch_assoc()['total'];

$totalPrescriptions = $conn->query("SELECT COUNT(*) as total FROM prescriptions 
    WHERE clinic_id = $clinicId AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

$totalTreatments = $conn->query("SELECT COUNT(*) as total FROM treatments 
    WHERE clinic_id = $clinicId AND DATE(treatment_date) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

$totalWorkDone = $conn->query("SELECT COALESCE(SUM(total_cost), 0) as total FROM patient_work_done 
    WHERE clinic_id = $clinicId AND DATE(work_date) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['total'];

// Payment status breakdown
$paymentStats = $conn->query("SELECT payment_status, COUNT(*) as count FROM patients WHERE clinic_id = $clinicId GROUP BY payment_status")->fetch_all(MYSQLI_ASSOC);

// Top expenses
$topExpenses = $conn->query("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses 
    WHERE clinic_id = $clinicId AND DATE(expense_date) BETWEEN '$dateFrom' AND '$dateTo' 
    GROUP BY category ORDER BY total DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Create PDF
$pdf = new PDFHelper($clinic, 'P', 'mm', 'A4');
$pdf->SetTitle('Financial Report');
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 10, 'FINANCIAL REPORT', 0, 1, 'C');
$pdf->Ln(2);

// Period
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Period: ' . date('d M Y', strtotime($dateFrom)) . ' to ' . date('d M Y', strtotime($dateTo)), 0, 1, 'C');
$pdf->Ln(5);

// Key Performance Indicators
$pdf->addSectionHeader('Key Performance Indicators');

// Revenue & Expenses
$profit = $totalRevenue - $totalExpenses;
$profitColor = $profit >= 0 ? [34, 197, 94] : [239, 68, 68];

$kpiData = [
    'Total Revenue' => '₹ ' . number_format($totalRevenue, 2),
    'Total Expenses' => '₹ ' . number_format($totalExpenses, 2),
    'Net Profit/Loss' => '₹ ' . number_format($profit, 2),
    'Pending Payments' => '₹ ' . number_format($pendingPayments, 2)
];
$pdf->addInfoBox('Financial Summary', $kpiData, [240, 255, 240]);

// Patient Statistics
$patientData = [
    'Total Patients' => $totalPatients,
    'New Patients' => $newPatients,
    'Total Prescriptions' => $totalPrescriptions,
    'Total Treatments' => $totalTreatments
];
$pdf->addInfoBox('Patient Statistics', $patientData, [240, 240, 255]);

// Revenue Breakdown
$pdf->AddPage();
$pdf->addSectionHeader('Revenue Breakdown');

$revenueBreakdown = [
    ['Source', 'Amount'],
    ['Consultations (Patients)', '₹ ' . number_format($totalRevenue, 2)],
    ['Work Done', '₹ ' . number_format($totalWorkDone, 2)],
    ['Total', '₹ ' . number_format($totalRevenue + $totalWorkDone, 2)]
];

array_shift($revenueBreakdown); // Remove header
$pdf->addTable(
    ['Revenue Source', 'Amount'],
    $revenueBreakdown,
    [100, 80]
);

// Payment Status Distribution
if (!empty($paymentStats)) {
    $pdf->addSectionHeader('Payment Status Distribution');
    
    $statusList = [];
    foreach ($paymentStats as $stat) {
        $statusList[] = [
            ucfirst($stat['payment_status']),
            $stat['count'],
            number_format(($stat['count'] / $totalPatients * 100), 1) . '%'
        ];
    }
    
    $pdf->addTable(
        ['Status', 'Count', 'Percentage'],
        $statusList,
        [80, 50, 50]
    );
}

// Top Expense Categories
if (!empty($topExpenses)) {
    $pdf->addSectionHeader('Top Expense Categories');
    
    $expenseList = [];
    foreach ($topExpenses as $exp) {
        $percentage = $totalExpenses > 0 ? ($exp['total'] / $totalExpenses * 100) : 0;
        $expenseList[] = [
            $exp['category'],
            '₹ ' . number_format($exp['total'], 2),
            number_format($percentage, 1) . '%'
        ];
    }
    
    $pdf->addTable(
        ['Category', 'Amount', '% of Total'],
        $expenseList,
        [80, 60, 40]
    );
}

// Financial Health Indicators
$pdf->AddPage();
$pdf->addSectionHeader('Financial Health Indicators');

$profitMargin = $totalRevenue > 0 ? (($profit / $totalRevenue) * 100) : 0;
$expenseRatio = $totalRevenue > 0 ? (($totalExpenses / $totalRevenue) * 100) : 0;

$healthData = [
    'Profit Margin' => number_format($profitMargin, 2) . '%',
    'Expense Ratio' => number_format($expenseRatio, 2) . '%',
    'Revenue per Patient' => $newPatients > 0 ? '₹ ' . number_format($totalRevenue / $newPatients, 2) : '₹ 0.00',
    'Average Treatment Value' => $totalTreatments > 0 ? '₹ ' . number_format($totalWorkDone / $totalTreatments, 2) : '₹ 0.00'
];

$pdf->addInfoBox('Key Ratios', $healthData);

// Recommendations
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 7, 'Recommendations', 0, 1);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);

$recommendations = [];
if ($pendingPayments > ($totalRevenue * 0.3)) {
    $recommendations[] = "• High pending payments detected. Consider implementing stricter payment follow-up procedures.";
}
if ($expenseRatio > 70) {
    $recommendations[] = "• Expense ratio is high. Review and optimize operational costs.";
}
if ($profitMargin < 20 && $totalRevenue > 0) {
    $recommendations[] = "• Profit margin is low. Consider reviewing pricing strategy or reducing costs.";
}
if ($newPatients < 5) {
    $recommendations[] = "• Low new patient acquisition. Increase marketing efforts.";
}
if (empty($recommendations)) {
    $recommendations[] = "• Financial performance is healthy. Maintain current strategies.";
}

foreach ($recommendations as $rec) {
    $pdf->MultiCell(0, 5, $rec, 0, 'L');
    $pdf->Ln(1);
}

// Output PDF
$pdf->Output('Financial_Report_' . date('Y-m-d') . '.pdf', 'I');
