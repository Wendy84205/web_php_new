<?php
require_once 'includes/init.php'; // phải khởi tạo $pdo và session_start()

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my-account.php?action=add_address');
    exit();
}

try {
    $user_id = $_SESSION['user_id'];

    // Lấy dữ liệu từ form, trim để loại khoảng trắng
    $address_type = $_POST['address_type'] ?? 'Home';
    $line1 = trim($_POST['address_line1'] ?? '');
    $line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // Location (nếu bạn vẫn giữ cột này)
    $location = trim($_POST['location'] ?? '');

    // Validate bắt buộc
    if ($line1 === '' || $city === '' || $district === '' || $ward === '' || $phone === '') {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc (Address Line 1, City, District, Ward, Phone).');
    }

    // Nếu có trường location bắt buộc
    if ($location === '') {
        throw new Exception('Vui lòng nhập Location.'); // hoặc bỏ nếu không cần bắt buộc
    }

    // Nếu đặt làm mặc định thì reset các địa chỉ cũ
    if ($is_default) {
        $resetStmt = $pdo->prepare("UPDATE customer_addresses SET is_default = 0 WHERE user_id = ?");
        $resetStmt->execute([$user_id]);
    }

    // Chèn vào database
    // Lưu ý: đảm bảo cấu trúc bảng hiện tại có cột `location` là VARCHAR/NULLABLE hoặc đã xoá nếu bỏ
    $stmt = $pdo->prepare("INSERT INTO customer_addresses 
        (user_id, address_type, address_line1, address_line2, city, district, ward, phone, notes, is_default, location)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $user_id,
        $address_type,
        $line1,
        $line2,
        $city,
        $district,
        $ward,
        $phone,
        $notes,
        $is_default,
        $location
    ]);

    // Chuyển hướng về danh sách địa chỉ
    header('Location: my-account.php?action=addresses');
    exit();

} catch (Exception $e) {
    // Ghi lỗi và quay lại form thêm
    $_SESSION['error'] = $e->getMessage();
    header('Location: my-account.php?action=add_address');
    exit();
}
