<?php
/**
 * API: Delete Expense
 * Location: api/expense/delete.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Expense ID is required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete expense']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
