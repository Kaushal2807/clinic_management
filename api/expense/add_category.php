<?php
/**
 * API: Add Expense Category
 * Location: api/expense/add_category.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $category_name = $_POST['category_name'] ?? '';

    if (empty($category_name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $stmt = $conn->prepare("INSERT INTO expense_categories (category_name) VALUES (?)");
    $stmt->bind_param("s", $category_name);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Category added successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add category']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
