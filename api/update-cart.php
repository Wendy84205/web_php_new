<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid cart data']);
    exit;
}

$_SESSION['cart'] = [];

foreach ($data['cart'] as $item) {
    if (isset($item['id']) && isset($item['quantity'])) {
        $_SESSION['cart'][$item['id']] = (int)$item['quantity'];
    }
}

echo json_encode(['status' => 'ok']);