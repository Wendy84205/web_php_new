<?php
// navbar.php - Navigation bar
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>">
            <img src="<?= BASE_URL ?>/assets/images/logos/logo.png" alt="<?= htmlspecialchars(APP_NAME) ?>" height="50">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/menu.php">Thực đơn</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/about.php">Giới thiệu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/contact.php">Liên hệ</a>
                </li>
            </ul>

            <div class="d-flex">
                <div class="nav-cart me-3">
                    <a href="cart.php" class="btn btn-outline-primary position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php
                            $cartCount = 0;
                            if (isLoggedIn() && ($cartId = hasActiveCart())) {
                                $db = Database::getInstance();
                                $result = $db->fetchOne("
                                    SELECT SUM(quantity) as total 
                                    FROM order_items 
                                    WHERE order_id = ?
                                ", [$cartId]);
                                $cartCount = $result['total'] ?? 0;
                            }
                            echo $cartCount;
                            ?>
                        </span>
                    </a>
                </div>

                <?php if (!isLoggedIn()): ?>
                    <div class="nav-auth">
                        <button id="navbarLoginBtn" class="btn btn-primary me-2">Đăng nhập</button>
                        <button id="navbarSignupBtn" class="btn btn-outline-primary">Đăng ký</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>