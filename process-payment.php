<?php
require_once 'includes/init.php';

// Kiểm tra dữ liệu
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['payment_method'])) {
    header('Location: payment-methods.php');
    exit();
}

$paymentMethod = $_POST['payment_method'];
$orderId = $_SESSION['current_order_id'] ?? null; // Lấy từ session sau khi tạo đơn hàng

try {
    // Kiểm tra đơn hàng hợp lệ
    if (!$orderId) {
        throw new Exception("Không tìm thấy đơn hàng");
    }

    // Load class thanh toán tương ứng
    $paymentClassFile = "payment-gateways/{$paymentMethod}/{$paymentMethod}Payment.php";
    
    if (!file_exists($paymentClassFile)) {
        throw new Exception("Phương thức thanh toán không khả dụng");
    }

    require_once $paymentClassFile;
    
    $className = ucfirst($paymentMethod) . 'Payment';
    if (!class_exists($className)) {
        throw new Exception("Hệ thống thanh toán tạm thời gián đoạn");
    }

    $payment = new $className();
    
    // Gọi phương thức thanh toán
    if (!method_exists($payment, 'processPayment')) {
        throw new Exception("Phương thức thanh toán không hỗ trợ");
    }

    // Lấy tổng tiền từ đơn hàng (giả sử đã lưu trong session)
    $amount = $_SESSION['current_order_amount'];
    
    // Thực hiện thanh toán
    $payment->processPayment($orderId, $amount);

} catch (Exception $e) {
    $_SESSION['payment_error'] = $e->getMessage();
    header("Location: payment-methods.php");
    exit();
}