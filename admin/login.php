<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Khởi tạo biến thông báo lỗi
$error = '';

// Kiểm tra nếu người dùng đã submit form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Xác định trường cần kiểm tra (email hoặc username)
    $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    try {
        // Truy vấn dữ liệu từ CSDL
        $stmt = $pdo->prepare("SELECT * FROM users WHERE $field = :identifier AND is_active = 1");
        $stmt->execute([':identifier' => $identifier]);
        $user = $stmt->fetch();

        // Kiểm tra người dùng tồn tại và mật khẩu đúng
        if ($user && password_verify($password, $user['password_hash'])) {
            // Lưu thông tin người dùng vào session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            // Chuyển hướng tới dashboard nếu là admin
            if ($user['role'] === 'admin') {
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Tài khoản không có quyền truy cập";
            }
        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi hệ thống, vui lòng thử lại sau";
        error_log("Database error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%);
            color: white;
            padding: 25px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .card-header h3 {
            font-weight: 700;
            margin-bottom: 0;
            position: relative;
            z-index: 2;
        }

        .card-body {
            padding: 30px;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -60px auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 3;
        }

        .form-group {
            margin-bottom: 20px;
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
            padding-left: 45px;
            height: 50px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #4776E6;
            box-shadow: 0 0 0 3px rgba(71, 118, 230, 0.2);
        }

        .btn-login {
            background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%);
            color: white;
            border: none;
            height: 50px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(71, 118, 230, 0.4);
        }

        .error-message {
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <h3>ĐĂNG NHẬP HỆ THỐNG</h3>
            </div>
            <div class="brand-logo">
                <i class="fas fa-lock fa-2x text-primary"></i>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger error-message">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="form-group">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" name="identifier" placeholder="Email hoặc Username" required autofocus>
                    </div>

                    <div class="form-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" name="password" placeholder="Mật khẩu" required>
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        <i class="fas fa-sign-in-alt me-2"></i> ĐĂNG NHẬP
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hiệu ứng khi có lỗi
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.classList.remove('error-message');
                }, 1000);
            }
            
            // Focus vào trường đầu tiên
            document.querySelector('input[name="identifier"]').focus();
        });
    </script>
</body>
</html>