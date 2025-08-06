<?php
// menu.php - Menu section
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = Database::getInstance()->getConnection();

    $categories = $pdo->query("SELECT * FROM menu_categories WHERE is_active = 1")->fetchAll();

    $menuItems = [];
    foreach ($categories as $category) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? AND is_available = TRUE ORDER BY display_order");
        $stmt->execute([$category['category_id']]);
        $menuItems[$category['category_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("<div class='error'>Lỗi database: " . htmlspecialchars($e->getMessage()) . "</div>");
}
?>

<section class="menu-section" id="menu">
    <div class="container">
        <div class="section-header" style="position: relative;">
            <h2>THỰC ĐƠN</h2>
            <p>Khám phá hương vị truyền thống Việt Nam</p>
            <!-- Cart toggle icon -->
            <button class="cart-toggle" aria-label="Mở giỏ hàng"
                style="position: absolute; top:0; right:0; background:none; border:none; cursor:pointer; font-size:1.2rem;">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"
                    style="background:#e67e22; color:#fff; border-radius:50%; padding:2px 6px; font-size:0.8rem; display:none; margin-left:4px;">0</span>
            </button>
        </div>

        <div class="menu-tabs">
            <?php foreach ($categories as $category): ?>
                <button class="tab-btn" data-category="<?= htmlspecialchars($category['category_id']) ?>">
                    <?= htmlspecialchars($category['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="menu-content">
            <?php foreach ($categories as $category): ?>
                <div class="menu-category" id="category-<?= htmlspecialchars($category['category_id']) ?>"
                    style="<?= $category['category_id'] !== $categories[0]['category_id'] ? 'display:none;' : '' ?>">
                    <h3><?= htmlspecialchars($category['name']) ?></h3>
                    <p class="category-desc"><?= htmlspecialchars($category['description']) ?></p>

                    <div class="menu-items">
                        <?php foreach ($menuItems[$category['category_id']] as $item): ?>
                            <div class="menu-item-card" data-id="<?= htmlspecialchars($item['item_id']) ?>">
                                <div class="menu-item-img">
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>" class="product-image"
                                        onerror="this.src='images/default-product.jpg'">
                                </div>
                                <div class="menu-item-body">
                                    <h4 class="menu-item-title product-name"><?= htmlspecialchars($item['name']) ?></h4>
                                    <div class="price">
                                        <span class="current-price product-price"
                                            data-price="<?= htmlspecialchars($item['price']) ?>">
                                            <?= number_format($item['price'], 0, ',', '.') ?>₫
                                        </span>
                                    </div>
                                    <button class="add-to-cart btn btn-primary"
                                        data-id="<?= htmlspecialchars($item['item_id']) ?>"
                                        data-price="<?= htmlspecialchars($item['price']) ?>">
                                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Nhúng script cart.js (nếu chưa nhúng ở layout chung) -->
<script src="assets/js/cart.js" defer></script>

<!-- Mở modal tự động khi nhấn thêm vào giỏ -->
<script>
    document.addEventListener('click', function (e) {
        if (e.target.closest('.add-to-cart')) {
            // Chờ một chút để cart.js xử lý thêm rồi mở modal
            setTimeout(() => {
                document.querySelector('.modal-overlay')?.classList.add('active');
            }, 100);
        }
    });

    // Tab switching (nếu chưa có logic khác)
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const cat = this.dataset.category;
            document.querySelectorAll('.menu-category').forEach(c => c.style.display = 'none');
            document.querySelector(`#category-${cat}`)?.removeAttribute('style');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
</script>

<style>
    /* Menu styles */
    .menu-section {
        padding: 80px 0;
        background-color: #fff9f5;
    }

    .menu-tabs {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
    }

    .tab-btn {
        padding: 8px 20px;
        background: #f8f9fa;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }

    .tab-btn:hover,
    .tab-btn.active {
        background: #e67e22;
        color: white;
    }

    .menu-items {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    .menu-item-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .menu-item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }

    .menu-item-img {
        height: 200px;
        overflow: hidden;
    }

    .menu-item-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }

    .menu-item-card:hover .menu-item-img img {
        transform: scale(1.05);
    }

    .menu-item-body {
        padding: 20px;
    }

    .menu-item-title {
        font-size: 1.2rem;
        margin-bottom: 10px;
        color: #333;
    }

    .menu-item-desc {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
        min-height: 40px;
    }

    .menu-item-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .price {
        font-weight: bold;
    }

    .current-price {
        color: #e67e22;
        font-size: 1.1rem;
    }

    .original-price {
        text-decoration: line-through;
        color: #999;
        font-size: 0.9rem;
        margin-right: 8px;
    }

    /* Cart modal styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
    }

    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .cart-modal {
        position: fixed;
        top: 0;
        right: 0;
        width: 100%;
        max-width: 400px;
        height: 100vh;
        background: white;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        transform: translateX(100%);
        transition: transform 0.3s;
        display: flex;
        flex-direction: column;
    }

    .modal-overlay.active .cart-modal {
        transform: translateX(0);
    }

    .cart-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .cart-header h3 {
        margin: 0;
        font-size: 1.3rem;
    }

    .cart-header .close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .cart-items-container {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .cart-empty-message {
        text-align: center;
        padding: 40px 0;
        color: #666;
    }

    .cart-summary {
        padding: 20px;
        border-top: 1px solid #eee;
    }

    .cart-summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .cart-summary-row.total {
        font-weight: bold;
        font-size: 1.1rem;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .cart-actions {
        display: flex;
        padding: 15px;
        gap: 10px;
        border-top: 1px solid #eee;
    }

    .cart-actions .btn {
        flex: 1;
    }

    /* Toast notification */
    .cart-toast {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 12px 24px;
        border-radius: 4px;
        z-index: 1001;
        animation: fadeInOut 2s ease-in-out;
    }

    @keyframes fadeInOut {
        0% {
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 1;
        }

        100% {
            opacity: 0;
        }
    }
</style>