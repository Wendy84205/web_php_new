<?php
// views/modals/cart-modal.php
// Reusable cart modal
?>

<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Giỏ hàng của bạn</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="cart-items">
                    <!-- Cart items will be loaded here dynamically -->
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Giỏ hàng của bạn đang trống</p>
                        <a href="#menu" class="btn btn-primary" data-dismiss="modal">Xem thực đơn</a>
                    </div>
                </div>
                
                <div class="cart-summary" style="display: none;">
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span class="subtotal">0đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí giao hàng:</span>
                        <span class="delivery-fee">0đ</span>
                    </div>
                    <div class="summary-row grand-total">
                        <span>Tổng cộng:</span>
                        <span class="total">0đ</span>
                    </div>
                    
                    <div class="promo-code">
                        <input type="text" placeholder="Nhập mã giảm giá" class="form-control">
                        <button class="btn btn-apply">Áp dụng</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="display: none;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tiếp tục mua hàng</button>
                <a href="checkout.php" class="btn btn-primary">Thanh toán</a>
            </div>
        </div>
    </div>
</div>

<style>
#cartModal .modal-content {
    border-radius: 10px;
    border: none;
}

#cartModal .modal-header {
    border-bottom: none;
    padding-bottom: 0;
}

#cartModal .modal-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

#cartModal .close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 1.5rem;
}

.empty-cart {
    text-align: center;
    padding: 30px 0;
}

.empty-cart i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-cart p {
    color: #666;
    margin-bottom: 20px;
    font-size: 1.1rem;
}

.cart-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.cart-item-image {
    width: 80px;
    height: 80px;
    border-radius: 5px;
    overflow: hidden;
    margin-right: 15px;
}

.cart-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-details {
    flex: 1;
}

.cart-item-name {
    font-weight: bold;
    margin-bottom: 5px;
}

.cart-item-price {
    color: #e67e22;
    font-weight: bold;
}

.cart-item-actions {
    display: flex;
    align-items: center;
}

.quantity-control {
    display: flex;
    align-items: center;
    margin-right: 15px;
}

.quantity-control button {
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    background-color: white;
    font-size: 1rem;
    cursor: pointer;
}

.quantity-control input {
    width: 40px;
    height: 30px;
    text-align: center;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    border-left: none;
    border-right: none;
}

.remove-item {
    color: #f44336;
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
}

.cart-summary {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.summary-row.grand-total {
    font-weight: bold;
    font-size: 1.1rem;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.promo-code {
    display: flex;
    margin-top: 20px;
}

.promo-code input {
    flex: 1;
    height: 45px;
    border-radius: 5px 0 0 5px;
    border: 1px solid #ddd;
    padding: 0 15px;
}

.btn-apply {
    height: 45px;
    border-radius: 0 5px 5px 0;
    background-color: #333;
    color: white;
    border: none;
    padding: 0 20px;
    cursor: pointer;
}

.modal-footer {
    justify-content: space-between;
    border-top: none;
    padding-top: 0;
}

.btn-secondary {
    background-color: #f5f5f5;
    color: #333;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
}

.btn-primary {
    background-color: #e67e22;
    border-color: #e67e22;
    padding: 10px 20px;
    border-radius: 5px;
}

.btn-primary:hover {
    background-color: #d35400;
    border-color: #d35400;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // This would be connected to your actual cart functionality
    // For demo purposes, we'll just show/hide elements based on cart state
    
    function updateCartDisplay() {
        // In a real implementation, you would check the actual cart contents
        const cartIsEmpty = true; // Change this based on your cart state
        
        if (cartIsEmpty) {
            document.querySelector('.empty-cart').style.display = 'block';
            document.querySelector('.cart-summary').style.display = 'none';
            document.querySelector('.modal-footer').style.display = 'none';
        } else {
            document.querySelector('.empty-cart').style.display = 'none';
            document.querySelector('.cart-summary').style.display = 'block';
            document.querySelector('.modal-footer').style.display = 'flex';
            
            //Update totals based on cart items
            document.querySelector('.subtotal').textContent = formatCurrency(cart.subtotal);
            document.querySelector('.delivery-fee').textContent = formatCurrency(cart.deliveryFee);
            document.querySelector('.total').textContent = formatCurrency(cart.total);
        }
    }
    
    // Initial update
    updateCartDisplay();
    
    // Example cart item HTML structure (would be generated dynamically)
    /*
    <div class="cart-item">
        <div class="cart-item-image">
            <img src="path-to-image.jpg" alt="Item name">
        </div>
        <div class="cart-item-details">
            <div class="cart-item-name">Tên món ăn</div>
            <div class="cart-item-price">50,000đ</div>
        </div>
        <div class="cart-item-actions">
            <div class="quantity-control">
                <button class="decrement">-</button>
                <input type="number" value="1" min="1">
                <button class="increment">+</button>
            </div>
            <button class="remove-item">&times;</button>
        </div>
    </div>
    */
});
</script>