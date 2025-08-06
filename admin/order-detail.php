<?php
require_once 'config.php';
require_once 'functions.php';
require_once '../includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    header("Location: orders.php");
    exit();
}

// Get order details
$order = null;
$order_items = [];
$status_history = [];
$driver_info = null;
$address_info = null;

try {
    $db = getDBConnection();
    
    // Get basic order info
    $stmt = $db->prepare("
        SELECT o.*, 
               CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
               u.phone AS customer_phone
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = :order_id
        AND (o.user_id = :user_id OR :is_admin = 1)
    ");
    
    $is_admin = isAdmin($_SESSION['user_id']);
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $_SESSION['user_id'],
        ':is_admin' => $is_admin ? 1 : 0
    ]);
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Order not found or you don't have permission to view it.";
        header("Location: orders.php");
        exit();
    }
    
    // Get order items
    $stmt = $db->prepare("
        SELECT oi.*, mi.name AS item_name, mi.image_url AS item_image
        FROM order_items oi
        JOIN menu_items mi ON oi.item_id = mi.item_id
        WHERE oi.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status history
    $stmt = $db->prepare("
        SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) AS changed_by_name
        FROM order_status_history h
        LEFT JOIN users u ON h.changed_by = u.user_id
        WHERE h.order_id = :order_id
        ORDER BY h.created_at DESC
    ");
    $stmt->execute([':order_id' => $order_id]);
    $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get driver info if assigned
    if ($order['driver_id']) {
        $stmt = $db->prepare("
            SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) AS driver_name,
                   u.phone AS driver_phone
            FROM drivers d
            JOIN users u ON d.driver_id = u.user_id
            WHERE d.driver_id = :driver_id
        ");
        $stmt->execute([':driver_id' => $order['driver_id']]);
        $driver_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get address info
    $stmt = $db->prepare("
        SELECT a.*, 
               ST_X(a.location) AS longitude, 
               ST_Y(a.location) AS latitude
        FROM customer_addresses a
        WHERE a.address_id = :address_id
    ");
    $stmt->execute([':address_id' => $order['address_id']]);
    $address_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment info
    $stmt = $db->prepare("
        SELECT * FROM payments
        WHERE order_id = :order_id
        ORDER BY payment_date DESC
    ");
    $stmt->execute([':order_id' => $order_id]);
    $payment_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error retrieving order details.";
    header("Location: orders.php");
    exit();
}

// Format dates for display
function formatDateForDisplay($date) {
    if (!$date) return 'N/A';
    return date('d/m/Y H:i', strtotime($date));
}

// Page title
$page_title = "Order #" . $order['order_number'];

// Include header
include 'header.php';
?>

<div class="container mt-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Order Details</h4>
                    <span class="badge bg-<?= getStatusColor($order['order_status']) ?>">
                        <?= ucwords(str_replace('_', ' ', $order['order_status'])) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Order Number:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                            <p><strong>Order Date:</strong> <?= formatDateForDisplay($order['order_date']) ?></p>
                            <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Method:</strong> <?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></p>
                            <p><strong>Payment Status:</strong> 
                                <span class="badge bg-<?= $order['payment_status'] == 'paid' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($order['payment_status']) ?>
                                </span>
                            </p>
                            <?php if ($payment_info): ?>
                                <p><strong>Payment Date:</strong> <?= formatDateForDisplay($payment_info['payment_date']) ?></p>
                                <?php if ($payment_info['transaction_id']): ?>
                                    <p><strong>Transaction ID:</strong> <?= htmlspecialchars($payment_info['transaction_id']) ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Items</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['item_image']): ?>
                                                    <img src="<?= htmlspecialchars($item['item_image']) ?>" 
                                                         alt="<?= htmlspecialchars($item['item_name']) ?>" 
                                                         class="img-thumbnail me-2" style="width: 50px; height: 50px;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                                    <?php if ($item['special_instructions']): ?>
                                                        <div class="text-muted small"><?= htmlspecialchars($item['special_instructions']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($item['discount_price'] && $item['discount_price'] < $item['unit_price']): ?>
                                                <span class="text-decoration-line-through text-muted">
                                                    <?= number_format($item['unit_price'], 0) ?>₫
                                                </span><br>
                                                <span class="text-danger"><?= number_format($item['discount_price'], 0) ?>₫</span>
                                            <?php else: ?>
                                                <?= number_format($item['unit_price'], 0) ?>₫
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><?= number_format(($item['discount_price'] ?: $item['unit_price']) * $item['quantity'], 0) ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td><?= number_format($order['subtotal'], 0) ?>₫</td>
                                </tr>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Discount:</strong></td>
                                        <td class="text-danger">-<?= number_format($order['discount_amount'], 0) ?>₫</td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($order['delivery_fee'] > 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Delivery Fee:</strong></td>
                                        <td><?= number_format($order['delivery_fee'], 0) ?>₫</td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($order['tax_amount'] > 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                        <td><?= number_format($order['tax_amount'], 0) ?>₫</td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="fw-bold"><?= number_format($order['total_amount'], 0) ?>₫</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if ($order['notes']): ?>
                        <div class="mt-3">
                            <h5>Order Notes</h5>
                            <p><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Delivery Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($address_info): ?>
                        <p><strong>Address Type:</strong> <?= ucfirst($address_info['address_type']) ?></p>
                        <p><strong>Address:</strong> 
                            <?= htmlspecialchars($address_info['address_line1']) ?><br>
                            <?php if ($address_info['address_line2']): ?>
                                <?= htmlspecialchars($address_info['address_line2']) ?><br>
                            <?php endif; ?>
                            <?= htmlspecialchars($address_info['ward']) ?>, 
                            <?= htmlspecialchars($address_info['district']) ?>, 
                            <?= htmlspecialchars($address_info['city']) ?>
                        </p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($address_info['phone']) ?></p>
                        
                        <?php if ($address_info['notes']): ?>
                            <p><strong>Delivery Notes:</strong> <?= htmlspecialchars($address_info['notes']) ?></p>
                        <?php endif; ?>
                        
                        <div id="deliveryMap" style="height: 250px; width: 100%;" class="mt-3"></div>
                    <?php else: ?>
                        <p>No delivery address found.</p>
                    <?php endif; ?>
                    
                    <?php if ($driver_info): ?>
                        <hr>
                        <h5>Driver Information</h5>
                        <div class="d-flex align-items-center mt-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-person-circle fs-1"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6><?= htmlspecialchars($driver_info['driver_name']) ?></h6>
                                <p class="mb-1">
                                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($driver_info['driver_phone']) ?>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-car-front"></i> <?= htmlspecialchars($driver_info['vehicle_type']) ?> 
                                    (<?= htmlspecialchars($driver_info['vehicle_number']) ?>)
                                </p>
                                <p class="mb-1">
                                    <span class="badge bg-<?= $driver_info['is_available'] ? 'success' : 'secondary' ?>">
                                        <?= $driver_info['is_available'] ? 'Available' : 'Not Available' ?>
                                    </span>
                                    <span class="badge bg-primary">
                                        Rating: <?= number_format($driver_info['rating'], 1) ?>/5
                                    </span>
                                </p>
                            </div>
                        </div>
                    <?php elseif ($order['order_status'] == 'on_delivery' || $order['order_status'] == 'ready'): ?>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> Your order is being prepared for delivery. A driver will be assigned soon.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Status History</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($status_history as $history): ?>
                            <div class="timeline-item">
                                <div class="timeline-item-marker">
                                    <div class="timeline-item-marker-indicator bg-<?= getStatusColor($history['status']) ?>"></div>
                                </div>
                                <div class="timeline-item-content">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold"><?= ucwords(str_replace('_', ' ', $history['status'])) ?></span>
                                        <small class="text-muted"><?= formatDateForDisplay($history['created_at']) ?></small>
                                    </div>
                                    <?php if ($history['changed_by_name']): ?>
                                        <small class="text-muted">Changed by: <?= htmlspecialchars($history['changed_by_name']) ?></small>
                                    <?php endif; ?>
                                    <?php if ($history['notes']): ?>
                                        <div class="mt-1 small"><?= nl2br(htmlspecialchars($history['notes'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Order Actions</h5>
                </div>
                <div class="card-body">
                    <?php if ($order['order_status'] == 'pending' && !$is_admin): ?>
                        <button class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                            <i class="bi bi-x-circle"></i> Cancel Order
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($is_admin): ?>
                        <div class="mb-3">
                            <label for="statusChange" class="form-label">Change Status</label>
                            <select class="form-select" id="statusChange">
                                <option value="">Select new status</option>
                                <option value="confirmed" <?= $order['order_status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="preparing" <?= $order['order_status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                <option value="ready" <?= $order['order_status'] == 'ready' ? 'selected' : '' ?>>Ready</option>
                                <option value="on_delivery" <?= $order['order_status'] == 'on_delivery' ? 'selected' : '' ?>>On Delivery</option>
                                <option value="delivered" <?= $order['order_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $order['order_status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <button id="updateStatusBtn" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-arrow-repeat"></i> Update Status
                        </button>
                        
                        <?php if (!$order['driver_id'] && ($order['order_status'] == 'ready' || $order['order_status'] == 'on_delivery')): ?>
                            <button class="btn btn-info w-100 mb-2" data-bs-toggle="modal" data-bs-target="#assignDriverModal">
                                <i class="bi bi-person-plus"></i> Assign Driver
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <a href="invoice.php?id=<?= $order_id ?>" class="btn btn-secondary w-100 mb-2" target="_blank">
                        <i class="bi bi-receipt"></i> View Invoice
                    </a>
                    
                    <?php if ($order['order_status'] == 'delivered' && !hasReviewedOrder($_SESSION['user_id'], $order_id)): ?>
                        <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#reviewModal">
                            <i class="bi bi-star"></i> Leave a Review
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($is_admin): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#editOrderModal">
                            <i class="bi bi-pencil"></i> Edit Order
                        </button>
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteOrderModal">
                            <i class="bi bi-trash"></i> Delete Order
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5>Customer Support</h5>
                </div>
                <div class="card-body">
                    <p>If you have any questions about your order, please contact our support team.</p>
                    <a href="contact.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-headset"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_order.php" method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to cancel this order?</p>
                    <input type="hidden" name="action" value="cancel_order">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label">Reason for cancellation</label>
                        <textarea class="form-control" id="cancelReason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Review Your Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_review.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Overall Rating</label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i == 5 ? 'checked' : '' ?>>
                                <label for="star<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reviewComment" class="form-label">Your Review</label>
                        <textarea class="form-control" id="reviewComment" name="comment" rows="3"></textarea>
                    </div>
                    
                    <h6>Rate Items (Optional)</h6>
                    <?php foreach ($order_items as $item): ?>
                        <div class="mb-2">
                            <label class="form-label"><?= htmlspecialchars($item['item_name']) ?></label>
                            <select class="form-select form-select-sm" name="item_ratings[<?= $item['item_id'] ?>]">
                                <option value="0">Not rated</option>
                                <option value="1">1 - Poor</option>
                                <option value="2">2 - Fair</option>
                                <option value="3">3 - Good</option>
                                <option value="4">4 - Very Good</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Driver Modal (Admin only) -->
<?php if ($is_admin): ?>
<div class="modal fade" id="assignDriverModal" tabindex="-1" aria-labelledby="assignDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignDriverModalLabel">Assign Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_order.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_driver">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    
                    <div class="mb-3">
                        <label for="driverSelect" class="form-label">Select Driver</label>
                        <select class="form-select" id="driverSelect" name="driver_id" required>
                            <option value="">-- Select Driver --</option>
                            <?php
                            $available_drivers = getAvailableDrivers();
                            foreach ($available_drivers as $driver): ?>
                                <option value="<?= $driver['driver_id'] ?>">
                                    <?= htmlspecialchars($driver['driver_name']) ?> 
                                    (<?= htmlspecialchars($driver['vehicle_type']) ?> - Rating: <?= number_format($driver['rating'], 1) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="driverNotes" class="form-label">Notes for Driver</label>
                        <textarea class="form-control" id="driverNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign Driver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Order Modal (Admin only) -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOrderModalLabel">Edit Order #<?= $order['order_number'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_order.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_order">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <p class="form-control-static"><?= htmlspecialchars($order['customer_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label for="editOrderStatus" class="form-label">Order Status</label>
                            <select class="form-select" id="editOrderStatus" name="order_status">
                                <option value="pending" <?= $order['order_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $order['order_status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="preparing" <?= $order['order_status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                <option value="ready" <?= $order['order_status'] == 'ready' ? 'selected' : '' ?>>Ready</option>
                                <option value="on_delivery" <?= $order['order_status'] == 'on_delivery' ? 'selected' : '' ?>>On Delivery</option>
                                <option value="delivered" <?= $order['order_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $order['order_status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editDeliveryFee" class="form-label">Delivery Fee</label>
                            <input type="number" class="form-control" id="editDeliveryFee" name="delivery_fee" 
                                   value="<?= number_format($order['delivery_fee'], 2, '.', '') ?>" min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label for="editDiscount" class="form-label">Discount Amount</label>
                            <input type="number" class="form-control" id="editDiscount" name="discount_amount" 
                                   value="<?= number_format($order['discount_amount'], 2, '.', '') ?>" min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editOrderNotes" class="form-label">Order Notes</label>
                        <textarea class="form-control" id="editOrderNotes" name="notes" rows="3"><?= htmlspecialchars($order['notes']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Order Modal (Admin only) -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteOrderModalLabel">Delete Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_order.php" method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to permanently delete this order? This action cannot be undone.</p>
                    <input type="hidden" name="action" value="delete_order">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <div class="mb-3">
                        <label for="deleteReason" class="form-label">Reason for deletion</label>
                        <textarea class="form-control" id="deleteReason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Order</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Include Leaflet JS for maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
$(document).ready(function() {
    <?php if ($address_info): ?>
        // Initialize delivery map
        const map = L.map('deliveryMap').setView([
            <?= $address_info['latitude'] ?>, 
            <?= $address_info['longitude'] ?>
        ], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add marker for delivery address
        L.marker([
            <?= $address_info['latitude'] ?>, 
            <?= $address_info['longitude'] ?>
        ]).addTo(map)
          .bindPopup('Delivery Location');
        
        <?php if ($driver_info): ?>
            // Add marker for driver's current location (if available)
            L.marker([
                <?= $driver_info['latitude'] ?>, 
                <?= $driver_info['longitude'] ?>
            ]).addTo(map)
              .bindPopup('Driver Location')
              .setIcon(
                L.icon({
                    iconUrl: 'assets/img/driver-icon.png',
                    iconSize: [32, 32]
                })
              );
        <?php endif; ?>
    <?php endif; ?>
    
    // Update status button
    $('#updateStatusBtn').click(function() {
        const newStatus = $('#statusChange').val();
        if (!newStatus) {
            alert('Please select a status');
            return;
        }
        
        if (confirm('Are you sure you want to change the order status to "' + newStatus + '"?')) {
            $.post('process_order.php', {
                action: 'update_status',
                order_id: <?= $order_id ?>,
                status: newStatus
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to update status'));
                }
            }).fail(function() {
                alert('Error: Failed to communicate with server');
            });
        }
    });
});
</script>

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}
.rating-input input {
    display: none;
}
.rating-input label {
    color: #ddd;
    font-size: 1.5rem;
    padding: 0 0.2rem;
    cursor: pointer;
}
.rating-input input:checked ~ label,
.rating-input input:hover ~ label {
    color: #ffc107;
}
.rating-input input:checked + label:hover,
.rating-input input:checked ~ label:hover,
.rating-input label:hover ~ input:checked ~ label {
    color: #ffc107;
}

.timeline {
    position: relative;
    padding-left: 1rem;
}
.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
    padding-left: 1.5rem;
    border-left: 1px solid #dee2e6;
}
.timeline-item:last-child {
    padding-bottom: 0;
    border-left: 1px solid transparent;
}
.timeline-item-marker {
    position: absolute;
    left: -0.5rem;
    top: 0;
    z-index: 1;
}
.timeline-item-marker-indicator {
    display: block;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    border: 2px solid #fff;
}
</style>

<?php
include 'footer.php';
?>