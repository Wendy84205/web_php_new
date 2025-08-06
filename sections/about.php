<?php
// about.php - About section
?>

<section class="about-section" id="about">
    <div class="container">
        <div class="section-header">
            <h2>COM NIEU</h2>
            <p>Cơm niêu là món ăn truyền thống của Việt Nam</p>
        </div>
        
        <div class="about-content">
            <div class="about-text">
                <p>Đây là loại cơm được nấu trong một cái niêu đất truyền thống. Cơm được nấu chín mềm và thơm ngon hơn so với cơm nấu bình thường. Ngoài ra, cơm niêu còn có một hương vị đặc trưng riêng, được tạo ra từ cách nấu của niêu đất.</p>
                
                <h3>NGON NHƯ BẾP NHÀ</h3>
                <p>Khi ăn, người ta thường dùng cả thịt, rau củ và gia vị để ăn cùng cơm niêu. Đặc biệt, với đầu bếp nhiều năm kinh nghiệm sẽ mang lại hương vị ngon như bếp nhà nấu.</p>
                
                <h3>BỮA CƠM ẤM CÚNG</h3>
                <p>Cơm niêu thường được dùng vào các dịp lễ tết hoặc trong các bữa tiệc gia đình. Ngoài ra, cơm niêu còn là một sự lựa chọn hoàn hảo cho những người yêu thích ẩm thực truyền thống của Việt Nam.</p>
            </div>
            
            <div class="about-image">
                <img src="../assets/images/about-com-nieu.jpg" alt="Món ăn Việt Nam">
            </div>
        </div>
    </div>
</section>

<style>
.about-section {
    padding: 80px 0;
    background-color: #f9f9f9;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 15px;
}

.section-header p {
    font-size: 1.2rem;
    color: #666;
}

.about-content {
    display: flex;
    align-items: center;
    gap: 50px;
}

.about-text {
    flex: 1;
}

.about-text h3 {
    font-size: 1.8rem;
    color: #e67e22;
    margin: 30px 0 15px;
}

.about-text p {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #555;
    margin-bottom: 20px;
}

.about-image {
    flex: 1;
}

.about-image img {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .about-content {
        flex-direction: column;
    }
}
</style>