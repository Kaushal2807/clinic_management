<?php
/**
 * API: Update Expense
 * Location: api/expense/update.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? 0;
    $category = $_POST['category'] ?? '';
    $expense_date = $_POST['expense_date'] ?? '';
    $description = $_POST['description'] ?? '';
    $vendor = $_POST['vendor'] ?? '';
    $amount = $_POST['amount'] ?? 0;

    if (empty($id) || empty($category) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'ID, category and description are required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    $stmt = $conn->prepare("UPDATE expenses 
                            SET category = ?, expense_date = ?, description = ?, vendor = ?, amount = ?
                            WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param("ssssdii", $category, $expense_date, $description, $vendor, $amount, $id, $clinicId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update expense']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
