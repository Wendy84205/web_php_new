<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/auth.php';

// Only admin and staff can access this API
if (!is_admin() && !is_staff()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

$user_id = get_current_user_id();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

// Validate status
$valid_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'on_delivery', 'delivered', 'cancelled'];
if (!in_array($data['status'], $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit();
}

try {
    $db->beginTransaction();
    
    // Update order status
    $stmt = $db->prepare("UPDATE orders 
                         SET order_status = ? 
                         WHERE order_id = ?");
    $stmt->execute([$data['status'], $data['order_id']]);
    
    // Add to status history
    $stmt = $db->prepare("INSERT INTO order_status_history 
                         (order_id, status, changed_by, notes)
                         VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data['order_id'],
        $data['status'],
        $user_id,
        isset($data['notes']) ? $data['notes'] : null
    ]);
    
    // If status is on_delivery and driver is assigned, notify driver
    if ($data['status'] === 'on_delivery' && isset($data['driver_id'])) {
        // Here you would typically send a push notification
        // For now we'll just log it
        error_log("Order {$data['order_id']} assigned to driver {$data['driver_id']}");
    }
    
    $db->commit();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>