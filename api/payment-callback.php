<?php
header('Content-Type: application/json');
require_once '../config.php';

// Verify this is a POST request from payment gateway
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get the callback data (this will vary by payment gateway)
$data = $_POST; // For form-encoded
// $data = json_decode(file_get_contents('php://input'), true); // For JSON

// Validate required fields (example for MoMo payment gateway)
if (!isset($data['orderId']) || !isset($data['transId']) || !isset($data['amount']) || !isset($data['resultCode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $db->beginTransaction();
    
    // Find the order
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$data['orderId']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Order not found");
    }
    
    // Check if payment already processed
    $stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? AND transaction_id = ?");
    $stmt->execute([$data['orderId'], $data['transId']]);
    $existing_payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_payment) {
        // Payment already processed
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Payment already processed']);
        exit();
    }
    
    // Determine payment status
    $payment_status = ($data['resultCode'] == 0) ? 'completed' : 'failed';
    
    // Record payment
    $stmt = $db->prepare("INSERT INTO payments 
                         (order_id, amount, payment_method, transaction_id, 
                          payment_status, payment_date, gateway_response)
                         VALUES (?, ?, 'momo', ?, ?, NOW(), ?)");
    $stmt->execute([
        $data['orderId'],
        $data['amount'],
        $data['transId'],
        $payment_status,
        json_encode($data)
    ]);
    
    // Update order payment status if successful
    if ($payment_status === 'completed') {
        $stmt = $db->prepare("UPDATE orders 
                             SET payment_status = 'paid' 
                             WHERE order_id = ?");
        $stmt->execute([$data['orderId']]);
    }
    
    $db->commit();
    
    // Return success response to payment gateway
    echo json_encode([
        'success' => true,
        'orderId' => $data['orderId'],
        'transId' => $data['transId'],
        'message' => 'Payment processed'
    ]);
    
    // TODO: Send notification to user about payment status
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>