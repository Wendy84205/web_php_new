<?php
// contact.php - Contact information section
?>

<section class="contact-section" id="contact">
    <div class="container">
        <div class="section-header">
            <h2>LIÊN HỆ</h2>
            <p>Chúng tôi luôn sẵn sàng phục vụ bạn</p>
        </div>
        
        <div class="contact-content">
            <div class="contact-info">
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h4>Địa chỉ</h4>
                        <p>Lô 42-43 Trần Bạch Đằng, Ngũ Hành Sơn, Đà Nẵng</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-phone-alt"></i>
                    <div>
                        <h4>Điện thoại</h4>
                        <p>078 866 1233</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h4>Email</h4>
                        <p>annamquandn@gmail.com</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h4>Giờ mở cửa</h4>
                        <p>Thứ 2 - Chủ nhật: 8:00 - 22:00</p>
                    </div>
                </div>
                
                <div class="social-links">
                    <a href="https://www.facebook.com/comnieuannam" target="_blank">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" target="_blank">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" target="_blank">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>
            
            <div class="contact-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3835.73883535685!2d108.25128631528797!3d15.975931588939832!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3142108997dc971f%3A0x1295cb3d313469c9!2zVHLhuqduIEJhY2ggxJDhuqNuZywgQuG6v24gTOG7hywgxJDDoCBO4bq1bmcgNTUwMDAwLCBWaeG7h3QgTmFt!5e0!3m2!1svi!2s!4v1620000000000!5m2!1svi!2s" 
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>

<style>
.contact-section {
    padding: 80px 0;
    background-color: white;
}

.contact-content {
    display: flex;
    gap: 50px;
    margin-top: 40px;
}

.contact-info {
    flex: 1;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 30px;
}

.info-item i {
    font-size: 1.5rem;
    color: #e67e22;
    margin-top: 5px;
}

.info-item h4 {
    font-size: 1.2rem;
    color: #333;
    margin-bottom: 5px;
}

.info-item p {
    color: #666;
    line-height: 1.6;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 40px;
}

.social-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: #f5f5f5;
    border-radius: 50%;
    color: #333;
    transition: all 0.3s ease;
}

.social-links a:hover {
    background-color: #e67e22;
    color: white;
    transform: translateY(-3px);
}

.contact-map {
    flex: 1;
    height: 400px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .contact-content {
        flex-direction: column;
    }
    
    .contact-map {
        height: 300px;
    }
}
</style>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">