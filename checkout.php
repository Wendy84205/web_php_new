<?php
// Luôn kiểm tra/khởi tạo session trước, và các redirect trước khi in bất kỳ output nào
require_once 'includes/init.php'; // giả định ở đây có session_start() và $pdo

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: my-account.php?redirect=checkout');
    exit();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}


// Tính toán giỏ hàng
$subtotal = 0;
$cart_items = [];
$session_cart = $_SESSION['cart']; // định dạng: [item_id => quantity]

// Lấy danh sách item_id
$item_ids = array_keys($session_cart);
$placeholders = implode(',', array_fill(0, count($item_ids), '?'));

// Lấy thông tin sản phẩm hiện có
$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE item_id IN ($placeholders) AND is_available = TRUE");
$stmt->execute($item_ids);
$fetched_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xây dựng mảng cart_items có quantity và total_price
foreach ($fetched_items as $item) {
    $quantity = isset($session_cart[$item['item_id']]) ? (int) $session_cart[$item['item_id']] : 0;
    if ($quantity <= 0)
        continue;
    $price = isset($item['discounted_price']) && $item['discounted_price'] > 0 ? $item['discounted_price'] : $item['price'];
    $total_price = $price * $quantity;
    $subtotal += $total_price;

    $item['quantity'] = $quantity;
    $item['unit_price'] = $item['price'];
    $item['discounted_price'] = $price !== $item['price'] ? $price : null;
    $item['total_price'] = $total_price;
    $cart_items[] = $item;
}

// Áp dụng coupon (nếu có)
$discount_amount = 0;
if (isset($_SESSION['applied_coupon']) && is_array($_SESSION['applied_coupon'])) {
    $coupon = $_SESSION['applied_coupon'];
    if (isset($coupon['discount_type'], $coupon['discount_value'])) {
        if ($coupon['discount_type'] === 'percentage') {
            $discount_amount = $subtotal * ($coupon['discount_value'] / 100);
        } else { // fixed
            $discount_amount = min($coupon['discount_value'], $subtotal);
        }
    }
}

// Phí giao hàng và tổng
$delivery_fee = 15000;
$total = $subtotal - $discount_amount + $delivery_fee;

// Lấy địa chỉ người dùng
$stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Validate address_id
        if (empty($_POST['address_id'])) {
            throw new Exception("Vui lòng chọn địa chỉ giao hàng.");
        }
        $address_id = (int) $_POST['address_id'];

        $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE address_id = ? AND user_id = ?");
        $stmt->execute([$address_id, $_SESSION['user_id']]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$address) {
            throw new Exception("Địa chỉ không hợp lệ.");
        }

        // Tạo mã đơn hàng
        $order_number = 'COM' . date('Ymd') . strtoupper(substr(uniqid(), -6));

        // Tính lại (phòng trường hợp thay đổi giữa lúc load và submit)
        $subtotal = 0;
        foreach ($cart_items as &$it) {
            $subtotal += $it['total_price'];
        }
        if (isset($coupon) && isset($coupon['discount_type'], $coupon['discount_value'])) {
            if ($coupon['discount_type'] === 'percentage') {
                $discount_amount = $subtotal * ($coupon['discount_value'] / 100);
            } else {
                $discount_amount = min($coupon['discount_value'], $subtotal);
            }
        } else {
            $discount_amount = 0;
        }
        $total = $subtotal - $discount_amount + $delivery_fee;

        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders 
            (user_id, address_id, order_number, order_status, delivery_fee, subtotal, discount_amount, total_amount, payment_method, payment_status, notes) 
            VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $address_id,
            $order_number,
            $delivery_fee,
            $subtotal,
            $discount_amount,
            $total,
            $_POST['payment_method'] ?? 'cash',
            $_POST['notes'] ?? null
        ]);
        $order_id = $pdo->lastInsertId();

        // Insert order items
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, item_id, quantity, unit_price, discount_price) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $order_id,
                $item['item_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['discounted_price'] ?? null
            ]);
        }

        // Lưu lịch sử trạng thái
        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, 'pending', ?)");
        $stmt->execute([$order_id, $_SESSION['user_id']]);

        $pdo->commit();

        // Xoá giỏ và coupon
        unset($_SESSION['cart']);
        unset($_SESSION['applied_coupon']);

        // Xử lý thanh toán
        $payment_method = $_POST['payment_method'] ?? 'cash';
        if ($payment_method === 'cash') {
            header("Location: order-confirmation.php?order_id=$order_id");
            exit();
        } else {
            // Ví dụ: online gateway
            $safe_method = preg_replace('/[^a-z0-9_\\-]/i', '', $payment_method);
            // Change this:
            $payment_path = "payment-gateways/{$safe_method}/{$safe_method}Payment.php";
            if (file_exists($payment_path)) {
                require_once $payment_path;
                $className = ucfirst($safe_method) . 'Payment';
                if (class_exists($className)) {
                    $payment = new $className();
                    $payment->processPayment($order_id, $total);
                } else {
                    throw new Exception("Phương thức thanh toán không hợp lệ.");
                }
            }

            // To this:
            $payment_class_file = "payment-gateways/{$safe_method}/{$safe_method}Payment.php";
            if (file_exists($payment_class_file)) {
                require_once $payment_class_file;
                $className = ucfirst($safe_method) . 'Payment';

                if (!class_exists($className)) {
                    throw new Exception("Payment class not found");
                }

                $payment = new $className();

                if (!method_exists($payment, 'processPayment')) {
                    throw new Exception("Payment method not supported");
                }

                $payment->processPayment($order_id, $total);
            } else {
                throw new Exception("Payment gateway not found");
            }
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

// Sau xử lý backend thì include header/navbar và hiển thị form
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>
<div class="container py-5">
    <h1 class="mb-4">Thanh toán</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="post" class="checkout-form">
        <div class="row">
            <div class="col-lg-8">
                <!-- Thông tin giao hàng -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Thông tin giao hàng</h5>
                        <div class="mb-3">
                            <label class="form-label">Chọn địa chỉ giao hàng</label>
                            <div class="list-group">
                                <?php foreach ($addresses as $addr): ?>
                                    <label class="list-group-item">
                                        <input type="radio" name="address_id" value="<?= $addr['address_id'] ?>"
                                            class="form-check-input me-2" <?= $addr['is_default'] ? 'checked' : '' ?>
                                            required>
                                        <div>
                                            <strong><?= ucfirst(htmlspecialchars($addr['address_type'])) ?></strong>
                                            <p class="mb-1"><?= htmlspecialchars($addr['address_line1']) ?></p>
                                            <?php if (!empty($addr['address_line2'])): ?>
                                                <p class="mb-1"><?= htmlspecialchars($addr['address_line2']) ?></p>
                                            <?php endif; ?>
                                            <p class="mb-1">
                                                <?= htmlspecialchars("{$addr['ward']}, {$addr['district']}, {$addr['city']}") ?>
                                            </p>
                                            <p class="mb-1">Điện thoại: <?= htmlspecialchars($addr['phone']) ?></p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <a href="my-account.php?action=add_address" class="btn btn-outline-primary">
                            <i class="bi bi-plus"></i> Thêm địa chỉ mới
                        </a>
                    </div>
                </div>

                <!-- Phương thức thanh toán -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Phương thức thanh toán</h5>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cash"
                                    value="cash" checked>
                                <label class="form-check-label" for="cash">
                                    <i class="bi bi-cash-coin"></i> Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="momo"
                                    value="momo">
                                <label class="form-check-label" for="momo">
                                    <img src="assets/images/payments/momo.png" alt="Momo" style="height: 24px;">
                                    Ví điện tử Momo
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="vnpay"
                                    value="vnpay">
                                <label class="form-check-label" for="vnpay">
                                    <img src="assets/images/payments/vnpay.png" alt="VNPay" style="height: 24px;">
                                    VNPay
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ghi chú -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ghi chú đơn hàng</h5>
                        <textarea name="notes" class="form-control" rows="3"
                            placeholder="Ghi chú cho nhà hàng..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Đơn hàng của bạn</h5>

                        <div class="mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <span class="fw-bold"><?= htmlspecialchars($item['name']) ?></span>
                                        <span class="text-muted">x <?= $item['quantity'] ?></span>
                                    </div>
                                    <span><?= number_format($item['total_price']) ?>₫</span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <span><?= number_format($subtotal) ?>₫</span>
                        </div>

                        <?php if ($discount_amount > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Giảm giá:</span>
                                <span class="text-danger">-<?= number_format($discount_amount) ?>₫</span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Phí giao hàng:</span>
                            <span><?= number_format($delivery_fee) ?>₫</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Tổng cộng:</span>
                            <span><?= number_format($total) ?>₫</span>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            Đặt hàng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
require_once 'includes/footer.php';
?>