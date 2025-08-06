<?php
// views/components/order-summary.php
// Reusable order summary component

/**
 * @param array $order - Order data
 * @param array $items - Array of order items
 * @param bool $showActions - Whether to show action buttons
 */
?>

<div class="order-summary">
    <h3>Đơn hàng #<?= htmlspecialchars($order['order_number']) ?></h3>
    
    <div class="order-status">
        <span class="status-badge <?= strtolower($order['order_status']) ?>">
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['order_status']))) ?>
        </span>
        <span class="order-date"><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></span>
    </div>
    
    <div class="order-items">
        <?php foreach ($items as $item): ?>
            <div class="order-item">
                <div class="item-name">
                    <?= htmlspecialchars($item['name']) ?>
                    <span class="item-quantity">x<?= $item['quantity'] ?></span>
                </div>
                <div class="item-price">
                    <?= number_format($item['unit_price'] * $item['quantity'], 0, ',', '.') ?>đ
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="order-totals">
        <div class="total-row">
            <span>Tạm tính:</span>
            <span><?= number_format($order['subtotal'], 0, ',', '.') ?>đ</span>
        </div>
        <?php if ($order['discount_amount'] > 0): ?>
            <div class="total-row">
                <span>Giảm giá:</span>
                <span class="discount">-<?= number_format($order['discount_amount'], 0, ',', '.') ?>đ</span>
            </div>
        <?php endif; ?>
        <?php if ($order['delivery_fee'] > 0): ?>
            <div class="total-row">
                <span>Phí giao hàng:</span>
                <span><?= number_format($order['delivery_fee'], 0, ',', '.') ?>đ</span>
            </div>
        <?php endif; ?>
        <div class="total-row grand-total">
            <span>Tổng cộng:</span>
            <span><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</span>
        </div>
    </div>
    
    <?php if ($showActions): ?>
        <div class="order-actions">
            <?php if ($order['order_status'] === 'pending'): ?>
                <button class="btn cancel-order" data-order-id="<?= $order['order_id'] ?>">Hủy đơn hàng</button>
            <?php endif; ?>
            <a href="order-tracking.php?order_id=<?= $order['order_id'] ?>" class="btn track-order">Theo dõi đơn hàng</a>
        </div>
    <?php endif; ?>
</div>

<style>
.order-summary {
    background-color: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.order-summary h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #333;
}

.order-status {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.status-badge {
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: bold;
}

.status-badge.pending {
    background-color: #FFC107;
    color: #333;
}

.status-badge.confirmed, .status-badge.preparing, .status-badge.ready {
    background-color: #2196F3;
    color: white;
}

.status-badge.on_delivery {
    background-color: #673AB7;
    color: white;
}

.status-badge.delivered {
    background-color: #4CAF50;
    color: white;
}

.status-badge.cancelled {
    background-color: #F44336;
    color: white;
}

.order-date {
    color: #666;
    font-size: 0.9rem;
}

.order-items {
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.item-name {
    flex: 1;
    color: #333;
}

.item-quantity {
    color: #666;
    font-size: 0.9rem;
    margin-left: 5px;
}

.item-price {
    font-weight: bold;
    color: #333;
}

.order-totals {
    margin-bottom: 20px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #555;
}

.total-row.grand-total {
    font-size: 1.1rem;
    font-weight: bold;
    color: #333;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.discount {
    color: #4CAF50;
}

.order-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    flex: 1;
}

.cancel-order {
    background-color: #F44336;
    color: white;
    border: none;
}

.cancel-order:hover {
    background-color: #D32F2F;
}

.track-order {
    background-color: #2196F3;
    color: white;
    border: none;
}

.track-order:hover {
    background-color: #1976D2;
}
</style>