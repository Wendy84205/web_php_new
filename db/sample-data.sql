-- Dữ liệu mẫu cho hệ thống Com Nieu

-- Thêm danh mục menu
INSERT INTO menu_categories (name, description, image_url, display_order) VALUES
('Món Chính', 'Các món ăn chính đặc trưng', '/assets/images/menu-items/main-courses.jpg', 1),
('Khai Vị', 'Các món ăn nhẹ khai vị', '/assets/images/menu-items/appetizers.jpg', 2),
('Tráng Miệng', 'Các món ngọt tráng miệng', '/assets/images/menu-items/desserts.jpg', 3);

-- Thêm món ăn
INSERT INTO menu_items (category_id, name, description, price, discounted_price, image_url, is_vegetarian, is_spicy) VALUES
(1, 'Cơm Tấm Sườn Bì Chả', 'Cơm tấm với sườn, bì, chả trứng và đồ chua', 65000, 55000, '/assets/images/menu-items/com-tam.jpg', FALSE, FALSE),
(1, 'Phở Bò', 'Phở bò truyền thống', 55000, NULL, '/assets/images/menu-items/pho-bo.jpg', FALSE, FALSE),
(2, 'Gỏi Cuốn', 'Gỏi cuốn tôm thịt', 35000, 30000, '/assets/images/menu-items/goi-cuon.jpg', FALSE, FALSE),
(3, 'Chè Thái', 'Chè thái nguyên chất', 25000, NULL, '/assets/images/menu-items/che-thai.jpg', TRUE, FALSE);

-- Thêm người dùng mẫu
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, address, role) VALUES
('admin', 'admin@comnieu.com', '$2y$10$EXAMPLEHASH', 'Quản', 'Trị', '0901234567', '123 Đường ABC, Quận 1, TP.HCM', 'admin'),
('customer1', 'customer1@example.com', '$2y$10$EXAMPLEHASH', 'Khách', 'Hàng', '0912345678', '456 Đường XYZ, Quận 2, TP.HCM', 'customer'),
('driver1', 'driver1@example.com', '$2y$10$EXAMPLEHASH', 'Tài', 'Xế', '0987654321', '789 Đường DEF, Quận 3, TP.HCM', 'driver');

-- Thêm thông tin tài xế
INSERT INTO drivers (driver_id, vehicle_type, vehicle_number, license_number, current_location) VALUES
(3, 'Xe máy', '51A-123.45', 'DL123456', ST_GeomFromText('POINT(10.823099 106.629664)'));

-- Thêm địa chỉ khách hàng
INSERT INTO customer_addresses (user_id, address_type, address_line1, city, district, ward, phone, is_default, location) VALUES
(2, 'home', '123 Đường Lê Lợi', 'TP.HCM', 'Quận 1', 'Phường Bến Nghé', '0912345678', TRUE, ST_GeomFromText('POINT(10.771944 106.698056)'));

-- Thêm khuyến mãi
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_amount, end_date) VALUES
('WELCOME10', 'Giảm 10% cho khách mới', 'Giảm 10% cho đơn hàng đầu tiên', 'percentage', 10, 100000, DATE_ADD(NOW(), INTERVAL 30 DAY)),
('FREESHIP', 'Miễn phí vận chuyển', 'Miễn phí vận chuyển cho đơn từ 200k', 'fixed', 20000, 200000, DATE_ADD(NOW(), INTERVAL 60 DAY));