<?php
// inventory.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        // Add new item
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, stock, category, is_available) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock'],
            $_POST['category'],
            isset($_POST['is_available']) ? 1 : 0
        ]);
        $_SESSION['success'] = "Item added successfully!";
    } elseif (isset($_POST['update_item'])) {
        // Update existing item
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, stock = ?, category = ?, is_available = ? WHERE item_id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock'],
            $_POST['category'],
            isset($_POST['is_available']) ? 1 : 0,
            $_POST['item_id']
        ]);
        $_SESSION['success'] = "Item updated successfully!";
    } elseif (isset($_POST['delete_item'])) {
        // Delete item
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE item_id = ?");
        $stmt->execute([$_POST['item_id']]);
        $_SESSION['success'] = "Item deleted successfully!";
    }
    header('Location: inventory.php');
    exit();
}

// Get all inventory items
$items = $pdo->query("SELECT * FROM menu_items ORDER BY stock ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$categories = $pdo->query("SELECT DISTINCT category FROM menu_items")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--light);
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .low-stock {
            color: var(--danger);
            font-weight: 600;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            border: none;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: var(--border-radius);
            width: 80%;
            max-width: 600px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            font-size: 24px;
            cursor: pointer;
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-boxes"></i> Inventory Management</h1>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Item
            </button>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Current Inventory</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= $item['item_id'] ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= number_format($item['price'], 0, ',', '.') ?> ₫</td>
                            <td class="<?= $item['stock'] < 5 ? 'low-stock' : '' ?>">
                                <?= $item['stock'] ?>
                            </td>
                            <td><?= htmlspecialchars($item['category']) ?></td>
                            <td>
                                <?= $item['is_available'] ? 
                                    '<span style="color:green">Available</span>' : 
                                    '<span style="color:red">Unavailable</span>' ?>
                            </td>
                            <td>
                                <button class="btn btn-primary" onclick="openEditModal(
                                    <?= $item['item_id'] ?>,
                                    '<?= addslashes($item['name']) ?>',
                                    '<?= addslashes($item['description']) ?>',
                                    <?= $item['price'] ?>,
                                    <?= $item['stock'] ?>,
                                    '<?= addslashes($item['category']) ?>',
                                    <?= $item['is_available'] ?>
                                )">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                    <button type="submit" name="delete_item" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Item Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Item</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Item Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (₫)</label>
                    <input type="number" id="price" name="price" min="0" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" min="0" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                        <option value="">-- New Category --</option>
                    </select>
                    <input type="text" id="new_category" name="new_category" style="display:none; margin-top:5px;" placeholder="Enter new category">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_available" checked> Available
                    </label>
                </div>
                <button type="submit" name="add_item" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Item
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Item</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_item_id" name="item_id">
                <div class="form-group">
                    <label for="edit_name">Item Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_price">Price (₫)</label>
                    <input type="number" id="edit_price" name="price" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_stock">Stock</label>
                    <input type="number" id="edit_stock" name="stock" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category" name="category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                        <option value="">-- New Category --</option>
                    </select>
                    <input type="text" id="edit_new_category" name="new_category" style="display:none; margin-top:5px;" placeholder="Enter new category">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_available" name="is_available"> Available
                    </label>
                </div>
                <button type="submit" name="update_item" class="btn btn-success">
                    <i class="fas fa-save"></i> Update Item
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Category selection handler
        document.getElementById('category').addEventListener('change', function() {
            const newCategoryInput = document.getElementById('new_category');
            newCategoryInput.style.display = this.value === '' ? 'block' : 'none';
            if (this.value !== '') newCategoryInput.value = '';
        });
        
        document.getElementById('edit_category').addEventListener('change', function() {
            const newCategoryInput = document.getElementById('edit_new_category');
            newCategoryInput.style.display = this.value === '' ? 'block' : 'none';
            if (this.value !== '') newCategoryInput.value = '';
        });
        
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(id, name, description, price, stock, category, isAvailable) {
            document.getElementById('edit_item_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_stock').value = stock;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_is_available').checked = isAvailable;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>