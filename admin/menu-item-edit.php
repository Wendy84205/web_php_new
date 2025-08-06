<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize variables
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$errors = [];
$success = false;

// Fetch categories for dropdown
$categories = [];
try {
    $stmt = $pdo->query("SELECT category_id, name FROM menu_categories WHERE is_active = TRUE ORDER BY display_order");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Failed to load categories: " . $e->getMessage();
}

// Fetch item data if editing
$item = [
    'item_id' => 0,
    'category_id' => '',
    'name' => '',
    'description' => '',
    'price' => '',
    'discounted_price' => '',
    'image_url' => '',
    'is_vegetarian' => 0,
    'is_spicy' => 0,
    'is_available' => 1,
    'display_order' => 0
];

if ($item_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            $errors[] = "Menu item not found.";
            $item_id = 0; // Reset to "new item" mode
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to load menu item: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $item['category_id'] = intval($_POST['category_id']);
    $item['name'] = trim($_POST['name']);
    $item['description'] = trim($_POST['description']);
    $item['price'] = floatval($_POST['price']);
    $item['discounted_price'] = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : null;
    $item['is_vegetarian'] = isset($_POST['is_vegetarian']) ? 1 : 0;
    $item['is_spicy'] = isset($_POST['is_spicy']) ? 1 : 0;
    $item['is_available'] = isset($_POST['is_available']) ? 1 : 0;
    $item['display_order'] = intval($_POST['display_order']);
    
    // Validate required fields
    if (empty($item['category_id'])) {
        $errors[] = "Category is required.";
    }
    
    if (empty($item['name'])) {
        $errors[] = "Item name is required.";
    }
    
    if (empty($item['price']) || $item['price'] <= 0) {
        $errors[] = "Valid price is required.";
    }
    
    if ($item['discounted_price'] !== null && $item['discounted_price'] >= $item['price']) {
        $errors[] = "Discounted price must be less than regular price.";
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/menu_items/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExt, $allowedExt)) {
            $fileName = uniqid('item_', true) . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                // Delete old image if exists
                if (!empty($item['image_url']) && file_exists($item['image_url'])) {
                    unlink($item['image_url']);
                }
                $item['image_url'] = $filePath;
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image format. Only JPG, PNG, and GIF are allowed.";
        }
    }
    
    // Save to database if no errors
    if (empty($errors)) {
        try {
            if ($item_id > 0) {
                // Update existing item
                $stmt = $pdo->prepare("UPDATE menu_items SET 
                    category_id = ?, name = ?, description = ?, price = ?, discounted_price = ?, 
                    image_url = ?, is_vegetarian = ?, is_spicy = ?, is_available = ?, display_order = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE item_id = ?");
                
                $params = [
                    $item['category_id'],
                    $item['name'],
                    $item['description'],
                    $item['price'],
                    $item['discounted_price'],
                    $item['image_url'],
                    $item['is_vegetarian'],
                    $item['is_spicy'],
                    $item['is_available'],
                    $item['display_order'],
                    $item_id
                ];
            } else {
                // Insert new item
                $stmt = $pdo->prepare("INSERT INTO menu_items 
                    (category_id, name, description, price, discounted_price, image_url, 
                    is_vegetarian, is_spicy, is_available, display_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $params = [
                    $item['category_id'],
                    $item['name'],
                    $item['description'],
                    $item['price'],
                    $item['discounted_price'],
                    $item['image_url'],
                    $item['is_vegetarian'],
                    $item['is_spicy'],
                    $item['is_available'],
                    $item['display_order']
                ];
            }
            
            $stmt->execute($params);
            
            if ($item_id === 0) {
                $item_id = $pdo->lastInsertId();
            }
            
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Page header
$pageTitle = $item_id > 0 ? "Edit Menu Item" : "Add New Menu Item";
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Menu item saved successfully!
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['category_id'] ?>" <?= $item['category_id'] == $category['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Item Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?= htmlspecialchars($item['name']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="price" name="price" 
                                   step="0.01" min="0" value="<?= htmlspecialchars($item['price']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="discounted_price" class="form-label">Discounted Price (optional)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="discounted_price" name="discounted_price" 
                                   step="0.01" min="0" value="<?= htmlspecialchars($item['discounted_price']) ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="image" class="form-label">Image</label>
                    <input class="form-control" type="file" id="image" name="image" accept="image/*">
                    <?php if (!empty($item['image_url'])): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="Current image" class="img-thumbnail" style="max-height: 200px;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image">
                                <label class="form-check-label" for="remove_image">Remove current image</label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_vegetarian" name="is_vegetarian" 
                                   <?= $item['is_vegetarian'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_vegetarian">Vegetarian</label>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_spicy" name="is_spicy" 
                                   <?= $item['is_spicy'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_spicy">Spicy</label>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" 
                                   <?= $item['is_available'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_available">Available</label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input type="number" class="form-control" id="display_order" name="display_order" 
                           value="<?= htmlspecialchars($item['display_order']) ?>">
                    <div class="form-text">Lower numbers appear first in the menu</div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="menu-items.php" class="btn btn-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>