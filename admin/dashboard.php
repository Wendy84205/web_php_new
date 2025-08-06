<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'customer';

// Get user details
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Dashboard content based on user role
$dashboard_content = '';
$page_title = '';

switch ($user_role) {
    case 'admin':
        $page_title = 'Admin Dashboard';
        $dashboard_content = renderAdminDashboard($pdo);
        break;
    case 'driver':
        $page_title = 'Driver Dashboard';
        $dashboard_content = renderDriverDashboard($pdo, $user_id);
        break;
    case 'customer':
        $page_title = 'My Account';
        $dashboard_content = renderCustomerDashboard($pdo, $user_id);
        break;
    default:
        $page_title = 'Dashboard';
        $dashboard_content = '<p>Welcome to your dashboard.</p>';
}

// Helper functions for rendering different dashboard views
function renderAdminDashboard($pdo) {
    // Get stats
    $orders_today = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()")->fetchColumn();
    $total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $revenue_month = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE MONTH(order_date) = MONTH(CURDATE())")->fetchColumn();
    $pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending', 'confirmed', 'preparing')")->fetchColumn();
    
    // Recent orders
    $stmt = $pdo->query("SELECT o.order_id, o.order_number, u.first_name, u.last_name, o.order_status, o.total_amount 
                         FROM orders o JOIN users u ON o.user_id = u.user_id 
                         ORDER BY o.order_date DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Low stock items
    $stmt = $pdo->query("SELECT item_id, name, price FROM menu_items WHERE is_available = 1 ORDER BY RAND() LIMIT 4");
    $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start(); ?>
    
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p>Welcome back! Here's what's happening with your business today.</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card bg-primary">
            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
            <div class="stat-info">
                <h3>Today's Orders</h3>
                <p><?= $orders_today ?></p>
            </div>
        </div>
        
        <div class="stat-card bg-success">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3>Total Customers</h3>
                <p><?= $total_customers ?></p>
            </div>
        </div>
        
        <div class="stat-card bg-warning">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-info">
                <h3>Monthly Revenue</h3>
                <p><?= formatCurrency($revenue_month) ?></p>
            </div>
        </div>
        
        <div class="stat-card bg-danger">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3>Pending Orders</h3>
                <p><?= $pending_orders ?></p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-content-grid">
        <div class="dashboard-card">
            <div class="card-header">
                  <a href="orders.php" class="btn btn-view-all">
                    <i class="fas fa-list"></i> View All Orders
                  </a>
            </div>
            <div class="card-body">
                <?php if (count($recent_orders)): ?>
                    <div class="order-list">
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <span class="order-number">#<?= $order['order_number'] ?></span>
                                    <span class="order-customer"><?= $order['first_name'] ?> <?= $order['last_name'] ?></span>
                                </div>
                                <div class="order-meta">
                                    <span class="order-status <?= $order['order_status'] ?>"><?= formatOrderStatus($order['order_status']) ?></span>
                                    <span class="order-amount"><?= formatCurrency($order['total_amount']) ?></span>
                                    <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-link"><i class="fas fa-eye"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No recent orders found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Items</h3>
                <a href="inventory.php" class="btn btn-sm">Manage</a>
            </div>
            <div class="card-body">
                <?php if (count($low_stock_items)): ?>
                    <div class="item-grid">
                        <?php foreach ($low_stock_items as $item): ?>
                            <div class="item-card">
                                <div class="item-info">
                                    <h4><?= $item['name'] ?></h4>
                                    <p class="item-price"><?= formatCurrency($item['price']) ?></p>
                                </div>
                                <a href="edit_item.php?id=<?= $item['item_id'] ?>" class="btn btn-sm btn-primary">Restock</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>All items are well stocked</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

function renderDriverDashboard($pdo, $driver_id) {
    // Get driver info
    $stmt = $pdo->prepare("SELECT d.*, u.first_name, u.last_name, u.phone 
                          FROM drivers d JOIN users u ON d.driver_id = u.user_id 
                          WHERE d.driver_id = ?");
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Current delivery
    $stmt = $pdo->prepare("SELECT o.order_id, o.order_number, o.total_amount, 
                           CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                           a.address_line1, a.city, a.ward
                           FROM orders o 
                           JOIN users u ON o.user_id = u.user_id
                           JOIN customer_addresses a ON o.address_id = a.address_id
                           WHERE o.driver_id = ? AND o.order_status = 'on_delivery'");
    $stmt->execute([$driver_id]);
    $current_delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Available deliveries
    $stmt = $pdo->query("SELECT o.order_id, o.order_number, o.total_amount, 
                         CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                         a.address_line1, a.city, a.ward
                         FROM orders o 
                         JOIN users u ON o.user_id = u.user_id
                         JOIN customer_addresses a ON o.address_id = a.address_id
                         WHERE o.order_status = 'ready' AND o.driver_id IS NULL
                         ORDER BY o.order_date ASC LIMIT 3");
    $available_deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Delivery history
    $stmt = $pdo->prepare("SELECT o.order_id, o.order_number, o.order_date, o.total_amount, 
                           CONCAT(u.first_name, ' ', u.last_name) as customer_name
                           FROM orders o 
                           JOIN users u ON o.user_id = u.user_id
                           WHERE o.driver_id = ? AND o.order_status = 'delivered'
                           ORDER BY o.order_date DESC LIMIT 3");
    $stmt->execute([$driver_id]);
    $delivery_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start(); ?>
    
    <div class="dashboard-header">
        <h1><i class="fas fa-motorcycle"></i> Driver Dashboard</h1>
        <p>Welcome back, <?= $driver['first_name'] ?>! Ready to deliver some delicious food?</p>
    </div>
    
    <div class="driver-profile-card">
        <div class="profile-header">
            <div class="avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-info">
                <h2><?= $driver['first_name'] ?> <?= $driver['last_name'] ?></h2>
                <p><i class="fas fa-phone"></i> <?= $driver['phone'] ?></p>
                <p><i class="fas fa-biking"></i> <?= $driver['vehicle_type'] ?> (<?= $driver['vehicle_number'] ?>)</p>
            </div>
        </div>
        <div class="profile-stats">
            <div class="stat-item">
                <span class="stat-value"><?= $driver['delivery_count'] ?? 0 ?></span>
                <span class="stat-label">Deliveries</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?= number_format($driver['rating'] ?? 0, 1) ?></span>
                <span class="stat-label">Rating</span>
            </div>
            <div class="stat-item">
                <span class="stat-value <?= $driver['is_available'] ? 'text-success' : 'text-danger' ?>">
                    <?= $driver['is_available'] ? 'Available' : 'Busy' ?>
                </span>
                <span class="stat-label">Status</span>
            </div>
        </div>
    </div>
    
    <?php if ($current_delivery): ?>
        <div class="dashboard-card current-delivery">
            <div class="card-header">
                <h3><i class="fas fa-shipping-fast"></i> Current Delivery</h3>
                <span class="badge bg-primary">In Progress</span>
            </div>
            <div class="card-body">
                <div class="delivery-info">
                    <div class="info-item">
                        <span class="info-label">Order #</span>
                        <span class="info-value"><?= $current_delivery['order_number'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Customer</span>
                        <span class="info-value"><?= $current_delivery['customer_name'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Amount</span>
                        <span class="info-value"><?= formatCurrency($current_delivery['total_amount']) ?></span>
                    </div>
                    <div class="info-item full-width">
                        <span class="info-label">Delivery Address</span>
                        <span class="info-value"><?= $current_delivery['address_line1'] ?>, <?= $current_delivery['ward'] ?>, <?= $current_delivery['city'] ?></span>
                    </div>
                </div>
                <button class="btn btn-success btn-block" id="complete-delivery" data-order="<?= $current_delivery['order_id'] ?>">
                    <i class="fas fa-check-circle"></i> Mark as Delivered
                </button>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="dashboard-content-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-list"></i> Available Deliveries</h3>
                <a href="deliveries.php" class="btn btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($available_deliveries)): ?>
                    <div class="delivery-list">
                        <?php foreach ($available_deliveries as $delivery): ?>
                            <div class="delivery-item">
                                <div class="delivery-info">
                                    <span class="delivery-number">#<?= $delivery['order_number'] ?></span>
                                    <span class="delivery-customer"><?= $delivery['customer_name'] ?></span>
                                    <span class="delivery-address"><?= $delivery['ward'] ?>, <?= $delivery['city'] ?></span>
                                </div>
                                <div class="delivery-actions">
                                    <span class="delivery-amount"><?= formatCurrency($delivery['total_amount']) ?></span>
                                    <button class="btn btn-sm btn-primary btn-accept" data-order="<?= $delivery['order_id'] ?>">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No available deliveries at the moment</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Recent Deliveries</h3>
                <a href="delivery_history.php" class="btn btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($delivery_history)): ?>
                    <div class="history-list">
                        <?php foreach ($delivery_history as $delivery): ?>
                            <div class="history-item">
                                <div class="history-info">
                                    <span class="history-number">#<?= $delivery['order_number'] ?></span>
                                    <span class="history-customer"><?= $delivery['customer_name'] ?></span>
                                    <span class="history-date"><?= formatDate($delivery['order_date']) ?></span>
                                </div>
                                <div class="history-actions">
                                    <span class="history-amount"><?= formatCurrency($delivery['total_amount']) ?></span>
                                    <a href="order_details.php?id=<?= $delivery['order_id'] ?>" class="btn btn-sm btn-link">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No delivery history yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

function renderCustomerDashboard($pdo, $user_id) {
    // Customer info
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Recent orders
    $stmt = $pdo->prepare("SELECT o.order_id, o.order_number, o.order_date, o.total_amount, o.order_status 
                          FROM orders o 
                          WHERE o.user_id = ? 
                          ORDER BY o.order_date DESC LIMIT 3");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Saved addresses
    $stmt = $pdo->prepare("SELECT address_id, address_type, address_line1, city, ward, is_default 
                          FROM customer_addresses 
                          WHERE user_id = ? 
                          ORDER BY is_default DESC LIMIT 3");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start(); ?>
    
    <div class="dashboard-header">
        <h1><i class="fas fa-user-circle"></i> My Account</h1>
        <p>Welcome back, <?= $customer['first_name'] ?>! What would you like to do today?</p>
    </div>
    
    <div class="dashboard-content-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Profile Information</h3>
                <a href="edit_profile.php" class="btn btn-sm">Edit</a>
            </div>
            <div class="card-body">
                <div class="profile-info-grid">
                    <div class="info-item">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= $customer['first_name'] ?> <?= $customer['last_name'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= $customer['email'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?= $customer['phone'] ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-shopping-bag"></i> Recent Orders</h3>
                <a href="order_history.php" class="btn btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_orders)): ?>
                    <div class="order-list">
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <span class="order-number">#<?= $order['order_number'] ?></span>
                                    <span class="order-date"><?= formatDate($order['order_date']) ?></span>
                                </div>
                                <div class="order-meta">
                                    <span class="order-status <?= $order['order_status'] ?>"><?= formatOrderStatus($order['order_status']) ?></span>
                                    <span class="order-amount"><?= formatCurrency($order['total_amount']) ?></span>
                                    <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-link">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>You haven't placed any orders yet</p>
                        <a href="menu.php" class="btn btn-primary">Order Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-map-marker-alt"></i> Saved Addresses</h3>
                <a href="addresses.php" class="btn btn-sm">Manage</a>
            </div>
            <div class="card-body">
                <?php if (count($addresses)): ?>
                    <div class="address-list">
                        <?php foreach ($addresses as $address): ?>
                            <div class="address-item <?= $address['is_default'] ? 'default' : '' ?>">
                                <div class="address-type">
                                    <i class="fas fa-<?= $address['address_type'] === 'home' ? 'home' : 'briefcase' ?>"></i>
                                    <?= ucfirst($address['address_type']) ?>
                                    <?php if ($address['is_default']): ?>
                                        <span class="badge bg-success">Default</span>
                                    <?php endif; ?>
                                </div>
                                <div class="address-details">
                                    <p><?= $address['address_line1'] ?></p>
                                    <p><?= $address['ward'] ?>, <?= $address['city'] ?></p>
                                </div>
                                <div class="address-actions">
                                    <a href="edit_address.php?id=<?= $address['address_id'] ?>" class="btn btn-sm btn-link">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>You haven't saved any addresses yet</p>
                        <a href="add_address.php" class="btn btn-primary">Add Address</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

// Helper formatting functions
function formatCurrency($amount) {
    return number_format($amount ?? 0, 0, ',', '.').' ₫';
}

function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}

function formatOrderStatus($status) {
    $status_map = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'ready' => 'Ready for Pickup',
        'on_delivery' => 'On Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
    return $status_map[$status] ?? $status;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Food Delivery System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #3f37c9;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
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
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-header p {
            color: var(--gray);
            font-size: 16px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: var(--white);
            display: flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 30px;
            margin-right: 20px;
            opacity: 0.8;
        }
        
        .stat-info h3 {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 5px;
            opacity: 0.9;
        }
        
        .stat-info p {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        
        .bg-primary { background-color: var(--primary); }
        .bg-success { background-color: var(--success); }
        .bg-warning { background-color: var(--warning); }
        .bg-danger { background-color: var(--danger); }
        .bg-info { background-color: var(--info); }
        .bg-secondary { background-color: var(--secondary); }
        
        .dashboard-content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
        }
        
        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: var(--white);
        }
        
        .btn-info {
            background-color: var(--info);
            color: var(--white);
        }
        
        .btn-link {
            background: transparent;
            color: var(--primary);
            padding: 0;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            color: var(--white);
        }
        
        .order-list, .delivery-list, .history-list, .address-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .order-item, .delivery-item, .history-item, .address-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            background-color: var(--light);
            transition: all 0.3s ease;
        }
        
        .address-item {
            align-items: flex-start;
            flex-direction: column;
            gap: 10px;
        }
        
        .address-item.default {
            border-left: 4px solid var(--success);
        }
        
        .order-item:hover, .delivery-item:hover, .history-item:hover, .address-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .order-info, .delivery-info, .history-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .order-number, .delivery-number, .history-number {
            font-weight: 600;
            color: var(--dark);
        }
        
        .order-customer, .delivery-customer, .history-customer {
            font-size: 14px;
            color: var(--gray);
        }
        
        .order-date, .delivery-address, .history-date {
            font-size: 13px;
            color: var(--gray);
        }
        
        .order-meta, .delivery-actions, .history-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-status, .delivery-status, .history-status {
            font-size: 12px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .order-amount, .delivery-amount, .history-amount {
            font-weight: 600;
            color: var(--dark);
        }
        
        .pending { background-color: #fff3cd; color: #856404; }
        .confirmed { background-color: #cce5ff; color: #004085; }
        .preparing { background-color: #d4edda; color: #155724; }
        .ready { background-color: #d1ecf1; color: #0c5460; }
        .on_delivery { background-color: #e2e3e5; color: #383d41; }
        .delivered { background-color: #d4edda; color: #155724; }
        .cancelled { background-color: #f8d7da; color: #721c24; }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--light-gray);
        }
        
        .empty-state p {
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .driver-profile-card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            padding: 20px;
            gap: 20px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 24px;
        }
        
        .profile-info h2 {
            font-size: 20px;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .profile-info p {
            font-size: 14px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 3px;
        }
        
        .profile-stats {
            display: flex;
            padding: 15px;
            background-color: var(--light);
        }
        
        .stat-item {
            flex: 1;
            text-align: center;
            padding: 10px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--gray);
        }
        
        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        
        .current-delivery {
            border-left: 4px solid var(--primary);
            margin-bottom: 30px;
        }
        
        .delivery-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-item.full-width {
            grid-column: 1 / -1;
        }
        
        .info-label {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .item-card {
            background-color: var(--light);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .item-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .item-price {
            font-weight: 600;
            color: var(--primary);
        }
        
        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .address-type {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .address-details {
            font-size: 14px;
            color: var(--gray);
        }
        
        .address-actions {
            align-self: flex-end;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-content-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .order-meta, .delivery-actions, .history-actions {
                flex-direction: column;
                align-items: flex-end;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?= $dashboard_content ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Accept delivery
            document.querySelectorAll('.btn-accept').forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order');
                    if (confirm('Are you sure you want to accept this delivery?')) {
                        fetch('accept_delivery.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'order_id=' + orderId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Delivery accepted successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        });
                    }
                });
            });
            
            // Complete delivery
            const completeBtn = document.getElementById('complete-delivery');
            if (completeBtn) {
                completeBtn.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order');
                    if (confirm('Have you successfully delivered this order?')) {
                        fetch('complete_delivery.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'order_id=' + orderId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Delivery completed successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>