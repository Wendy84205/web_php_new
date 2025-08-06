<?php
// hero.php - Main banner section
require_once __DIR__ . '/../includes/db.php';
?>

<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1>NHÀ HÀNG CƠM NIÊU</h1>
            <p class="lead">Thực đơn đa dạng và phong phú với nhiều món ăn dân dã, được chế biến bởi đội ngũ đầu bếp nhiều năm kinh nghiệm</p>
            <div class="hero-buttons">
                <a href="#menu" class="btn btn-primary">Xem Thực Đơn</a>
                <a href="#reservation" class="btn btn-outline">Đặt Bàn Ngay</a>
            </div>
        </div>
    </div>
</section>

<style>
.hero-section {
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../assets/images/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    color: white;
    padding: 120px 0;
    text-align: center;
}

.hero-content h1 {
    font-size: 3.5rem;
    margin-bottom: 20px;
    text-transform: uppercase;
    font-weight: 700;
}

.hero-content .lead {
    font-size: 1.5rem;
    max-width: 800px;
    margin: 0 auto 30px;
}

.hero-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.btn {
    padding: 12px 30px;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #e67e22;
    color: white;
    border: 2px solid #e67e22;
}

.btn-primary:hover {
    background-color: transparent;
    color: #e67e22;
}

.btn-outline {
    background-color: transparent;
    color: white;
    border: 2px solid white;
}

.btn-outline:hover {
    background-color: white;
    color: #333;
}
</style>