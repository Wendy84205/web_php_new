<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = getDBConnection();
        
        if (isset($_POST['add_category'])) {
            // Add new category
            $stmt = $db->prepare("
                INSERT INTO menu_categories (name, description, image_url, display_order, is_active)
                VALUES (:name, :description, :image_url, :display_order, :is_active)
            ");
            
            $stmt->execute([
                ':name' => $_POST['name'],
                ':description' => $_POST['description'],
                ':image_url' => $_POST['image_url'],
                ':display_order' => $_POST['display_order'],
                ':is_active' => isset($_POST['is_active']) ? 1 : 0
            ]);
            
            $_SESSION['success'] = "Category added successfully!";
        } 
        elseif (isset($_POST['update_category'])) {
            // Update category
            $stmt = $db->prepare("
                UPDATE menu_categories 
                SET name = :name,
                    description = :description,
                    image_url = :image_url,
                    display_order = :display_order,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE category_id = :category_id
            ");
            
            $stmt->execute([
                ':category_id' => $_POST['category_id'],
                ':name' => $_POST['name'],
                ':description' => $_POST['description'],
                ':image_url' => $_POST['image_url'],
                ':display_order' => $_POST['display_order'],
                ':is_active' => isset($_POST['is_active']) ? 1 : 0
            ]);
            
            $_SESSION['success'] = "Category updated successfully!";
        }
        elseif (isset($_POST['add_item'])) {
            // Add new menu item
            $stmt = $db->prepare("
                INSERT INTO menu_items 
                (category_id, name, description, price, discounted_price, image_url, 
                 is_vegetarian, is_spicy, is_available, display_order)
                VALUES 
                (:category_id, :name, :description, :price, :discounted_price, :image_url,
                 :is_vegetarian, :is_spicy, :is_available, :display_order)
            ");
            
            $stmt->execute([
                ':category_id' => $_POST['category_id'],
                ':name' => $_POST['name'],
                ':description' => $_POST['description'],
                ':price' => $_POST['price'],
                ':discounted_price' => !empty($_POST['discounted_price']) ? $_POST['discounted_price'] : null,
                ':image_url' => $_POST['image_url'],
                ':is_vegetarian' => isset($_POST['is_vegetarian']) ? 1 : 0,
                ':is_spicy' => isset($_POST['is_spicy']) ? 1 : 0,
                ':is_available' => isset($_POST['is_available']) ? 1 : 0,
                ':display_order' => $_POST['display_order']
            ]);
            
            $_SESSION['success'] = "Menu item added successfully!";
        }
        elseif (isset($_POST['update_item'])) {
            // Update menu item
            $stmt = $db->prepare("
                UPDATE menu_items 
                SET category_id = :category_id,
                    name = :name,
                    description = :description,
                    price = :price,
                    discounted_price = :discounted_price,
                    image_url = :image_url,
                    is_vegetarian = :is_vegetarian,
                    is_spicy = :is_spicy,
                    is_available = :is_available,
                    display_order = :display_order,
                    updated_at = CURRENT_TIMESTAMP
                WHERE item_id = :item_id
            ");
            
            $stmt->execute([
                ':item_id' => $_POST['item_id'],
                ':category_id' => $_POST['category_id'],
                ':name' => $_POST['name'],
                ':description' => $_POST['description'],
                ':price' => $_POST['price'],
                ':discounted_price' => !empty($_POST['discounted_price']) ? $_POST['discounted_price'] : null,
                ':image_url' => $_POST['image_url'],
                ':is_vegetarian' => isset($_POST['is_vegetarian']) ? 1 : 0,
                ':is_spicy' => isset($_POST['is_spicy']) ? 1 : 0,
                ':is_available' => isset($_POST['is_available']) ? 1 : 0,
                ':display_order' => $_POST['display_order']
            ]);
            
            $_SESSION['success'] = "Menu item updated successfully!";
        }
        
        header("Location: menu-management.php");
        exit();
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Error processing your request. Please try again.";
        header("Location: menu-management.php");
        exit();
    }
}

// Handle delete actions
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    try {
        $db = getDBConnection();
        
        if (isset($_GET['category_id'])) {
            // Check if category has items
            $stmt = $db->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
            $stmt->execute([$_GET['category_id']]);
            $item_count = $stmt->fetchColumn();
            
            if ($item_count > 0) {
                $_SESSION['error'] = "Cannot delete category that has menu items. Please delete or move the items first.";
            } else {
                $stmt = $db->prepare("DELETE FROM menu_categories WHERE category_id = ?");
                $stmt->execute([$_GET['category_id']]);
                $_SESSION['success'] = "Category deleted successfully!";
            }
        } 
        elseif (isset($_GET['item_id'])) {
            $stmt = $db->prepare("DELETE FROM menu_items WHERE item_id = ?");
            $stmt->execute([$_GET['item_id']]);
            $_SESSION['success'] = "Menu item deleted successfully!";
        }
        
        header("Location: menu-management.php");
        exit();
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Error deleting item. Please try again.";
        header("Location: menu-management.php");
        exit();
    }
}

// Get all categories and items
$categories = [];
$items_by_category = [];

try {
    $db = getDBConnection();
    
    // Get all categories ordered by display order
    $stmt = $db->query("
        SELECT * FROM menu_categories 
        ORDER BY display_order, name
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all items grouped by category
    $stmt = $db->query("
        SELECT mi.*, mc.name AS category_name
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.category_id
        ORDER BY mi.category_id, mi.display_order, mi.name
    ");
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group items by category
    foreach ($items as $item) {
        $items_by_category[$item['category_id']][] = $item;
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading menu data. Please try again.";
}

// Page title
$page_title = "Menu Management";

// Include header
include 'header.php';
?>

<div class="container mt-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Menu Categories</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle"></i> Add Category
            </button>
        </div>
    </div>
    
    <div class="card mb-5">
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <div class="alert alert-info">No categories found. Add your first category to get started.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Items</th>
                                <th>Display Order</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <?php if ($category['image_url']): ?>
                                            <img src="<?= htmlspecialchars($category['image_url']) ?>" 
                                                 alt="<?= htmlspecialchars($category['name']) ?>" 
                                                 class="img-thumbnail me-2" style="width: 50px; height: 50px;">
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($category['name']) ?></strong>
                                    </td>
                                    <td>
                                        <?= isset($items_by_category[$category['category_id']]) ? count($items_by_category[$category['category_id']]) : 0 ?>
                                    </td>
                                    <td><?= $category['display_order'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-category-btn" 
                                                data-category-id="<?= $category['category_id'] ?>"
                                                data-name="<?= htmlspecialchars($category['name']) ?>"
                                                data-description="<?= htmlspecialchars($category['description']) ?>"
                                                data-image-url="<?= htmlspecialchars($category['image_url']) ?>"
                                                data-display-order="<?= $category['display_order'] ?>"
                                                data-is-active="<?= $category['is_active'] ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <a href="menu-management.php?action=delete&category_id=<?= $category['category_id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this category?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Menu Items</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="bi bi-plus-circle"></i> Add Item
            </button>
        </div>
    </div>
    
    <?php if (empty($categories)): ?>
        <div class="alert alert-warning">
            You need to create at least one category before you can add menu items.
        </div>
    <?php else: ?>
        <div class="accordion" id="menuItemsAccordion">
            <?php foreach ($categories as $category): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $category['category_id'] ?>">
                        <button class="accordion-button <?= empty($items_by_category[$category['category_id']]) ? 'collapsed' : '' ?>" 
                                type="button" data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?= $category['category_id'] ?>" 
                                aria-expanded="<?= empty($items_by_category[$category['category_id']]) ? 'false' : 'true' ?>" 
                                aria-controls="collapse<?= $category['category_id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                            <span class="badge bg-primary ms-2">
                                <?= isset($items_by_category[$category['category_id']]) ? count($items_by_category[$category['category_id']]) : 0 ?>
                            </span>
                        </button>
                    </h2>
                    <div id="collapse<?= $category['category_id'] ?>" 
                         class="accordion-collapse collapse <?= empty($items_by_category[$category['category_id']]) ? '' : 'show' ?>" 
                         aria-labelledby="heading<?= $category['category_id'] ?>">
                        <div class="accordion-body">
                            <?php if (empty($items_by_category[$category['category_id']])): ?>
                                <div class="alert alert-info">No items in this category.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($items_by_category[$category['category_id']] as $item): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card h-100">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                         class="card-img-top" 
                                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                                         style="height: 180px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <?= htmlspecialchars($item['name']) ?>
                                                        <?php if ($item['is_vegetarian']): ?>
                                                            <span class="badge bg-success">Vegetarian</span>
                                                        <?php endif; ?>
                                                        <?php if ($item['is_spicy']): ?>
                                                            <span class="badge bg-danger">Spicy</span>
                                                        <?php endif; ?>
                                                    </h5>
                                                    <p class="card-text text-muted small">
                                                        <?= nl2br(htmlspecialchars($item['description'])) ?>
                                                    </p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <?php if ($item['discounted_price'] && $item['discounted_price'] < $item['price']): ?>
                                                                <span class="text-decoration-line-through text-muted">
                                                                    <?= number_format($item['price'], 0) ?>₫
                                                                </span>
                                                                <span class="text-danger fw-bold ms-2">
                                                                    <?= number_format($item['discounted_price'], 0) ?>₫
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="fw-bold">
                                                                    <?= number_format($item['price'], 0) ?>₫
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="badge bg-<?= $item['is_available'] ? 'success' : 'secondary' ?>">
                                                            <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-footer bg-transparent">
                                                    <button class="btn btn-sm btn-outline-primary edit-item-btn"
                                                            data-item-id="<?= $item['item_id'] ?>"
                                                            data-category-id="<?= $item['category_id'] ?>"
                                                            data-name="<?= htmlspecialchars($item['name']) ?>"
                                                            data-description="<?= htmlspecialchars($item['description']) ?>"
                                                            data-price="<?= $item['price'] ?>"
                                                            data-discounted-price="<?= $item['discounted_price'] ?>"
                                                            data-image-url="<?= htmlspecialchars($item['image_url']) ?>"
                                                            data-is-vegetarian="<?= $item['is_vegetarian'] ?>"
                                                            data-is-spicy="<?= $item['is_spicy'] ?>"
                                                            data-is-available="<?= $item['is_available'] ?>"
                                                            data-display-order="<?= $item['display_order'] ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <a href="menu-management.php?action=delete&item_id=<?= $item['item_id'] ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this menu item?')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="menu-management.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="add_category" value="1">
                    
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoryImageUrl" class="form-label">Image URL</label>
                        <input type="url" class="form-control" id="categoryImageUrl" name="image_url">
                        <small class="text-muted">Enter the URL of the category image</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="categoryDisplayOrder" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="categoryDisplayOrder" name="display_order" value="0">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="categoryIsActive" name="is_active" checked>
                                <label class="form-check-label" for="categoryIsActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="menu-management.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="update_category" value="1">
                    <input type="hidden" name="category_id" id="editCategoryId">
                    
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="editCategoryName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editCategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editCategoryDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editCategoryImageUrl" class="form-label">Image URL</label>
                        <input type="url" class="form-control" id="editCategoryImageUrl" name="image_url">
                        <small class="text-muted">Enter the URL of the category image</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editCategoryDisplayOrder" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="editCategoryDisplayOrder" name="display_order">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="editCategoryIsActive" name="is_active">
                                <label class="form-check-label" for="editCategoryIsActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">Add New Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="menu-management.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="add_item" value="1">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="itemCategory" class="form-label">Category *</label>
                            <select class="form-select" id="itemCategory" name="category_id" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="itemDisplayOrder" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="itemDisplayOrder" name="display_order" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="itemName" class="form-label">Item Name *</label>
                        <input type="text" class="form-control" id="itemName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="itemDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="itemDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="itemPrice" class="form-label">Price *</label>
                            <input type="number" class="form-control" id="itemPrice" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="itemDiscountedPrice" class="form-label">Discounted Price</label>
                            <input type="number" class="form-control" id="itemDiscountedPrice" name="discounted_price" min="0" step="0.01">
                            <small class="text-muted">Leave empty if no discount</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="itemImageUrl" class="form-label">Image URL</label>
                        <input type="url" class="form-control" id="itemImageUrl" name="image_url">
                        <small class="text-muted">Enter the URL of the item image</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="itemIsVegetarian" name="is_vegetarian">
                                <label class="form-check-label" for="itemIsVegetarian">Vegetarian</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="itemIsSpicy" name="is_spicy">
                                <label class="form-check-label" for="itemIsSpicy">Spicy</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="itemIsAvailable" name="is_available" checked>
                                <label class="form-check-label" for="itemIsAvailable">Available</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editItemModalLabel">Edit Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="menu-management.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="update_item" value="1">
                    <input type="hidden" name="item_id" id="editItemId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editItemCategory" class="form-label">Category *</label>
                            <select class="form-select" id="editItemCategory" name="category_id" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editItemDisplayOrder" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="editItemDisplayOrder" name="display_order">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editItemName" class="form-label">Item Name *</label>
                        <input type="text" class="form-control" id="editItemName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editItemDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editItemDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editItemPrice" class="form-label">Price *</label>
                            <input type="number" class="form-control" id="editItemPrice" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editItemDiscountedPrice" class="form-label">Discounted Price</label>
                            <input type="number" class="form-control" id="editItemDiscountedPrice" name="discounted_price" min="0" step="0.01">
                            <small class="text-muted">Leave empty if no discount</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editItemImageUrl" class="form-label">Image URL</label>
                        <input type="url" class="form-control" id="editItemImageUrl" name="image_url">
                        <small class="text-muted">Enter the URL of the item image</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="editItemIsVegetarian" name="is_vegetarian">
                                <label class="form-check-label" for="editItemIsVegetarian">Vegetarian</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="editItemIsSpicy" name="is_spicy">
                                <label class="form-check-label" for="editItemIsSpicy">Spicy</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="editItemIsAvailable" name="is_available">
                                <label class="form-check-label" for="editItemIsAvailable">Available</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle edit category button clicks
    $('.edit-category-btn').click(function() {
        const categoryId = $(this).data('category-id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        const imageUrl = $(this).data('image-url');
        const displayOrder = $(this).data('display-order');
        const isActive = $(this).data('is-active');
        
        $('#editCategoryId').val(categoryId);
        $('#editCategoryName').val(name);
        $('#editCategoryDescription').val(description);
        $('#editCategoryImageUrl').val(imageUrl);
        $('#editCategoryDisplayOrder').val(displayOrder);
        $('#editCategoryIsActive').prop('checked', isActive == 1);
        
        $('#editCategoryModal').modal('show');
    });
    
    // Handle edit item button clicks
    $('.edit-item-btn').click(function() {
        const itemId = $(this).data('item-id');
        const categoryId = $(this).data('category-id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        const price = $(this).data('price');
        const discountedPrice = $(this).data('discounted-price');
        const imageUrl = $(this).data('image-url');
        const isVegetarian = $(this).data('is-vegetarian');
        const isSpicy = $(this).data('is-spicy');
        const isAvailable = $(this).data('is-available');
        const displayOrder = $(this).data('display-order');
        
        $('#editItemId').val(itemId);
        $('#editItemCategory').val(categoryId);
        $('#editItemName').val(name);
        $('#editItemDescription').val(description);
        $('#editItemPrice').val(price);
        $('#editItemDiscountedPrice').val(discountedPrice);
        $('#editItemImageUrl').val(imageUrl);
        $('#editItemDisplayOrder').val(displayOrder);
        $('#editItemIsVegetarian').prop('checked', isVegetarian == 1);
        $('#editItemIsSpicy').prop('checked', isSpicy == 1);
        $('#editItemIsAvailable').prop('checked', isAvailable == 1);
        
        $('#editItemModal').modal('show');
    });
    
    // Initialize item price validation
    $('#itemPrice, #editItemPrice').on('change', function() {
        const price = parseFloat($(this).val());
        const discountedPriceInput = $(this).closest('.row').find('input[name="discounted_price"]');
        const discountedPrice = parseFloat(discountedPriceInput.val());
        
        if (discountedPrice && discountedPrice >= price) {
            alert('Discounted price must be less than regular price');
            discountedPriceInput.val('');
        }
    });
    
    $('#itemDiscountedPrice, #editItemDiscountedPrice').on('change', function() {
        const discountedPrice = parseFloat($(this).val());
        const priceInput = $(this).closest('.row').find('input[name="price"]');
        const price = parseFloat(priceInput.val());
        
        if (discountedPrice && discountedPrice >= price) {
            alert('Discounted price must be less than regular price');
            $(this).val('');
        }
    });
});
</script>

<style>
.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #212529;
}
.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}
.card {
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
</style>

<?php
include 'footer.php';
?>