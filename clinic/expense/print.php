<?php
/**
 * Print Expense Report as PDF
 * Location: clinic/expense/print.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/PDFHelper.php';

Auth::requireClinic();
ClinicContext::init();

$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today
$category = $_GET['category'] ?? '';

// Build query
$conditions = ["DATE(expense_date) BETWEEN '$dateFrom' AND '$dateTo'"];
if ($category) {
    $conditions[] = "category = '" . $conn->real_escape_string($category) . "'";
}
$whereClause = "WHERE " . implode(' AND ', $conditions);

// Get expenses
$expenses = $conn->query("SELECT * FROM expenses $whereClause ORDER BY expense_date DESC");

// Get total
$total = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses $whereClause")->fetch_assoc()['total'];

// Get category breakdown
$categoryBreakdown = $conn->query("SELECT category, COALESCE(SUM(amount), 0) as total 
    FROM expenses $whereClause 
    GROUP BY category 
    ORDER BY total DESC");

// Create PDF
$pdf = new PDFHelper($clinic, 'P', 'mm', 'A4');
$pdf->SetTitle('Expense Report');
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(67, 56, 202);
$pdf->Cell(0, 10, 'EXPENSE REPORT', 0, 1, 'C');
$pdf->Ln(2);

// Period
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Period: ' . date('d M Y', strtotime($dateFrom)) . ' to ' . date('d M Y', strtotime($dateTo)), 0, 1, 'C');
if ($category) {
    $pdf->Cell(0, 5, 'Category: ' . $category, 0, 1, 'C');
}
$pdf->Ln(3);

// Summary Box
$pdf->SetFillColor(239, 68, 68, 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(60, 8, 'Total Expenses:', 0, 0, 'L', true);
$pdf->SetTextColor(239, 68, 68);
$pdf->Cell(0, 8, '₹ ' . number_format($total, 2), 0, 1, 'R', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(5);

// Category Breakdown
if ($categoryBreakdown && $categoryBreakdown->num_rows > 0) {
    $pdf->addSectionHeader('Expense by Category');
    
    $catList = [];
    while ($cat = $categoryBreakdown->fetch_assoc()) {
        $percentage = $total > 0 ? ($cat['total'] / $total * 100) : 0;
        $catList[] = [
            $cat['category'],
            '₹ ' . number_format($cat['total'], 2),
            number_format($percentage, 1) . '%'
        ];
    }
    
    $pdf->addTable(
        ['Category', 'Amount', '% of Total'],
        $catList,
        [80, 60, 40]
    );
}

// Detailed Expenses
if ($expenses && $expenses->num_rows > 0) {
    $pdf->AddPage();
    $pdf->addSectionHeader('Detailed Expenses');
    
    $expenseList = [];
    $counter = 1;
    
    while ($exp = $expenses->fetch_assoc()) {
        $expenseList[] = [
            $counter++,
            date('d/m/Y', strtotime($exp['expense_date'])),
            $exp['category'],
            substr($exp['description'] ?? 'N/A', 0, 40),
            '₹ ' . number_format($exp['amount'], 2)
        ];
    }
    
    $pdf->addTable(
        ['#', 'Date', 'Category', 'Description', 'Amount'],
        $expenseList,
        [10, 25, 40, 70, 35]
    );
} else {
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'No expenses found for the selected period', 0, 1);
}

// Summary Statistics
$pdf->AddPage();
$pdf->addSectionHeader('Summary Statistics');

// Calculate monthly average
$monthsDiff = max(1, (strtotime($dateTo) - strtotime($dateFrom)) / (30 * 24 * 60 * 60));
$monthlyAvg = $total / $monthsDiff;

$stats = [
    'Total Expenses' => '₹ ' . number_format($total, 2),
    'Number of Transactions' => $expenses ? $expenses->num_rows : 0,
    'Average per Transaction' => $expenses && $expenses->num_rows > 0 ? '₹ ' . number_format($total / $expenses->num_rows, 2) : '₹ 0.00',
    'Monthly Average' => '₹ ' . number_format($monthlyAvg, 2),
    'Report Generated On' => date('d M Y, h:i A')
];

$pdf->addInfoBox('', $stats);

// Output PDF
$pdf->Output('Expense_Report_' . date('Y-m-d') . '.pdf', 'I');
