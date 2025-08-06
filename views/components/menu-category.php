<?php
// views/components/menu-category.php
// Reusable menu category component

/**
 * @param array $category - Menu category data
 * @param array $items - Array of menu items in this category
 * @param bool $isActive - Whether this category is currently active
 */
?>

<div class="menu-category" id="category-<?= $category['category_id'] ?>" style="<?= !$isActive ? 'display:none;' : '' ?>">
    <h3><?= htmlspecialchars($category['name']) ?></h3>
    <?php if (!empty($category['description'])): ?>
        <p class="category-desc"><?= htmlspecialchars($category['description']) ?></p>
    <?php endif; ?>
    
    <div class="menu-items">
        <?php foreach ($items as $item): ?>
            <?php include __DIR__ . '/menu-item-card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>

<style>
.menu-category {
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.menu-category h3 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 10px;
    text-align: center;
}

.category-desc {
    text-align: center;
    color: #666;
    margin-bottom: 30px;
    font-style: italic;
}

.menu-items {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
}
</style>