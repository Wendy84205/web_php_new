<?php
header('Content-Type: application/json');
require_once '../config.php';

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get all active categories with their items
    $stmt = $db->query("SELECT * FROM menu_categories WHERE is_active = TRUE ORDER BY display_order");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as &$category) {
        $stmt = $db->prepare("SELECT * FROM menu_items 
                            WHERE category_id = ? AND is_available = TRUE 
                            ORDER BY display_order");
        $stmt->execute([$category['category_id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format price and add image URL if needed
        foreach ($items as &$item) {
            $item['price'] = floatval($item['price']);
            $item['discounted_price'] = $item['discounted_price'] ? floatval($item['discounted_price']) : null;
            
            if ($item['image_url'] && !filter_var($item['image_url'], FILTER_VALIDATE_URL)) {
                $item['image_url'] = BASE_URL . '/uploads/menu/' . $item['image_url'];
            }
        }
        
        $category['items'] = $items;
        
        // Format category image URL if needed
        if ($category['image_url'] && !filter_var($category['image_url'], FILTER_VALIDATE_URL)) {
            $category['image_url'] = BASE_URL . '/uploads/categories/' . $category['image_url'];
        }
    }
    
    // Check if we should return a single category
    if (isset($_GET['category_id'])) {
        $category_id = intval($_GET['category_id']);
        $found_category = null;
        
        foreach ($categories as $cat) {
            if ($cat['category_id'] == $category_id) {
                $found_category = $cat;
                break;
            }
        }
        
        if ($found_category) {
            echo json_encode(['data' => $found_category]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
        }
    } 
    // Check if we should return a single item
    elseif (isset($_GET['item_id'])) {
        $item_id = intval($_GET['item_id']);
        $found_item = null;
        
        foreach ($categories as $cat) {
            foreach ($cat['items'] as $item) {
                if ($item['item_id'] == $item_id) {
                    $found_item = $item;
                    break 2;
                }
            }
        }
        
        if ($found_item) {
            echo json_encode(['data' => $found_item]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
        }
    }
    // Return all categories with items
    else {
        echo json_encode(['data' => $categories]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>