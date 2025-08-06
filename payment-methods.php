<?php
require_once 'includes/header.php';

// Kiểm tra giỏ hàng và đăng nhập
if (empty($_SESSION['cart']) {
    header('Location: cart.php');
    exit();
}
?>

<div class="container py-5">
    <h2 class="mb-4">Chọn phương thức thanh toán</h2>
    
    <div class="row">
        <!-- Phương thức Momo -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <img src="assets/images/momo-logo.png" alt="Momo" class="img-fluid mb-3" style="height: 50px;">
                    <h4>Ví điện tử Momo</h4>
                    <p>Thanh toán nhanh chóng qua ứng dụng Momo</p>
                    <form action="process-payment.php" method="post">
                        <input type="hidden" name="payment_method" value="momo">
                        <button type="submit" class="btn btn-primary">Chọn Momo</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Phương thức VNPay -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <img src="assets/images/vnpay-logo.png" alt="VNPay" class="img-fluid mb-3" style="height: 50px;">
                    <h4>VNPay</h4>
                    <p>Thanh toán qua thẻ ngân hàng nội địa</p>
                    <form action="process-payment.php" method="post">
                        <input type="hidden" name="payment_method" value="vnpay">
                        <button type="submit" class="btn btn-primary">Chọn VNPay</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <a href="checkout.php" class="btn btn-outline-secondary">Quay lại</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>