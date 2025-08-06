<?php
require_once '../includes/db.php'; // File chứa class Database

function createAdminAccount() {
    $db = Database::getInstance();
    
    // Thông tin tài khoản admin
    $adminData = [
        'username' => 'admin1',
        'email' => 'admin1@example.com',
        'password' => 'password', // Mật khẩu gốc
        'first_name' => 'Admin',
        'last_name' => 'Account',
        'phone' => '01234567891',
        'role' => 'admin',
        'is_active' => 1
    ];
    
    try {
        // Kiểm tra tài khoản đã tồn tại chưa
        $existing = $db->fetchOne("SELECT user_id FROM users WHERE username = ? OR email = ?", 
                                [$adminData['username'], $adminData['email']]);
        
        if ($existing) {
            return "Tài khoản admin đã tồn tại!";
        }
        
        // Hash mật khẩu
        $hashedPassword = password_hash($adminData['password'], PASSWORD_DEFAULT);
        
        // Thêm vào database
        $db->execute("
            INSERT INTO users (
                username, email, password_hash, 
                first_name, last_name, phone, 
                role, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $adminData['username'],
            $adminData['email'],
            $hashedPassword,
            $adminData['first_name'],
            $adminData['last_name'],
            $adminData['phone'],
            $adminData['role'],
            $adminData['is_active']
        ]);
        
        return "Tài khoản admin đã được tạo thành công!";
        
    } catch (Exception $e) {
        return "Lỗi khi tạo tài khoản: " . $e->getMessage();
    }
}

// Gọi hàm để thực thi
echo createAdminAccount();
?>