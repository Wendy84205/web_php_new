<?php
// footer.php - Common footer for all pages
?>
    </main>
    
    <footer class="footer bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h3 class="footer-heading"><?= htmlspecialchars(APP_NAME) ?></h3>
                    <p>Nhà hàng chuyên phục vụ các món ăn dân dã truyền thống Việt Nam, đặc biệt là cơm niêu đất.</p>
                    <div class="social-icons">
                        <a href="<?= getSetting('facebook_url', 'https://www.facebook.com/comnieuannam') ?>" class="text-white me-3" target="_blank">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-white me-3">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-white">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h3 class="footer-heading">Thông tin liên hệ</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?= getSetting('restaurant_address', 'Lô 42-43 Trần Bạch Đằng, Ngũ Hành Sơn, Đà Nẵng') ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone-alt me-2"></i>
                            <?= getSetting('contact_phone', '078 866 1233') ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <?= getSetting('contact_email', 'annamquandn@gmail.com') ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock me-2"></i>
                            <?= getSetting('opening_hours', 'Mở cửa: 10:00 - 22:00 hàng ngày') ?>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h3 class="footer-heading">Đăng ký nhận tin</h3>
                    <p>Đăng ký để nhận thông tin khuyến mãi và sự kiện từ nhà hàng.</p>
                    <form class="subscribe-form">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Email của bạn" required>
                            <button class="btn btn-primary" type="submit">Đăng ký</button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="<?= BASE_URL ?>/privacy-policy.php" class="text-white">Chính sách bảo mật</a></li>
                        <li class="list-inline-item"><a href="<?= BASE_URL ?>/terms.php" class="text-white">Điều khoản</a></li>
                        <li class="list-inline-item"><a href="<?= BASE_URL ?>/sitemap.php" class="text-white">Sơ đồ website</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    
    <?php if (isset($customJs)): ?>
        <!-- Page-specific JS -->
        <script src="<?= BASE_URL ?>/assets/js/<?= htmlspecialchars($customJs) ?>"></script>
    <?php endif; ?>
    
    <?php if (APP_ENV === 'production' && getSetting('google_analytics_id')): ?>
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars(getSetting('google_analytics_id')) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= htmlspecialchars(getSetting('google_analytics_id')) ?>');
        </script>
    <?php endif; ?>
</body>
</html>
<script src="js/cart.js" defer></script>