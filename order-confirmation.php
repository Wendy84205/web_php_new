<?php
require_once 'includes/init.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$order_id = (int)$_GET['order_id'];

// Get order details
$stmt = $pdo->prepare("SELECT o.*, a.* 
                       FROM orders o 
                       JOIN customer_addresses a ON o.address_id = a.address_id 
                       WHERE o.order_id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, mi.name, mi.image_url 
                       FROM order_items oi 
                       JOIN menu_items mi ON oi.item_id = mi.item_id 
                       WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
        <h1 class="mt-3">Đặt hàng thành công!</h1>
        <p class="lead">Cảm ơn bạn đã đặt hàng tại Com Niêu An Nam Quán</p>
        <p>Mã đơn hàng của bạn: <strong><?= htmlspecialchars($order['order_number']) ?></strong></p>
    </div>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Thông tin đơn hàng</h5>
                    
                    <div class="mb-3">
                        <p><strong>Ngày đặt hàng:</strong> <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                        <p><strong>Trạng thái:</strong> 
                            <span class="badge bg-<?= getStatusColor($order['order_status']) ?>">
                                <?= getStatusText($order['order_status']) ?>
                            </span>
                        </p>
                        <p><strong>Phương thức thanh toán:</strong> <?= getPaymentMethodText($order['payment_method']) ?></p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($item['image_url'] ?? 'assets/images/menu-items/default.jpg') ?>" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                                     class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div class="ms-3">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><?= number_format(($item['discount_price'] ?? $item['unit_price']) * $item['quantity']) ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>Tạm tính:</span>
                            <span><?= number_format($order['subtotal']) ?>₫</span>
                        </div>
                        
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="d-flex justify-content-between">
                                <span>Giảm giá:</span>
                                <span class="text-danger">-<?= number_format($order['discount_amount']) ?>₫</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between">
                            <span>Phí giao hàng:</span>
                            <span><?= number_format($order['delivery_fee']) ?>₫</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Tổng cộng:</span>
                            <span><?= number_format($order['total_amount']) ?>₫</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Thông tin giao hàng</h5>
                    
                    <p><strong>Họ tên:</strong> <?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?></p>
                    <p><strong>Điện thoại:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                    <p><strong>Địa chỉ:</strong> 
                        <?= htmlspecialchars($order['address_line1']) ?>
                        <?php if ($order['address_line2']): ?>
                            , <?= htmlspecialchars($order['address_line2']) ?>
                        <?php endif; ?>
                        , <?= htmlspecialchars("{$order['ward']}, {$order['district']}, {$order['city']}") ?>
                    </p>
                    <p><strong>Ghi chú:</strong> <?= $order['notes'] ? htmlspecialchars($order['notes']) : 'Không có' ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Theo dõi đơn hàng</h5>
                    <p>Bạn có thể theo dõi trạng thái đơn hàng của mình bất cứ lúc nào</p>
                    <a href="order-tracking.php?order_id=<?= $order_id ?>" class="btn btn-primary w-100">
                        Theo dõi đơn hàng
                    </a>
                    <div class="text-center mt-3">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Tiếp tục mua hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';

// Helper functions
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'preparing': return 'primary';
        case 'ready': return 'success';
        case 'on_delivery': return 'info';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    $statuses = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'preparing' => 'Đang chuẩn bị',
        'ready' => 'Sẵn sàng giao',
        'on_delivery' => 'Đang giao hàng',
        'delivered' => 'Đã giao',
        'cancelled' => 'Đã hủy'
    ];
    return $statuses[$status] ?? $status;
}

function getPaymentMethodText($method) {
    $methods = [
        'cash' => 'Tiền mặt khi nhận hàng',
        'momo' => 'Ví điện tử Momo',
        'vnpay' => 'VNPay',
        'bank_transfer' => 'Chuyển khoản ngân hàng'
    ];
    return $methods[$method] ?? $method;
}
?>