<?php
require_once 'includes/init.php';

$vnp_ResponseCode = $_GET['vnp_ResponseCode'];
$orderId = $_GET['vnp_TxnRef'];

if ($vnp_ResponseCode == '00') {
    // Thanh toán thành công
    $_SESSION['payment_success'] = true;
    
    // Cập nhật CSDL
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ?");
    $stmt->execute([$orderId]);
    
    // Xóa giỏ hàng
    unset($_SESSION['cart']);
    
    header("Location: order-confirmation.php?id=" . $orderId);
} else {
    // Thanh toán thất bại
    $_SESSION['payment_error'] = "Thanh toán VNPay thất bại. Mã lỗi: " . $vnp_ResponseCode;
    header("Location: payment-methods.php?order_id=" . $orderId);
}
exit();