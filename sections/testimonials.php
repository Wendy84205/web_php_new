<?php
// testimonials.php - Customer reviews section
require_once __DIR__ . '/../includes/db.php';

// Fetch approved reviews
try {
    $stmt = $pdo->query("
        SELECT r.*, u.first_name, u.last_name 
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.is_approved = TRUE
        ORDER BY r.review_date DESC
        LIMIT 5
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reviews = [];
}
?>

<section class="testimonials-section">
    <div class="container">
        <div class="section-header">
            <h2>KHÁCH HÀNG NÓI GÌ VỀ CHÚNG TÔI</h2>
            <p>Những đánh giá chân thực từ khách hàng</p>
        </div>
        
        <div class="testimonials-container">
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="testimonial-card">
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <p class="review-text">"<?= htmlspecialchars($review['comment']) ?>"</p>
                        <div class="reviewer">
                            <span class="reviewer-name"><?= htmlspecialchars($review['first_name'] . ' ' . htmlspecialchars($review['last_name'])) ?></span>
                            <span class="review-date"><?= date('d/m/Y', strtotime($review['review_date'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-reviews">Chưa có đánh giá nào.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.testimonials-section {
    padding: 80px 0;
    background-color: #f9f9f9;
}

.testimonials-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.testimonial-card {
    background-color: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.rating {
    margin-bottom: 15px;
    color: #e67e22;
    font-size: 1.2rem;
}

.star {
    color: #ddd;
}

.star.filled {
    color: #e67e22;
}

.review-text {
    font-style: italic;
    line-height: 1.6;
    color: #555;
    margin-bottom: 20px;
}

.reviewer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.reviewer-name {
    font-weight: 600;
    color: #333;
}

.review-date {
    color: #999;
    font-size: 0.9rem;
}

.no-reviews {
    text-align: center;
    grid-column: 1 / -1;
    color: #666;
    font-style: italic;
}
</style>