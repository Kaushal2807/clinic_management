<?php
/**
 * API: Update Expense Category
 * Location: api/expense/update_category.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? 0;
    $category_name = $_POST['category_name'] ?? '';

    if (empty($id) || empty($category_name)) {
        echo json_encode(['success' => false, 'message' => 'ID and category name are required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $clinicId = ClinicContext::getClinicId();
    $stmt = $conn->prepare("UPDATE expense_categories SET category_name = ? WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param("sii", $category_name, $id, $clinicId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update category']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
