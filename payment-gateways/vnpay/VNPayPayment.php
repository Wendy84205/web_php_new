<?php
class VNPayPayment {
    private $vnp_TmnCode;
    private $vnp_HashSecret;
    private $vnp_Url;
    private $vnp_Returnurl;
    
    public function __construct() {
        $this->vnp_TmnCode = 'VNPAY123';
        $this->vnp_HashSecret = 'SECRET123456789';
        $this->vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
        $this->vnp_Returnurl = 'https://yourdomain.com/payment-gateways/vnpay/callback-handler.php';
    }
    
    public function createPayment($orderId, $amount, $orderInfo, $clientIp) {
        $vnp_TxnRef = $orderId;
        $vnp_OrderInfo = $orderInfo;
        $vnp_OrderType = 'food';
        $vnp_Amount = $amount * 100;
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $clientIp;
        $vnp_CreateDate = date('YmdHis');
        
        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $this->vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        ];
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url = $this->vnp_Url . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        
        return $vnp_Url;
    }
    
    public function verifyResponse($data) {
        $vnp_SecureHash = $data['vnp_SecureHash'];
        unset($data['vnp_SecureHash']);
        
        ksort($data);
        $i = 0;
        $hashData = "";
        
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);
        return $secureHash === $vnp_SecureHash;
    }
}
?>