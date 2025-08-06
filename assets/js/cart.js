class ShoppingCart {
  constructor() {
    this.cartKey = 'foodCart';
    this.cart = this.loadCart();
    this.updateCartCount();
    this.initEvents();
    this.renderCart();
  }

  loadCart() {
    try {
      const cartData = localStorage.getItem(this.cartKey);
      return cartData ? JSON.parse(cartData) : [];
    } catch (e) {
      console.error('Error loading cart:', e);
      return [];
    }
  }

  saveCart() {
    localStorage.setItem(this.cartKey, JSON.stringify(this.cart));
    this.updateCartCount();
    this.renderCart();
  }

  addItem(item) {
    const existingItem = this.cart.find(i => i.id === item.id);
    if (existingItem) {
      existingItem.quantity += 1;
    } else {
      this.cart.push({
        id: item.id,
        name: item.name,
        price: item.price,
        quantity: 1,
        image: item.image || 'images/default-product.jpg'
      });
    }
    this.saveCart();
    this.showToast(`Đã thêm "${item.name}" vào giỏ hàng`);
  }

  removeItem(id) {
    this.cart = this.cart.filter(item => item.id !== id);
    this.saveCart();
  }

  updateQuantity(id, quantity) {
    const item = this.cart.find(item => item.id === id);
    if (item) {
      item.quantity = quantity;
      if (item.quantity <= 0) {
        this.removeItem(id);
      } else {
        this.saveCart();
      }
    }
  }

  clearCart() {
    this.cart = [];
    this.saveCart();
  }

  getTotalItems() {
    return this.cart.reduce((total, item) => total + item.quantity, 0);
  }

  getSubtotal() {
    return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
  }

  updateCartCount() {
    const count = this.getTotalItems();
    document.querySelectorAll('.cart-count').forEach(el => {
      el.textContent = count;
      el.style.display = count > 0 ? 'inline-block' : 'none';
    });
  }

  showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.innerHTML = `
      <i class="fas fa-check-circle"></i>
      <span>${message}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
  }

  renderCart() {
    const renderTargets = [
      { container: '.cart-items', emptyMessage: '.cart-empty-message' },
      { container: '#cart-container', emptyMessage: null }
    ];

    renderTargets.forEach(target => {
      const container = document.querySelector(target.container);
      if (!container) return;

      if (this.cart.length === 0) {
        if (target.emptyMessage) {
          document.querySelector(target.emptyMessage).style.display = 'block';
        }
        if (target.container === '#cart-container') {
          container.innerHTML = `
            <div class="alert alert-info">
              <i class="fas fa-shopping-cart"></i>
              <p>Giỏ hàng của bạn đang trống</p>
              <a href="index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
            </div>
          `;
        } else {
          container.innerHTML = '';
        }
      } else {
        if (target.emptyMessage) {
          document.querySelector(target.emptyMessage).style.display = 'none';
        }

        const itemsHTML = this.cart.map(item => `
          <div class="cart-item" data-id="${item.id}">
            <div class="cart-item-img">
              <img src="${item.image}" alt="${item.name}" onerror="this.src='images/default-product.jpg'">
            </div>
            <div class="cart-item-details">
              <h4>${item.name}</h4>
              <div class="cart-item-price">${item.price.toLocaleString()}₫</div>
              <div class="cart-item-quantity">
                <button class="quantity-btn minus">-</button>
                <input type="number" value="${item.quantity}" min="1">
                <button class="quantity-btn plus">+</button>
              </div>
            </div>
            <button class="cart-item-remove">
              <i class="fas fa-trash"></i>
            </button>
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
            <button class="btn btn-outline-secondary cart-clear-btn">
              <i class="fas fa-trash"></i> Xóa giỏ hàng
            </button>
            <button class="btn btn-primary cart-checkout-btn">
              <i class="fas fa-credit-card"></i> Thanh toán
            </button>
          </div>
        `;

        if (target.container === '#cart-container') {
          container.innerHTML = `
            <div class="cart-items-list">
              ${itemsHTML}
              ${summaryHTML}
            </div>
          `;
        } else {
          container.innerHTML = itemsHTML;
          const summary = document.querySelector('.cart-summary');
          if (summary) summary.innerHTML = summaryHTML;
        }
      }
    });
  }

  initEvents() {
    document.addEventListener('click', (e) => {
      if (e.target.closest('.add-to-cart')) {
        const button = e.target.closest('.add-to-cart');
        const productCard = button.closest('.menu-item-card');

        const product = {
          id: productCard.dataset.id,
          name: productCard.querySelector('.product-name').textContent.trim(),
          price: parseInt(button.dataset.price || productCard.querySelector('.product-price').dataset.price),
          image: productCard.querySelector('.product-image')?.src || ''
        };

        this.addItem(product);
      }

      if (e.target.closest('.cart-item-remove')) {
        const itemId = e.target.closest('.cart-item').dataset.id;
        this.removeItem(itemId);
      }

      if (e.target.closest('.quantity-btn')) {
        const itemElement = e.target.closest('.cart-item');
        const itemId = itemElement.dataset.id;
        const input = itemElement.querySelector('input');
        let quantity = parseInt(input.value);

        if (e.target.classList.contains('plus')) {
          quantity += 1;
        } else if (e.target.classList.contains('minus')) {
          quantity -= 1;
        }

        input.value = quantity > 0 ? quantity : 1;
        this.updateQuantity(itemId, quantity);
      }

      if (e.target.matches('.cart-item-quantity input')) {
        const itemElement = e.target.closest('.cart-item');
        const itemId = itemElement.dataset.id;
        const quantity = parseInt(e.target.value) || 1;
        this.updateQuantity(itemId, quantity);
      }

      if (e.target.closest('.cart-clear-btn')) {
        if (confirm('Bạn có chắc chắn muốn xóa toàn bộ giỏ hàng?')) {
          this.clearCart();
        }
      }
    });

    document.querySelector('.cart-toggle')?.addEventListener('click', () => {
      document.querySelector('.modal-overlay').classList.add('active');
    });

    document.querySelector('.modal-overlay .close')?.addEventListener('click', () => {
      document.querySelector('.modal-overlay').classList.remove('active');
    });
  }

  getCartData() {
    return this.cart;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  window.cart = new ShoppingCart();

  // ✅ Gắn nút sau khi giỏ hàng đã khởi tạo
  document.addEventListener('click', function(e) {
    if (e.target.closest('.cart-checkout-btn')) {
      e.preventDefault();
      handleCheckout();
    }
  });
});


async function handleCheckout() {
  if (!window.cart || typeof window.cart.getCartData !== 'function') {
    alert('Hệ thống giỏ hàng chưa sẵn sàng.');
    return;
  }

  const cartData = window.cart.getCartData();
  if (!cartData || cartData.length === 0) {
    alert('Giỏ hàng trống!');
    return;
  }

  try {
    const res = await fetch('api/update-cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cart: cartData })
    });

    const result = await res.json();
    if (result.status === 'ok') {
      window.location.href = 'checkout.php';
    } else {
      alert('Không thể cập nhật giỏ hàng. Vui lòng thử lại.');
    }
  } catch (err) {
    console.error('Checkout error:', err);
    alert('Lỗi hệ thống. Vui lòng thử lại sau.');
  }
}
// Thêm vào cuối file cart.js
document.addEventListener('DOMContentLoaded', () => {
  window.cart = new ShoppingCart();

  // Gắn sự kiện cho nút Thanh toán
  document.addEventListener('click', function(e) {
    if (e.target.closest('.cart-checkout-btn')) {
      e.preventDefault();
      handleCheckout();
    }
  });
});

async function handleCheckout() {
  if (!window.cart || typeof window.cart.getCartData !== 'function') {
    alert('Hệ thống giỏ hàng chưa sẵn sàng.');
    return;
  }

  const cartData = window.cart.getCartData();
  if (!cartData || cartData.length === 0) {
    alert('Giỏ hàng trống!');
    return;
  }

  try {
    const res = await fetch('api/update-cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cart: cartData })
    });

    const result = await res.json();
    if (result.status === 'ok') {
      window.location.href = 'checkout.php';
    } else {
      alert('Không thể cập nhật giỏ hàng: ' + (result.error || 'Lỗi không xác định'));
    }
  } catch (err) {
    console.error('Checkout error:', err);
    alert('Lỗi hệ thống. Vui lòng thử lại sau.');
  }
}