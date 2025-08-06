<?php
class MomoPayment {
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint;
    
    public function __construct() {
        // Lấy thông tin cấu hình từ database hoặc config
        $this->partnerCode = 'MOMO123456';
        $this->accessKey = 'AK123456789';
        $this->secretKey = 'SK123456789';
        $this->endpoint = 'https://payment.momo.vn/gw_payment/transactionProcessor';
    }
    
    public function createPayment($orderId, $amount, $returnUrl, $notifyUrl) {
        $requestId = time() . '';
        $orderInfo = "Thanh toán đơn hàng #$orderId";
        $extraData = '';
        
        $rawHash = "partnerCode=" . $this->partnerCode .
                   "&accessKey=" . $this->accessKey .
                   "&requestId=" . $requestId .
                   "&amount=" . $amount .
                   "&orderId=" . $orderId .
                   "&orderInfo=" . $orderInfo .
                   "&returnUrl=" . $returnUrl .
                   "&notifyUrl=" . $notifyUrl .
                   "&extraData=" . $extraData;
        
        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);
        
        $data = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'returnUrl' => $returnUrl,
            'notifyUrl' => $notifyUrl,
            'extraData' => $extraData,
            'requestType' => 'captureMoMoWallet',
            'signature' => $signature
        ];
        
        return $this->sendRequest($data);
    }
    
    private function sendRequest($data) {
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    public function verifySignature($data, $signature) {
        $rawHash = "partnerCode=" . $data['partnerCode'] .
                   "&accessKey=" . $data['accessKey'] .
                   "&requestId=" . $data['requestId'] .
                   "&amount=" . $data['amount'] .
                   "&orderId=" . $data['orderId'] .
                   "&orderInfo=" . $data['orderInfo'] .
                   "&orderType=" . $data['orderType'] .
                   "&transId=" . $data['transId'] .
                   "&message=" . $data['message'] .
                   "&localMessage=" . $data['localMessage'] .
                   "&responseTime=" . $data['responseTime'] .
                   "&errorCode=" . $data['errorCode'] .
                   "&payType=" . $data['payType'] .
                   "&extraData=" . $data['extraData'];
        
        $expectedSignature = hash_hmac('sha256', $rawHash, $this->secretKey);
        
        return $expectedSignature === $signature;
    }
}
?>