<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDBConnection() {
    // Verify required constants are defined
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
        throw new Exception('Database configuration constants are not properly defined');
    }

    // Connection settings
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false, // Important for production
        PDO::ATTR_TIMEOUT            => 5,     // Connection timeout in seconds
    ];

    try {
        // Create connection
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_NAME
        );
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Verify connection with a simple query
        $pdo->query('SELECT 1 + 1')->fetchColumn();
        
        // Set timezone if needed
        $pdo->exec("SET time_zone = '+00:00'");
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log detailed error information
        $errorInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error_code' => $e->getCode(),
            'message' => $e->getMessage(),
            'host' => DB_HOST,
            'database' => DB_NAME,
            'user' => DB_USER
        ];
        
        error_log('Database Connection Failed: ' . json_encode($errorInfo));
        
        // Different messages for development vs production
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            $errorMessage = sprintf(
                "Database connection failed: %s (Code: %d)\nHost: %s\nDatabase: %s",
                $e->getMessage(),
                $e->getCode(),
                DB_HOST,
                DB_NAME
            );
        } else {
            $errorMessage = "Database connection error. Please try again later.";
        }
        
        throw new Exception($errorMessage, (int)$e->getCode());
    }
}
// Validate user access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=order-tracking&order_id=" . ($_GET['order_id'] ?? ''));
    exit();
}

// Validate order_id
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$order_id) {
    $_SESSION['error'] = "Invalid order ID";
    header("Location: orders.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize variables
$order = null;
$order_items = [];
$status_history = [];
$tracking_info = null;
$error = null;

try {
    $db = getDBConnection();
    
    // Get order information with improved query
    $stmt = $db->prepare("
        SELECT o.*, 
               a.address_line1, a.address_line2, a.city, a.district, a.ward, a.phone as delivery_phone,
               CONCAT(u.first_name, ' ', u.last_name) as customer_name, 
               u.phone as customer_phone, u.email as customer_email,
               CONCAT(d.first_name, ' ', d.last_name) as driver_name, 
               d.phone as driver_phone, d.avatar as driver_avatar,
               dr.vehicle_type, dr.vehicle_number, dr.rating as driver_rating
        FROM orders o
        JOIN customer_addresses a ON o.address_id = a.address_id
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN drivers dr ON o.driver_id = dr.driver_id
        LEFT JOIN users d ON dr.driver_id = d.user_id
        WHERE o.order_id = ? AND (o.user_id = ? OR ? = (SELECT user_id FROM users WHERE role = 'admin' LIMIT 1))
    ");
    $stmt->execute([$order_id, $user_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Order not found or you don't have permission to view it.");
    }
    
    // Get order items with additional product info
    $stmt = $db->prepare("
        SELECT oi.*, 
               mi.name as item_name, mi.description as item_description,
               mi.image_url, mi.category_id
        FROM order_items oi
        JOIN menu_items mi ON oi.item_id = mi.item_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status history with more details
    $stmt = $db->prepare("
        SELECT h.*, 
               CONCAT(u.first_name, ' ', u.last_name) as changed_by_name,
               u.avatar as changed_by_avatar
        FROM order_status_history h
        LEFT JOIN users u ON h.changed_by = u.user_id
        WHERE h.order_id = ?
        ORDER BY h.created_at DESC
    ");
    $stmt->execute([$order_id]);
    $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get tracking info with additional safety checks
    if (in_array($order['order_status'], ['on_delivery', 'ready', 'delivered'])) {
        $stmt = $db->prepare("
            SELECT dt.*, 
                   ST_X(dt.current_location) as lng, 
                   ST_Y(dt.current_location) as lat,
                   dt.estimated_time, dt.status, dt.updated_at
            FROM delivery_tracking dt
            WHERE dt.order_id = ?
            ORDER BY dt.updated_at DESC
            LIMIT 1
        ");
        $stmt->execute([$order_id]);
        $tracking_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Database error [Order ID: $order_id, User ID: $user_id]: " . $e->getMessage());
    $error = "A database error occurred while fetching order details. Our team has been notified.";
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Set page title safely
$page_title = isset($order['order_number']) ? "Order Tracking #" . htmlspecialchars($order['order_number']) : "Order Tracking";

// Include header
include 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            <div class="mt-2">
                <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
                <a href="contact.php" class="btn btn-primary">Contact Support</a>
            </div>
        </div>
    <?php elseif ($order): ?>
        <!-- Order Tracking Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
            <div>
                <a href="orders.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/order-edit.php?id=<?php echo $order_id; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit Order
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Main Order Content -->
            <div class="col-lg-8">
                <!-- Order Status Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Order Status</h5>
                            <span class="badge bg-white text-primary">
                                <?php echo format_date($order['order_date'], true); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Order Status Progress -->
                        <div class="order-status-progress mb-4">
                            <?php 
                            $statuses = [
                                'pending' => ['icon' => 'bi-hourglass', 'label' => 'Pending'],
                                'confirmed' => ['icon' => 'bi-check-circle', 'label' => 'Confirmed'],
                                'preparing' => ['icon' => 'bi-egg-fried', 'label' => 'Preparing'],
                                'ready' => ['icon' => 'bi-check2-all', 'label' => 'Ready'],
                                'on_delivery' => ['icon' => 'bi-truck', 'label' => 'On Delivery'],
                                'delivered' => ['icon' => 'bi-house-check', 'label' => 'Delivered']
                            ];
                            
                            $current_status = $order['order_status'];
                            $status_keys = array_keys($statuses);
                            $current_index = array_search($current_status, $status_keys);
                            ?>
                            
                            <div class="steps">
                                <?php foreach ($statuses as $key => $status): ?>
                                    <?php 
                                    $step_index = array_search($key, $status_keys);
                                    $is_completed = $step_index < $current_index;
                                    $is_active = $key === $current_status;
                                    $is_pending = $step_index > $current_index;
                                    ?>
                                    
                                    <div class="step <?php echo $is_completed ? 'completed' : ''; ?> 
                                        <?php echo $is_active ? 'active' : ''; ?>">
                                        <div class="step-icon">
                                            <i class="bi <?php echo $status['icon']; ?>"></i>
                                        </div>
                                        <div class="step-label"><?php echo $status['label']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="bi bi-clock-history"></i> Estimated Delivery</h6>
                                <p class="text-muted">
                                    <?php if ($order['delivery_time']): ?>
                                        <?php echo format_date($order['delivery_time'], true); ?>
                                    <?php elseif ($tracking_info && $tracking_info['estimated_time']): ?>
                                        <?php echo format_date($tracking_info['estimated_time'], true); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-credit-card"></i> Payment</h6>
                                <p>
                                    <span class="badge bg-<?php 
                                        switch($order['payment_status']) {
                                            case 'paid': echo 'success'; break;
                                            case 'failed': echo 'danger'; break;
                                            case 'refunded': echo 'info'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                    <span class="text-muted ms-2">
                                        (<?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?>)
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px"></th>
                                        <th>Item</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'assets/images/menu-items/default.jpg'); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                                     class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                                <?php if ($item['item_description']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['item_description']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($item['special_instructions']): ?>
                                                    <div class="mt-1">
                                                        <small class="text-primary">
                                                            <i class="bi bi-info-circle"></i> 
                                                            <?php echo htmlspecialchars($item['special_instructions']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end"><?php echo format_currency($item['discount_price'] ?? $item['unit_price']); ?></td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end"><?php echo format_currency(($item['discount_price'] ?? $item['unit_price']) * $item['quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Subtotal</th>
                                        <td class="text-end"><?php echo format_currency($order['subtotal']); ?></td>
                                    </tr>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <tr>
                                            <th colspan="4" class="text-end">Discount</th>
                                            <td class="text-end text-danger">-<?php echo format_currency($order['discount_amount']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($order['tax_amount'] > 0): ?>
                                        <tr>
                                            <th colspan="4" class="text-end">Tax</th>
                                            <td class="text-end"><?php echo format_currency($order['tax_amount']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th colspan="4" class="text-end">Delivery Fee</th>
                                        <td class="text-end"><?php echo format_currency($order['delivery_fee']); ?></td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <th colspan="4" class="text-end">Total</th>
                                        <td class="text-end"><?php echo format_currency($order['total_amount']); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Status History -->
                <?php if (!empty($status_history)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Status History</h5>
                                <small class="text-muted"><?php echo count($status_history); ?> updates</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($status_history as $history): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-item-marker">
                                            <div class="timeline-item-marker-indicator bg-<?php 
                                                switch($history['status']) {
                                                    case 'pending': echo 'secondary'; break;
                                                    case 'confirmed': echo 'info'; break;
                                                    case 'preparing': echo 'warning'; break;
                                                    case 'ready': echo 'primary'; break;
                                                    case 'on_delivery': echo 'primary'; break;
                                                    case 'delivered': echo 'success'; break;
                                                    case 'cancelled': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"></div>
                                            <?php if ($history['changed_by_avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($history['changed_by_avatar']); ?>" 
                                                     alt="<?php echo htmlspecialchars($history['changed_by_name']); ?>" 
                                                     class="timeline-avatar">
                                            <?php else: ?>
                                                <div class="timeline-avatar-initials">
                                                    <?php echo getInitials($history['changed_by_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="timeline-item-content">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1">
                                                    <?php echo ucwords(str_replace('_', ' ', $history['status'])); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo format_date($history['created_at'], true); ?>
                                                </small>
                                            </div>
                                            <?php if ($history['changed_by_name']): ?>
                                                <p class="small mb-1">
                                                    <i class="bi bi-person"></i> 
                                                    <?php echo htmlspecialchars($history['changed_by_name']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($history['notes']): ?>
                                                <div class="alert alert-light p-2 mt-2 mb-0">
                                                    <small><?php echo htmlspecialchars($history['notes']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Delivery Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Delivery Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-geo-alt fs-4 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Delivery Address</h6>
                                <p class="mb-1">
                                    <?php echo htmlspecialchars($order['address_line1']); ?>
                                    <?php if ($order['address_line2']): ?>
                                        <br><?php echo htmlspecialchars($order['address_line2']); ?>
                                    <?php endif; ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($order['ward'] . ', ' . $order['district'] . ', ' . $order['city']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-telephone fs-4 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Contact Phone</h6>
                                <p class="mb-0">
                                    <a href="tel:<?php echo htmlspecialchars($order['delivery_phone']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($order['delivery_phone']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-chat-left-text fs-4 text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Delivery Notes</h6>
                                    <div class="alert alert-light p-2 mb-0">
                                        <small><?php echo htmlspecialchars($order['notes']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Driver Information -->
                <?php if ($order['driver_id']): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Your Driver</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="flex-shrink-0">
                                    <?php if ($order['driver_avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($order['driver_avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['driver_name']); ?>" 
                                             class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="avatar-initials rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px; background-color: #<?php echo stringToColor($order['driver_name']); ?>">
                                            <?php echo getInitials($order['driver_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($order['driver_name']); ?></h6>
                                    <div class="d-flex align-items-center mb-1">
                                        <?php if ($order['driver_rating'] > 0): ?>
                                            <div class="rating-stars me-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $order['driver_rating'] ? '-fill' : ''; ?> text-warning"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($order['driver_rating'], 1); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">No ratings yet</small>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-0">
                                        <a href="tel:<?php echo htmlspecialchars($order['driver_phone']); ?>" class="text-decoration-none">
                                            <i class="bi bi-telephone"></i> 
                                            <?php echo htmlspecialchars($order['driver_phone']); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-truck fs-4 text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Vehicle Information</h6>
                                    <p class="mb-1">
                                        <?php echo htmlspecialchars(ucfirst($order['vehicle_type'])); ?>
                                        <small class="text-muted">(<?php echo htmlspecialchars($order['vehicle_number']); ?>)</small>
                                    </p>
                                    <?php if ($tracking_info): ?>
                                        <div class="alert alert-info p-2 mt-2 mb-0">
                                            <small>
                                                <i class="bi bi-info-circle"></i> 
                                                <?php echo ucwords(str_replace('_', ' ', $tracking_info['status'])); ?>
                                                <?php if ($tracking_info['estimated_time']): ?>
                                                    - ETA: <?php echo format_date($tracking_info['estimated_time'], true); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Delivery Tracking Map -->
                <?php if ($tracking_info): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Delivery Tracking</h5>
                        </div>
                        <div class="card-body">
                            <div id="deliveryMap" style="height: 250px; width: 100%;" class="mb-3"></div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 
                                    Last updated: <?php echo format_date($tracking_info['updated_at'], true); ?>
                                </small>
                                <?php if ($tracking_info['estimated_time']): ?>
                                    <small class="text-primary">
                                        <i class="bi bi-alarm"></i> 
                                        ETA: <?php echo format_date($tracking_info['estimated_time'], true); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        // Initialize map when tracking info is available
                        document.addEventListener('DOMContentLoaded', function() {
                            // Initialize map with Leaflet.js (make sure to include Leaflet CSS/JS in your header)
                            var map = L.map('deliveryMap').setView([<?php echo $tracking_info['lat']; ?>, <?php echo $tracking_info['lng']; ?>], 15);
                            
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                            }).addTo(map);
                            
                            // Add marker for driver location
                            var driverMarker = L.marker([<?php echo $tracking_info['lat']; ?>, <?php echo $tracking_info['lng']; ?>]).addTo(map)
                                .bindPopup('Driver Location');
                            
                            // Add marker for delivery address (you would need to geocode the address)
                            // L.marker([LAT, LNG]).addTo(map).bindPopup('Delivery Address');
                            
                            // Optional: Add a line between driver and destination
                            // L.polyline([[LAT1, LNG1], [LAT2, LNG2]], {color: 'red'}).addTo(map);
                        });
                    </script>
                <?php endif; ?>
                
                <!-- Customer Support -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-headset fs-4 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Customer Support</h6>
                                <p class="mb-1">
                                    <a href="tel:0123456789" class="text-decoration-none">
                                        <i class="bi bi-telephone"></i> 0123 456 789
                                    </a>
                                </p>
                                <p class="mb-0">
                                    <a href="mailto:support@com-nieu.com" class="text-decoration-none">
                                        <i class="bi bi-envelope"></i> support@com-nieu.com
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="contact.php" class="btn btn-outline-primary">
                                <i class="bi bi-chat-left-text"></i> Contact Us
                            </a>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#helpModal">
                                <i class="bi bi-question-circle"></i> Help Center
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Help Modal -->
        <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="helpModalLabel">Help Center</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Frequently Asked Questions</h6>
                        <div class="accordion mb-3" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                        How can I change my delivery address?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        You can change your delivery address before the restaurant starts preparing your order. 
                                        Contact our support team immediately if you need to change your address.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                        My order is delayed, what should I do?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        We apologize for the delay. You can track your order in real-time on this page. 
                                        If the delay is significant, please contact our support team for assistance.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h6>Order Issues</h6>
                        <div class="list-group mb-3">
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="bi bi-basket"></i> Missing items in my order
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="bi bi-emoji-frown"></i> Wrong items delivered
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="bi bi-currency-dollar"></i> Payment issues
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="contact.php" class="btn btn-primary">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include 'includes/footer.php';// Helper functions