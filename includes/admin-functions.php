<?php
// admin-functions.php - Admin-specific functions

/**
 * Get all orders with pagination
 */
function getAllOrders($page = 1, $perPage = 10, $status = null) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $perPage;
    
    $where = '';
    $params = [];
    
    if ($status) {
        $where = 'WHERE order_status = ?';
        $params[] = $status;
    }
    
    $orders = $db->fetchAll("
        SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        $where
        ORDER BY o.order_date DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$perPage, $offset]));
    
    $total = $db->fetchOne("
        SELECT COUNT(*) as total FROM orders
        $where
    ", $params)['total'];
    
    return [
        'orders' => $orders,
        'total' => $total,
        'pages' => ceil($total / $perPage)
    ];
}

/**
 * Get order details
 */
function getOrderDetails($orderId) {
    $db = Database::getInstance();
    
    $order = $db->fetchOne("
        SELECT o.*, u.first_name, u.last_name, u.email, u.phone,
               a.address_line1, a.address_line2, a.city, a.district, a.ward, a.phone as delivery_phone,
               d.first_name as driver_first_name, d.last_name as driver_last_name, d.phone as driver_phone
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        JOIN customer_addresses a ON o.address_id = a.address_id
        LEFT JOIN users d ON o.driver_id = d.user_id
        WHERE o.order_id = ?
    ", [$orderId]);
    
    if (!$order) {
        return null;
    }
    
    $order['items'] = $db->fetchAll("
        SELECT oi.*, mi.name as item_name, mi.image_url
        FROM order_items oi
        JOIN menu_items mi ON oi.item_id = mi.item_id
        WHERE oi.order_id = ?
    ", [$orderId]);
    
    $order['history'] = $db->fetchAll("
        SELECT h.*, u.first_name, u.last_name
        FROM order_status_history h
        LEFT JOIN users u ON h.changed_by = u.user_id
        WHERE h.order_id = ?
        ORDER BY h.created_at DESC
    ", [$orderId]);
    
    return $order;
}

/**
 * Update order status
 */
function updateOrderStatus($orderId, $status, $notes = '') {
    $db = Database::getInstance();
    
    // Update order
    $db->execute("
        UPDATE orders SET order_status = ?
        WHERE order_id = ?
    ", [$status, $orderId]);
    
    // Add to history
    $db->execute("
        INSERT INTO order_status_history (order_id, status, changed_by, notes)
        VALUES (?, ?, ?, ?)
    ", [
        $orderId,
        $status,
        getCurrentUserId(),
        $notes
    ]);
    
    return true;
}

/**
 * Get all menu categories
 */
function getAllMenuCategories() {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT * FROM menu_categories 
        ORDER BY display_order
    ");
}

/**
 * Get menu item by ID
 */
function getMenuItem($itemId) {
    $db = Database::getInstance();
    return $db->fetchOne("
        SELECT mi.*, mc.name as category_name
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.category_id
        WHERE mi.item_id = ?
    ", [$itemId]);
}

/**
 * Save menu item
 */
function saveMenuItem($data) {
    $db = Database::getInstance();
    
    if (empty($data['name']) || empty($data['category_id']) || !isset($data['price'])) {
        throw new Exception('Required fields are missing.');
    }
    
    if (isset($data['item_id'])) {
        // Update existing item
        $db->execute("
            UPDATE menu_items SET 
                category_id = ?,
                name = ?,
                description = ?,
                price = ?,
                discounted_price = ?,
                is_vegetarian = ?,
                is_spicy = ?,
                is_available = ?,
                display_order = ?
            WHERE item_id = ?
        ", [
            $data['category_id'],
            $data['name'],
            $data['description'] ?? '',
            $data['price'],
            $data['discounted_price'] ?? null,
            $data['is_vegetarian'] ?? false,
            $data['is_spicy'] ?? false,
            $data['is_available'] ?? true,
            $data['display_order'] ?? 0,
            $data['item_id']
        ]);
        
        return $data['item_id'];
    } else {
        // Insert new item
        $db->execute("
            INSERT INTO menu_items (
                category_id, name, description, price, discounted_price, 
                is_vegetarian, is_spicy, is_available, display_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['category_id'],
            $data['name'],
            $data['description'] ?? '',
            $data['price'],
            $data['discounted_price'] ?? null,
            $data['is_vegetarian'] ?? false,
            $data['is_spicy'] ?? false,
            $data['is_available'] ?? true,
            $data['display_order'] ?? 0
        ]);
        
        return $db->lastInsertId();
    }
}

/**
 * Get all customers
 */
function getAllCustomers($page = 1, $perPage = 20, $search = '') {
    $db = Database::getInstance();
    $offset = ($page - 1) * $perPage;
    
    $where = "WHERE role = 'customer'";
    $params = [];
    
    if ($search) {
        $where .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_fill(0, 4, $searchTerm);
    }
    
    $customers = $db->fetchAll("
        SELECT * FROM users
        $where
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$perPage, $offset]));
    
    $total = $db->fetchOne("
        SELECT COUNT(*) as total FROM users
        $where
    ", $params)['total'];
    
    return [
        'customers' => $customers,
        'total' => $total,
        'pages' => ceil($total / $perPage)
    ];
}