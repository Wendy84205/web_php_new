<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/MomoPayment.php';

$momo = new MomoPayment();
$data = $_POST;

// Kiểm tra chữ ký
if (!$momo->verifySignature($data, $data['signature'])) {
    http_response_code(403);
    die('Invalid signature');
}

// Xử lý kết quả thanh toán
$orderId = $data['orderId'];
$transId = $data['transId'];
$amount = $data['amount'];
$errorCode = $data['errorCode'];

// Lấy thông tin đơn hàng từ database
$order = getOrderById($orderId);

if ($errorCode == 0) {
    // Thanh toán thành công
    updateOrderPaymentStatus($orderId, 'paid', $transId, $amount);
    
    // Ghi vào bảng payments
    $paymentData = [
        'order_id' => $orderId,
        'amount' => $amount,
        'payment_method' => 'momo',
        'transaction_id' => $transId,
        'payment_status' => 'completed',
        'gateway_response' => json_encode($data)
    ];
    createPayment($paymentData);
    
    // Trả kết quả về cho Momo
    echo json_encode(['status' => 0, 'message' => 'Success']);
} else {
    // Thanh toán thất bại
    updateOrderPaymentStatus($orderId, 'failed', $transId, $amount);
    
    // Ghi vào bảng payments
    $paymentData = [
        'order_id' => $orderId,
        'amount' => $amount,
        'payment_method' => 'momo',
        'transaction_id' => $transId,
        'payment_status' => 'failed',
        'gateway_response' => json_encode($data)
    ];
    createPayment($paymentData);
    
    // Trả kết quả về cho Momo
    echo json_encode(['status' => 1, 'message' => 'Failed']);
}
?>