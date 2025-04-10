<?php
require_once 'connection.php';

// In a real application, you would implement proper payment verification
// This is just a simple example
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_STRING);
    $reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING);
    
    try {
        $stmt = $conn->prepare("UPDATE payments SET status = 'completed', payment_reference = ? WHERE order_id = ?");
        $stmt->execute([$reference, $order_id]);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>