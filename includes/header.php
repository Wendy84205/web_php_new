<?php
// header.php - Common header for all pages

// Kiểm tra xem BASE_URL đã được định nghĩa chưa
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    define('BASE_URL', $protocol . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
}

// Định nghĩa APP_NAME nếu chưa tồn tại
if (!defined('APP_NAME')) {
    define('APP_NAME', 'ẨM THỰC VIỆT NAM TRUYỀN THỐNG');
}

// Lấy thông tin cài đặt an toàn
$contact_phone = function_exists('getSetting')
    ? getSetting('contact_phone', '078 866 1233')
    : '078 866 1233';

$contact_email = function_exists('getSetting')
    ? getSetting('contact_email', 'amthucvietnam@gmail.com')
    : 'amthucvietnam@gmail.com';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - <?= htmlspecialchars($pageTitle ?? 'Trang chủ') ?></title>
    <meta name="description" content="Nhà hàng ẩm thực Việt Nam truyền thống">

    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/logos/favicon.ico" type="image/x-icon">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">

    <?php if (!empty($customCss)): ?>
        <!-- CSS riêng cho từng trang -->
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= htmlspecialchars($customCss) ?>?v=<?= time() ?>">
    <?php endif; ?>

    <!-- CSS cho form đăng nhập/đăng ký -->
    <style>
        /* Modal Auth */
        .auth-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            width: 90%;
            max-width: 900px;
            border-radius: 10px;
            position: relative;
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #333;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #e74c3c;
        }

        /* Form Toggle */
        .form-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: #f5f5f5;
            border-radius: 30px;
            padding: 5px;
        }

        .toggle-btn {
            padding: 12px 30px;
            border: none;
            background: transparent;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 30px;
            transition: all 0.3s ease;
            outline: none;
        }

        .toggle-btn.active {
            background: #4776E6;
            color: white;
            box-shadow: 0 4px 10px rgba(71, 118, 230, 0.3);
        }

        /* Form Styles */
        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #4776E6;
            box-shadow: 0 0 0 3px rgba(71, 118, 230, 0.2);
            outline: none;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember {
            display: flex;
            align-items: center;
        }

        .remember input {
            margin-right: 8px;
        }

        .forgot-password {
            color: #4776E6;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn-auth {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(71, 118, 230, 0.3);
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(71, 118, 230, 0.4);
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .signup-link {
            text-align: center;
            margin-top: 25px;
            color: #777;
        }

        .signup-link a {
            color: #4776E6;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .modal-content {
                padding: 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <!-- Thanh trên cùng -->
    <div class="top-bar bg-dark text-white py-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="contact-info">
                        <span><i class="fas fa-phone-alt me-2"></i> <?= htmlspecialchars($contact_phone) ?></span>
                        <span class="ms-3"><i class="fas fa-envelope me-2"></i>
                            <?= htmlspecialchars($contact_email) ?></span>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <div class="user-menu">
                            <span class="me-3">Xin chào, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Khách') ?></span>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="<?= BASE_URL ?>/dashboard.php" class="text-white me-2">Quản trị</a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/my-account.php" class="text-white me-2">Tài khoản</a>
                            <a href="<?= BASE_URL ?>/logout.php" class="text-white">Đăng xuất</a>
                        </div>
                    <?php else: ?>
                        <button id="headerLoginBtn" class="btn btn-link text-white me-3">Đăng nhập</button>
                        <button id="headerSignupBtn" class="btn btn-link text-white">Đăng ký</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="main-content">

        <!-- Modal đăng nhập/đăng ký -->
        <div id="authModal" class="auth-modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>

                <div class="form-container">
                    <div class="form-header text-center mb-4">
                        <h2 class="mb-2">Đăng Nhập Tài Khoản</h2>
                        <p class="text-muted">Vui lòng nhập thông tin của bạn để tiếp tục</p>
                    </div>

                    <div class="form-toggle">
                        <button class="toggle-btn active" id="loginToggle">Đăng Nhập</button>
                        <button class="toggle-btn" id="signupToggle">Đăng Ký</button>
                    </div>

                    <!-- Login Form -->
                    <div class="form-section active" id="loginForm">
                        <form id="loginFormContent">
                            <div class="form-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="loginEmail" placeholder="Email" required>
                            </div>

                            <div class="form-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="loginPassword" placeholder="Mật khẩu"
                                    required>
                            </div>

                            <div class="form-footer">
                                <div class="remember">
                                    <input type="checkbox" id="remember">
                                    <label for="remember">Ghi nhớ tôi</label>
                                </div>
                                <a href="#" class="forgot-password">Quên mật khẩu?</a>
                            </div>

                            <button type="submit" class="btn-auth">Đăng Nhập</button>

                            <div class="signup-link mt-4">
                                Chưa có tài khoản? <a href="#" id="signupLink">Đăng ký ngay</a>
                            </div>
                        </form>
                    </div>

                    <!-- Signup Form -->
                    <div class="form-section" id="signupForm">
                        <form id="signupFormContent">
                            <div class="form-row">
                                <div class="form-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" class="form-control" id="firstName" placeholder="Họ" required>
                                </div>

                                <div class="form-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" class="form-control" id="lastName" placeholder="Tên" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="signupEmail" placeholder="Email" required>
                            </div>

                            <div class="form-group">
                                <i class="fas fa-phone"></i>
                                <input type="tel" class="form-control" id="phone" placeholder="Số điện thoại" required>
                            </div>

                            <div class="form-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="signupPassword" placeholder="Mật khẩu"
                                    required>
                            </div>

                            <div class="form-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="confirmPassword"
                                    placeholder="Xác nhận mật khẩu" required>
                            </div>

                            <button type="submit" class="btn-auth">Đăng Ký</button>

                            <div class="signup-link mt-4">
                                Đã có tài khoản? <a href="#" id="loginLink">Đăng nhập ngay</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
<script src="js/cart.js" defer></script>
        <script>

            const navbarLoginBtn = document.getElementById('navbarLoginBtn');
            const navbarSignupBtn = document.getElementById('navbarSignupBtn');

            // Mở modal khi click đăng nhập từ navbar
            if (navbarLoginBtn) {
                navbarLoginBtn.addEventListener('click', function () {
                    modal.style.display = 'block';
                    showLoginForm();
                });
            }

            // Mở modal khi click đăng ký từ navbar
            if (navbarSignupBtn) {
                navbarSignupBtn.addEventListener('click', function () {
                    modal.style.display = 'block';
                    showSignupForm();
                });
            }
            // Xử lý modal đăng nhập/đăng ký
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('authModal');
                const loginBtn = document.getElementById('headerLoginBtn');
                const signupBtn = document.getElementById('headerSignupBtn');
                const closeBtn = document.querySelector('.close-modal');

                // Mở modal khi click đăng nhập
                if (loginBtn) {
                    loginBtn.addEventListener('click', function () {
                        modal.style.display = 'block';
                        showLoginForm();
                    });
                }

                // Mở modal khi click đăng ký
                if (signupBtn) {
                    signupBtn.addEventListener('click', function () {
                        modal.style.display = 'block';
                        showSignupForm();
                    });
                }

                // Đóng modal
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        modal.style.display = 'none';
                    });
                }

                // Đóng modal khi click bên ngoài
                window.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                });

                // Toggle giữa các form
                const loginToggle = document.getElementById('loginToggle');
                const signupToggle = document.getElementById('signupToggle');
                const loginForm = document.getElementById('loginForm');
                const signupForm = document.getElementById('signupForm');
                const signupLink = document.getElementById('signupLink');
                const loginLink = document.getElementById('loginLink');

                function showLoginForm() {
                    loginForm.classList.add('active');
                    signupForm.classList.remove('active');
                    loginToggle.classList.add('active');
                    signupToggle.classList.remove('active');
                }

                function showSignupForm() {
                    signupForm.classList.add('active');
                    loginForm.classList.remove('active');
                    signupToggle.classList.add('active');
                    loginToggle.classList.remove('active');
                }

                if (loginToggle && signupToggle) {
                    loginToggle.addEventListener('click', showLoginForm);
                    signupToggle.addEventListener('click', showSignupForm);
                }

                if (signupLink) {
                    signupLink.addEventListener('click', function (e) {
                        e.preventDefault();
                        showSignupForm();
                    });
                }

                if (loginLink) {
                    loginLink.addEventListener('click', function (e) {
                        e.preventDefault();
                        showLoginForm();
                    });
                }

                // Xử lý submit form
                const loginFormContent = document.getElementById('loginFormContent');
                const signupFormContent = document.getElementById('signupFormContent');

                if (loginFormContent) {
                    loginFormContent.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const email = document.getElementById('loginEmail').value;
                        const password = document.getElementById('loginPassword').value;

                        if (email && password) {
                            // Gửi dữ liệu đăng nhập đến server
                            fetch('<?= BASE_URL ?>/api/login.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ email, password })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('Đăng nhập thành công!');
                                        modal.style.display = 'none';
                                        location.reload(); // Tải lại trang để cập nhật header
                                    } else {
                                        alert(data.message || 'Đăng nhập thất bại!');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Có lỗi xảy ra khi đăng nhập');
                                });
                        } else {
                            alert('Vui lòng điền đầy đủ thông tin!');
                        }
                    });
                }

                if (signupFormContent) {
                    signupFormContent.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const firstName = document.getElementById('firstName').value;
                        const lastName = document.getElementById('lastName').value;
                        const email = document.getElementById('signupEmail').value;
                        const phone = document.getElementById('phone').value;
                        const password = document.getElementById('signupPassword').value;
                        const confirmPassword = document.getElementById('confirmPassword').value;

                        if (!firstName || !lastName || !email || !phone || !password || !confirmPassword) {
                            alert('Vui lòng điền đầy đủ thông tin!');
                            return;
                        }

                        if (password !== confirmPassword) {
                            alert('Mật khẩu xác nhận không khớp!');
                            return;
                        }

                        // Gửi dữ liệu đăng ký đến server
                        fetch('<?= BASE_URL ?>/api/register.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                first_name: firstName,
                                last_name: lastName,
                                email,
                                phone,
                                password
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Đăng ký thành công! Vui lòng đăng nhập.');
                                    showLoginForm();
                                } else {
                                    alert(data.message || 'Đăng ký thất bại!');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Có lỗi xảy ra khi đăng ký');
                            });
                    });
                }
            });
        </script>