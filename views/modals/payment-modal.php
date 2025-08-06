<?php
// Kiểm tra xem người dùng đã đăng nhập chưa
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Lấy thông tin giỏ hàng từ session
$cart = $_SESSION['cart'] ?? [];
$cartTotal = calculateCartTotal($cart);
?>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Chọn phương thức thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" action="/checkout.php" method="POST">
                    <input type="hidden" name="action" value="process_payment">
                    
                    <div class="order-summary mb-4">
                        <h6 class="fw-bold">Tổng đơn hàng:</h6>
                        <div class="d-flex justify-content-between">
                            <span>Tạm tính:</span>
                            <span class="fw-bold"><?= formatCurrency($cartTotal['subtotal']) ?></span>
                        </div>
                        <?php if ($cartTotal['discount'] > 0): ?>
                        <div class="d-flex justify-content-between text-success">
                            <span>Giảm giá:</span>
                            <span class="fw-bold">-<?= formatCurrency($cartTotal['discount']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <span>Phí giao hàng:</span>
                            <span class="fw-bold"><?= formatCurrency($cartTotal['delivery_fee']) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5">
                            <span>Tổng cộng:</span>
                            <span class="fw-bold text-primary"><?= formatCurrency($cartTotal['total']) ?></span>
                        </div>
                    </div>
                    
                    <div class="payment-options mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="cashOnDelivery" value="cash" checked>
                            <label class="form-check-label d-flex align-items-center" for="cashOnDelivery">
                                <img src="/assets/images/payments/cash.png" alt="Tiền mặt" class="me-2" width="30">
                                Thanh toán khi nhận hàng (COD)
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="momoPayment" value="momo">
                            <label class="form-check-label d-flex align-items-center" for="momoPayment">
                                <img src="/assets/images/payments/momo.png" alt="Momo" class="me-2" width="30">
                                Ví điện tử Momo
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="vnpayPayment" value="vnpay">
                            <label class="form-check-label d-flex align-items-center" for="vnpayPayment">
                                <img src="/assets/images/payments/vnpay.png" alt="VNPay" class="me-2" width="30">
                                VNPay
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="bankTransfer" value="bank_transfer">
                            <label class="form-check-label d-flex align-items-center" for="bankTransfer">
                                <img src="/assets/images/payments/bank-transfer.png" alt="Chuyển khoản" class="me-2" width="30">
                                Chuyển khoản ngân hàng
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            Tôi đồng ý với <a href="/terms.php" target="_blank">điều khoản và điều kiện</a> của Com Nieu
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check-circle me-2"></i> Xác nhận thanh toán
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');
    
    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!document.getElementById('agreeTerms').checked) {
            alert('Vui lòng đồng ý với điều khoản và điều kiện trước khi tiếp tục.');
            return;
        }
        
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        // Nếu là thanh toán online, chuyển hướng đến trang thanh toán tương ứng
        if (paymentMethod === 'momo' || paymentMethod === 'vnpay') {
            window.location.href = `/payment-gateways/${paymentMethod}/init.php?amount=<?= $cartTotal['total'] ?>`;
        } else {
            // Gửi form cho thanh toán COD hoặc chuyển khoản
            paymentForm.submit();
        }
    });
});
</script>