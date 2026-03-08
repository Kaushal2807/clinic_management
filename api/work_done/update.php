<?php
/**
 * API: Update Work Done Record
 * Location: api/work_done/update.php
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? 0;
    $work_done_id = $_POST['work_done_id'] ?? 0;
    $work_date = $_POST['work_date'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $quantity = $_POST['quantity'] ?? 1;

    if (empty($id) || empty($work_done_id)) {
        echo json_encode(['success' => false, 'message' => 'ID and Work Type are required']);
        exit;
    }

    $conn = ClinicContext::getConnection();
    
    // Get work cost from work_done table
    $work = $conn->query("SELECT cost FROM work_done WHERE id = $work_done_id LIMIT 1")->fetch_assoc();
    if (!$work) {
        echo json_encode(['success' => false, 'message' => 'Work type not found']);
        exit;
    }
    $total_cost = $work['cost'] * $quantity;
    
    $stmt = $conn->prepare("UPDATE patient_work_done 
                            SET work_done_id = ?, work_date = ?, notes = ?, quantity = ?, total_cost = ?
                            WHERE id = ?");
    $stmt->bind_param("issidi", $work_done_id, $work_date, $notes, $quantity, $total_cost, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Work record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update work record']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
