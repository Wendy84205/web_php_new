<?php
// views/components/menu-item-card.php
// Reusable menu item card component

/**
 * @param array $item - Menu item data
 */
?>

<div class="menu-item" data-item-id="<?= $item['item_id'] ?>">
    <div class="item-image">
        <img src="<?= htmlspecialchars($item['image_url'] ?? '../assets/images/menu-items/default.jpg') ?>" 
             alt="<?= htmlspecialchars($item['name']) ?>">
        <?php if ($item['is_vegetarian']): ?>
            <span class="veg-badge">Chay</span>
        <?php endif; ?>
        <?php if ($item['is_spicy']): ?>
            <span class="spicy-badge">Cay</span>
        <?php endif; ?>
    </div>
    <div class="item-details">
        <h4><?= htmlspecialchars($item['name']) ?></h4>
        <p class="item-desc"><?= htmlspecialchars($item['description']) ?></p>
        <div class="item-price">
            <?php if ($item['discounted_price']): ?>
                <span class="original-price"><?= number_format($item['price'], 0, ',', '.') ?>đ</span>
                <span class="discounted-price"><?= number_format($item['discounted_price'], 0, ',', '.') ?>đ</span>
            <?php else: ?>
                <span><?= number_format($item['price'], 0, ',', '.') ?>đ</span>
            <?php endif; ?>
        </div>
        <button class="add-to-cart" data-item-id="<?= $item['item_id'] ?>">
            Thêm vào giỏ
        </button>
    </div>
</div>

<style>
.menu-item {
    border: 1px solid #eee;
    border-radius: 10px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.menu-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.item-image {
    height: 200px;
    overflow: hidden;
    position: relative;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.menu-item:hover .item-image img {
    transform: scale(1.05);
}

.veg-badge, .spicy-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    color: white;
}

.veg-badge {
    background-color: #4CAF50;
}

.spicy-badge {
    background-color: #F44336;
}

.item-details {
    padding: 20px;
}

.item-details h4 {
    font-size: 1.3rem;
    margin-bottom: 10px;
    color: #333;
}

.item-desc {
    color: #666;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.item-price {
    font-weight: bold;
    font-size: 1.2rem;
    color: #e67e22;
    margin-bottom: 15px;
}

.original-price {
    text-decoration: line-through;
    color: #999;
    margin-right: 10px;
    font-size: 1rem;
}

.add-to-cart {
    width: 100%;
    padding: 10px;
    background-color: #e67e22;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.add-to-cart:hover {
    background-color: #d35400;
}
</style>