<?php
require_once 'includes/init.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=cart');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/cart.js" defer></script> <!-- RẤT QUAN TRỌNG -->
    <style>
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .cart-item-img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
        }

        .cart-item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .cart-item-price {
            color: #e67e22;
            font-weight: bold;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background: #f8f9fa;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
        }

        .cart-summary {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .cart-summary-row.total {
            font-weight: bold;
            font-size: 1.1rem;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }

        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Giỏ hàng của bạn</h1>
        <div id="cart-container"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.cart) {
                console.error('Cart system not initialized');
                return;
            }

            // Render lại cart để hiển thị trong #cart-container
            window.cart.renderCart = function () {
                const container = document.getElementById('cart-container');

                if (!container) return;

                if (this.cart.length === 0) {
                    container.innerHTML = `
        <div class="alert alert-info">
          <i class="fas fa-shopping-cart"></i>
          <p>Giỏ hàng của bạn đang trống</p>
          <a href="index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>`;
                    return;
                }

                const itemsHTML = this.cart.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <div class="cart-item-info">
          <div class="cart-item-img">
            <img src="${item.image || 'images/default-product.jpg'}" alt="${item.name}">
          </div>
          <div>
            <div class="cart-item-title">${item.name}</div>
            <div class="cart-item-price">${item.price.toLocaleString()}₫</div>
          </div>
        </div>
        <div class="cart-item-quantity">
          <button class="quantity-btn minus">-</button>
          <input type="number" value="${item.quantity}" min="1">
          <button class="quantity-btn plus">+</button>
        </div>
      </div>
    `).join('');

                const summaryHTML = `
      <div class="cart-summary">
        <div class="cart-summary-row">
          <span>Tạm tính:</span>
          <span class="cart-subtotal">${this.getSubtotal().toLocaleString()}₫</span>
        </div>
        <div class="cart-summary-row">
          <span>Phí vận chuyển:</span>
          <span class="cart-shipping">0₫</span>
        </div>
        <div class="cart-summary-row total">
          <span>Tổng cộng:</span>
          <span class="cart-total">${this.getSubtotal().toLocaleString()}₫</span>
        </div>
      </div>
      <div class="cart-actions">
        <button class="btn btn-outline-danger cart-clear-btn">
          <i class="fas fa-trash"></i> Xóa giỏ hàng
        </button>
        <button type="button" class="btn btn-primary cart-checkout-btn">
  Thanh toán
</button>

      </div>`;

                container.innerHTML = `
      <div class="cart-items-list">
        ${itemsHTML}
      </div>
      ${summaryHTML}`;
            };

            // Render giỏ hàng ban đầu
            window.cart.renderCart();

            // Xử lý sự kiện
            document.getElementById('cart-container').addEventListener('click', function (e) {
                const target = e.target;

                if (target.closest('.btn-checkout')) {
                    handleCheckout();
                } else if (target.closest('.cart-clear-btn')) {
                    if (confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) {
                        window.cart.clearCart();
                    }
                } else if (target.closest('.quantity-btn.plus')) {
                    const input = target.closest('.cart-item-quantity').querySelector('input');
                    input.value = parseInt(input.value) + 1;
                    updateQuantity(input);
                } else if (target.closest('.quantity-btn.minus')) {
                    const input = target.closest('.cart-item-quantity').querySelector('input');
                    input.value = Math.max(1, parseInt(input.value) - 1);
                    updateQuantity(input);
                } else if (target.matches('.cart-item-quantity input')) {
                    updateQuantity(target);
                }
            });

            function updateQuantity(input) {
                const itemId = input.closest('.cart-item').dataset.id;
                const quantity = parseInt(input.value) || 1;
                window.cart.updateQuantity(itemId, quantity);
            }

            async function handleCheckout() {
                if (window.cart.getCartData().length === 0) {
                    alert('Giỏ hàng của bạn đang trống!');
                    return;
                }

                try {
                    const response = await fetch('api/update-cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cart: window.cart.getCartData() })
                    });

                    if (!response.ok) throw new Error('Lỗi khi cập nhật giỏ hàng');

                    window.location.href = 'checkout.php';
                } catch (error) {
                    console.error('Checkout error:', error);
                    alert('Có lỗi xảy ra khi xử lý thanh toán. Vui lòng thử lại.');
                }
            }
        });
    </script>
    <?php require_once 'includes/footer.php'; ?>