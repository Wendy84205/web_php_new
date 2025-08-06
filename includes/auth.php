<?php
// functions.php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function is_staff() {
    return isLoggedIn() && ($_SESSION['user_role'] === 'staff' || $_SESSION['user_role'] === 'admin');
}

function isDriver() {
    return isLoggedIn() && $_SESSION['user_role'] === 'driver';
}

function getAuthUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = Database::getInstance();
    return $db->fetchOne("
        SELECT * FROM users 
        WHERE user_id = ?
    ", [getAuthUserId()]);
}

// includes/auth.php
function loginUser($identifier, $password) {
    $db = Database::getInstance();
    
    $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    error_log("Login field: " . $field);
    
    $user = $db->fetchOne("
        SELECT user_id, password_hash, role 
        FROM users 
        WHERE $field = ? AND is_active = 1
    ", [$identifier]);

    if (!$user) {
        error_log("User not found or inactive");
        return false;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        error_log("Password mismatch");
        return false;
    }
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_role'] = $user['role'];
    error_log("Session set: " . $user['user_id'] . " - " . $user['role']);
    
    return true;
}
function logoutUser() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

function registerUser($data) {
    $db = Database::getInstance();
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }
    
    if (empty($data['password']) || strlen($data['password']) < 6) {
        throw new Exception('Password must be at least 6 characters long.');
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        throw new Exception('Passwords do not match.');
    }
    
    $existing = $db->fetchOne("
        SELECT user_id FROM users 
        WHERE email = ?
    ", [$data['email']]);
    
    if ($existing) {
        throw new Exception('Email address is already registered.');
    }
    
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $db->execute("
        INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role)
        VALUES (?, ?, ?, ?, ?, ?, 'customer')
    ", [
        $data['email'],
        $data['email'],
        $passwordHash,
        $data['first_name'] ?? '',
        $data['last_name'] ?? '',
        $data['phone'] ?? ''
    ]);
    
    return $db->lastInsertId();
}

function hasActiveCart() {
    if (!isLoggedIn()) return false;
    
    $db = Database::getInstance();
    $cart = $db->fetchOne("
        SELECT order_id FROM orders 
        WHERE user_id = ? AND order_status = 'pending'
        ORDER BY order_date DESC LIMIT 1
    ", [getAuthUserId()]);
    
    return $cart ? $cart['order_id'] : false;
}