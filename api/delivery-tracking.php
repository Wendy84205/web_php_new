<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/auth.php';

// Only drivers can access this API
if (!is_driver()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

$driver_id = get_current_user_id();

// Handle different request methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get current tracking info for driver's orders
        try {
            $stmt = $db->prepare("SELECT t.*, o.order_number 
                                FROM delivery_tracking t
                                JOIN orders o ON t.order_id = o.order_id
                                WHERE t.driver_id = ? AND o.order_status = 'on_delivery'");
            $stmt->execute([$driver_id]);
            $tracking = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['data' => $tracking]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Update driver's location and status
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['order_id']) || !isset($data['lat']) || !isset($data['lng']) || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }
        
        try {
            // Convert lat/lng to POINT
            $point = "POINT(" . floatval($data['lat']) . " " . floatval($data['lng']) . ")";
            
            // Check if tracking record exists
            $stmt = $db->prepare("SELECT * FROM delivery_tracking 
                                 WHERE order_id = ? AND driver_id = ?");
            $stmt->execute([$data['order_id'], $driver_id]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing
                $stmt = $db->prepare("UPDATE delivery_tracking 
                                    SET current_location = ST_GeomFromText(?), 
                                        status = ?, 
                                        updated_at = NOW(),
                                        estimated_time = ?
                                    WHERE order_id = ? AND driver_id = ?");
                $stmt->execute([
                    $point,
                    $data['status'],
                    isset($data['estimated_time']) ? $data['estimated_time'] : null,
                    $data['order_id'],
                    $driver_id
                ]);
            } else {
                // Insert new
                $stmt = $db->prepare("INSERT INTO delivery_tracking 
                                    (order_id, driver_id, current_location, status, estimated_time)
                                    VALUES (?, ?, ST_GeomFromText(?), ?, ?)");
                $stmt->execute([
                    $data['order_id'],
                    $driver_id,
                    $point,
                    $data['status'],
                    isset($data['estimated_time']) ? $data['estimated_time'] : null
                ]);
            }
            
            // If status is delivered, update order status
            if ($data['status'] === 'delivered') {
                $stmt = $db->prepare("UPDATE orders 
                                    SET order_status = 'delivered', 
                                        delivery_time = NOW() 
                                    WHERE order_id = ?");
                $stmt->execute([$data['order_id']]);
                
                // Add to status history
                $stmt = $db->prepare("INSERT INTO order_status_history 
                                    (order_id, status, changed_by)
                                    VALUES (?, 'delivered', ?)");
                $stmt->execute([$data['order_id'], $driver_id]);
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>