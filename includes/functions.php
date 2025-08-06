<?php

function getSetting($key, $default = null) {
    $db = Database::getInstance();
    $setting = $db->fetchOne("
        SELECT setting_value FROM system_settings 
        WHERE setting_key = ? AND (is_public = TRUE OR ? = TRUE)
    ", [$key, isAdmin()]);
    
    return $setting ? $setting['setting_value'] : $default;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

function format_currency($amount, $currency = 'đ') {
    if (!is_numeric($amount)) return $amount;
    return number_format($amount, 0, ',', '.') . $currency;
}

function currentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function format_date($date, $includeTime = false) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;
    
    return $includeTime 
        ? date('d/m/Y H:i', $timestamp)
        : date('d/m/Y', $timestamp);
}

function generateOrderNumber() {
    return 'CN-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
}

// ==============================================
// HÀM QUẢN LÝ ĐƠN HÀNG VÀ THANH TOÁN
// ==============================================

function calculateCartTotal() {
    if (empty($_SESSION['cart'])) return 0;
    
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += ($item['discounted_price'] ?? $item['price']) * $item['quantity'];
    }
    return $total;
}

function getOrderById($order_id) {
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM orders WHERE order_id = ?", [$order_id]);
}

function updateOrderPaymentStatus($order_id, $status) {
    $db = Database::getInstance();
    return $db->execute("UPDATE orders SET payment_status = ? WHERE order_id = ?", [$status, $order_id]);
}

function createPayment(array $paymentData) {
    $db = Database::getInstance();
    return $db->execute("
        INSERT INTO payments 
        (order_id, amount, payment_method, payment_status, transaction_id) 
        VALUES (?, ?, ?, ?, ?)
    ", [
        $paymentData['order_id'],
        $paymentData['amount'],
        $paymentData['method'],
        $paymentData['status'],
        $paymentData['transaction_id']
    ]);
}

function hasReviewedOrder($user_id, $order_id) {
    $db = Database::getInstance();
    $count = $db->fetchOne("
        SELECT COUNT(*) 
        FROM reviews 
        WHERE user_id = ? AND order_id = ?
    ", [$user_id, $order_id]);
    
    return $count && $count['COUNT(*)'] > 0;
}

// ==============================================
// HÀM QUẢN LÝ TÀI XẾ
// ==============================================

function getAvailableDrivers() {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT 
            d.driver_id,
            CONCAT(u.first_name, ' ', u.last_name) AS driver_name,
            d.vehicle_type,
            d.vehicle_number,
            d.rating
        FROM drivers d
        JOIN users u ON d.driver_id = u.user_id
        WHERE d.is_available = 1
        ORDER BY d.rating DESC
    ");
}

// ==============================================
// HÀM HIỂN THỊ GIAO DIỆN
// ==============================================

function getMenuCategoriesWithItems() {
    $db = Database::getInstance();
    $categories = $db->fetchAll("
        SELECT * FROM menu_categories 
        WHERE is_active = TRUE 
        ORDER BY display_order
    ");
    
    foreach ($categories as &$category) {
        $category['items'] = $db->fetchAll("
            SELECT * FROM menu_items 
            WHERE category_id = ? AND is_available = TRUE 
            ORDER BY display_order
        ", [$category['category_id']]);
    }
    
    return $categories;
}

function getFeaturedMenuItems($limit = 6) {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT mi.*, mc.name AS category_name 
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.category_id
        WHERE mi.is_available = TRUE AND mc.is_active = TRUE
        ORDER BY RAND() LIMIT ?
    ", [$limit]);
}

function getCustomerReviews($limit = 5) {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT r.*, u.first_name, u.last_name 
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.is_approved = TRUE
        ORDER BY r.review_date DESC LIMIT ?
    ", [$limit]);
}