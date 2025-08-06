<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/VNPayPayment.php';

$vnpay = new VNPayPayment();
$data = $_GET;

// Kiểm tra chữ ký
if (!$vnpay->verifyResponse($data)) {
    http_response_code(403);
    die('Invalid signature');
}

// Xử lý kết quả thanh toán
$orderId = $data['vnp_TxnRef'];
$transId = $data['vnp_TransactionNo'];
$amount = $data['vnp_Amount'] / 100;
$responseCode = $data['vnp_ResponseCode'];

// Lấy thông tin đơn hàng từ database
$order = getOrderById($orderId);

if ($responseCode == '00') {
    // Thanh toán thành công
    updateOrderPaymentStatus($orderId, 'paid', $transId, $amount);
    
    // Ghi vào bảng payments
    $paymentData = [
        'order_id' => $orderId,
        'amount' => $amount,
        'payment_method' => 'vnpay',
        'transaction_id' => $transId,
        'payment_status' => 'completed',
        'gateway_response' => json_encode($data)
    ];
    createPayment($paymentData);
    
    // Chuyển hướng đến trang cảm ơn
    header('Location: /order-confirmation.php?order_id=' . $orderId);
} else {
    // Thanh toán thất bại
    updateOrderPaymentStatus($orderId, 'failed', $transId, $amount);
    
    // Ghi vào bảng payments
    $paymentData = [
        'order_id' => $orderId,
        'amount' => $amount,
        'payment_method' => 'vnpay',
        'transaction_id' => $transId,
        'payment_status' => 'failed',
        'gateway_response' => json_encode($data)
    ];
    createPayment($paymentData);
    
    // Chuyển hướng đến trang lỗi
    header('Location: /checkout.php?payment_error=1');
}
?>