<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập & Đăng Ký</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%);
            color: white;
            padding: 50px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .left-panel::after {
            content: '';
            position: absolute;
            bottom: -80px;
            right: -80px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .left-panel h1 {
            font-size: 32px;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .left-panel p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }

        .right-panel {
            flex: 1;
            padding: 50px 40px;
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        .form-container {
            width: 100%;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #777;
            font-size: 16px;
        }

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

        .btn {
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

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(71, 118, 230, 0.4);
        }

        .social-login {
            margin-top: 30px;
            text-align: center;
        }

        .social-login p {
            color: #777;
            margin-bottom: 15px;
            position: relative;
        }

        .social-login p::before,
        .social-login p::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: #eee;
        }

        .social-login p::before {
            left: 0;
        }

        .social-login p::after {
            right: 0;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .social-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            transform: translateY(-5px);
        }

        .facebook {
            background: #3b5998;
        }

        .google {
            background: #dd4b39;
        }

        .twitter {
            background: #1da1f2;
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

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .left-panel {
                padding: 30px 20px;
            }

            .right-panel {
                padding: 40px 25px;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="left-panel">
            <h1>Chào mừng bạn!</h1>
            <p>Đăng nhập để khám phá thế giới ẩm thực đa dạng và trải nghiệm dịch vụ tốt nhất. Hoặc đăng ký tài khoản
                mới để nhận ưu đãi đặc biệt!</p>
            <div class="features">
                <div class="feature">
                    <i class="fas fa-shipping-fast fa-2x"></i>
                    <h3>Giao hàng nhanh</h3>
                </div>
                <div class="feature">
                    <i class="fas fa-tag fa-2x"></i>
                    <h3>Ưu đãi đặc biệt</h3>
                </div>
                <div class="feature">
                    <i class="fas fa-star fa-2x"></i>
                    <h3>Chất lượng hàng đầu</h3>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="form-container">
                <div class="form-header">
                    <h2>Đăng Nhập Tài Khoản</h2>
                    <p>Vui lòng nhập thông tin của bạn để tiếp tục</p>
                </div>

                <div class="form-toggle">
                    <button id="headerLoginBtn" class="btn btn-link text-white me-3">Đăng nhập</button>
                    <button id="headerSignupBtn" class="btn btn-link text-white">Đăng ký</button>
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

                        <button type="submit" class="btn">Đăng Nhập</button>

                        <div class="social-login">
                            <p>Hoặc đăng nhập bằng</p>
                            <div class="social-icons">
                                <div class="social-icon facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </div>
                                <div class="social-icon google">
                                    <i class="fab fa-google"></i>
                                </div>
                                <div class="social-icon twitter">
                                    <i class="fab fa-twitter"></i>
                                </div>
                            </div>
                        </div>

                        <div class="signup-link">
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

                        <button type="submit" class="btn">Đăng Ký</button>

                        <div class="signup-link">
                            Đã có tài khoản? <a href="#" id="loginLink">Đăng nhập ngay</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle between forms
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

            loginToggle.addEventListener('click', showLoginForm);
            signupToggle.addEventListener('click', showSignupForm);
            signupLink.addEventListener('click', function (e) {
                e.preventDefault();
                showSignupForm();
            });
            loginLink.addEventListener('click', function (e) {
                e.preventDefault();
                showLoginForm();
            });

            // Form validation
            const loginFormContent = document.getElementById('loginFormContent');
            const signupFormContent = document.getElementById('signupFormContent');

            loginFormContent.addEventListener('submit', function (e) {
                e.preventDefault();
                const email = document.getElementById('loginEmail').value;
                const password = document.getElementById('loginPassword').value;

                // Simple validation
                if (email && password) {
                    alert('Đăng nhập thành công!');
                    // Here you would typically submit the form to your backend
                } else {
                    alert('Vui lòng điền đầy đủ thông tin!');
                }
            });

            signupFormContent.addEventListener('submit', function (e) {
                e.preventDefault();
                const firstName = document.getElementById('firstName').value;
                const lastName = document.getElementById('lastName').value;
                const email = document.getElementById('signupEmail').value;
                const phone = document.getElementById('phone').value;
                const password = document.getElementById('signupPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                // Simple validation
                if (!firstName || !lastName || !email || !phone || !password || !confirmPassword) {
                    alert('Vui lòng điền đầy đủ thông tin!');
                    return;
                }

                if (password !== confirmPassword) {
                    alert('Mật khẩu xác nhận không khớp!');
                    return;
                }

                alert('Đăng ký thành công!');
                // Here you would typically submit the form to your backend
            });
        });
    </script>
</body>

</html>