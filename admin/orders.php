<?php
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

// Xử lý các thao tác CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $stmt->execute([$new_status, $order_id]);
            
            $stmt = $pdo->prepare("INSERT INTO order_status_history 
                                 (order_id, status, changed_by, notes) 
                                 VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $new_status, $user_id, $notes]);
            
            $pdo->commit();
            $_SESSION['success'] = "Order status updated successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to update order: " . $e->getMessage();
        }
        
        header("Location: orders.php");
        exit();
    }
}

// Lấy danh sách đơn hàng
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$query = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name 
          FROM orders o
          JOIN users u ON o.user_id = u.user_id
          WHERE 1=1";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $query .= " AND (o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_term = "%$search_query%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

$query .= " ORDER BY o.order_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy các trạng thái đơn hàng có sẵn
$statuses = $pdo->query("SELECT DISTINCT order_status FROM orders")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Food Delivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
            --border-radius: 10px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
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
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .filter-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 14px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            border: none;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
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
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-confirmed { background-color: #cce5ff; color: #004085; }
        .badge-preparing { background-color: #d4edda; color: #155724; }
        .badge-ready { background-color: #28a745; color: white; }
        .badge-on-delivery { background-color: #6f42c1; color: white; }
        .badge-delivered { background-color: #28a745; color: white; }
        .badge-cancelled { background-color: #dc3545; color: white; }
        
        .action-btns {
            display: flex;
            gap: 5px;
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
            max-width: 500px;
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: var(--light-gray);
        }
        
        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }
            
            .action-btns {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-list"></i> Orders Management</h1>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="filters">
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" class="filter-control" onchange="filterOrders()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= $status ?>" <?= $status_filter === $status ? 'selected' : '' ?>>
                                <?= ucfirst($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" class="filter-control" placeholder="Search orders..." 
                           value="<?= htmlspecialchars($search_query) ?>" oninput="filterOrders()">
                </div>
            </div>
            
            <?php if (count($orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_number']) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $order['order_status'] ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td><?= number_format($order['total_amount'], 0, ',', '.') ?> ₫</td>
                                <td>
                                    <div class="action-btns">
                                        <a href="order.php?id=<?= $order['order_id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="openStatusModal(<?= $order['order_id'] ?>, '<?= $order['order_status'] ?>')">
                                            <i class="fas fa-edit"></i> Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No orders found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Order Status</h2>
                <span class="close" onclick="closeModal('statusModal')">&times;</span>
            </div>
            <form method="POST" id="statusForm">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label for="status">New Status</label>
                    <select name="status" id="modalStatus" class="form-control" required>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= $status ?>"><?= ucfirst($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function filterOrders() {
            const status = document.getElementById('status').value;
            const search = document.getElementById('search').value;
            const url = new URL(window.location.href);
            
            url.searchParams.set('status', status);
            url.searchParams.set('search', search);
            
            window.location.href = url.toString();
        }
        
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
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