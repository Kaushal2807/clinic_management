<?php
/**
 * API: Add Expense
 * Location: api/expense/add.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $category = $_POST['category'] ?? '';
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $description = $_POST['description'] ?? '';
    $vendor = $_POST['vendor'] ?? '';
    $amount = $_POST['amount'] ?? 0;

    if (empty($category) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Category and description are required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $stmt = $conn->prepare("INSERT INTO expenses (category, expense_date, description, vendor, amount, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssd", $category, $expense_date, $description, $vendor, $amount);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Expense added successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add expense']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
