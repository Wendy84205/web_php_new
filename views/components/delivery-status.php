<?php
// views/components/delivery-status.php
// Reusable delivery status component

/**
 * @param array $tracking - Delivery tracking data
 * @param array $order - Order data
 */
?>

<div class="delivery-status">
    <h3>Tình trạng giao hàng</h3>
    
    <div class="status-timeline">
        <div class="timeline-step <?= $order['order_status'] === 'pending' ? 'active' : 'completed' ?>">
            <div class="step-icon">1</div>
            <div class="step-label">Đơn hàng đã đặt</div>
            <div class="step-time"><?= date('H:i', strtotime($order['order_date'])) ?></div>
        </div>
        
        <div class="timeline-step <?= in_array($order['order_status'], ['confirmed', 'preparing', 'ready', 'on_delivery', 'delivered']) ? 'completed' : '' ?> 
                                <?= $order['order_status'] === 'confirmed' ? 'active' : '' ?>">
            <div class="step-icon">2</div>
            <div class="step-label">Xác nhận đơn hàng</div>
            <?php if (in_array($order['order_status'], ['confirmed', 'preparing', 'ready', 'on_delivery', 'delivered'])): ?>
                <div class="step-time"><?= date('H:i', strtotime($orderStatusHistory['confirmed'] ?? $order['order_date'])) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="timeline-step <?= in_array($order['order_status'], ['preparing', 'ready', 'on_delivery', 'delivered']) ? 'completed' : '' ?> 
                                <?= $order['order_status'] === 'preparing' ? 'active' : '' ?>">
            <div class="step-icon">3</div>
            <div class="step-label">Đang chuẩn bị</div>
            <?php if (in_array($order['order_status'], ['preparing', 'ready', 'on_delivery', 'delivered'])): ?>
                <div class="step-time"><?= date('H:i', strtotime($orderStatusHistory['preparing'] ?? $order['order_date'])) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="timeline-step <?= in_array($order['order_status'], ['ready', 'on_delivery', 'delivered']) ? 'completed' : '' ?> 
                                <?= $order['order_status'] === 'ready' ? 'active' : '' ?>">
            <div class="step-icon">4</div>
            <div class="step-label">Sẵn sàng giao</div>
            <?php if (in_array($order['order_status'], ['ready', 'on_delivery', 'delivered'])): ?>
                <div class="step-time"><?= date('H:i', strtotime($orderStatusHistory['ready'] ?? $order['order_date'])) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="timeline-step <?= in_array($order['order_status'], ['on_delivery', 'delivered']) ? 'completed' : '' ?> 
                                <?= $order['order_status'] === 'on_delivery' ? 'active' : '' ?>">
            <div class="step-icon">5</div>
            <div class="step-label">Đang giao hàng</div>
            <?php if ($tracking && in_array($tracking['status'], ['picked_up', 'on_way', 'nearby', 'delivered'])): ?>
                <div class="step-time"><?= date('H:i', strtotime($tracking['updated_at'])) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="timeline-step <?= $order['order_status'] === 'delivered' ? 'completed' : '' ?> 
                                <?= $order['order_status'] === 'delivered' ? 'active' : '' ?>">
            <div class="step-icon">6</div>
            <div class="step-label">Giao hàng thành công</div>
            <?php if ($order['order_status'] === 'delivered'): ?>
                <div class="step-time"><?= date('H:i', strtotime($order['delivery_time'])) ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($tracking && $order['order_status'] === 'on_delivery'): ?>
        <div class="delivery-map-container">
            <h4>Theo dõi tài xế</h4>
            <div id="delivery-map" style="height: 300px; width: 100%;"></div>
            <div class="driver-info">
                <div class="driver-name">Tài xế: <?= htmlspecialchars($driver['first_name'] . ' ' . htmlspecialchars($driver['last_name'])) ?></div>
                <div class="driver-phone">Liên hệ: <?= htmlspecialchars($driver['phone']) ?></div>
                <div class="driver-vehicle">Phương tiện: <?= htmlspecialchars($driver['vehicle_type']) ?> (<?= htmlspecialchars($driver['vehicle_number']) ?>)</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.delivery-status {
    background-color: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.delivery-status h3 {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: #333;
}

.status-timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin-bottom: 30px;
}

.status-timeline::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 0;
    right: 0;
    height: 3px;
    background-color: #eee;
    z-index: 1;
}

.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
    flex: 1;
}

.step-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #eee;
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 10px;
}

.timeline-step.completed .step-icon {
    background-color: #4CAF50;
    color: white;
}

.timeline-step.active .step-icon {
    background-color: #2196F3;
    color: white;
}

.step-label {
    font-size: 0.9rem;
    text-align: center;
    color: #666;
    margin-bottom: 5px;
}

.step-time {
    font-size: 0.8rem;
    color: #999;
}

.delivery-map-container {
    margin-top: 30px;
}

.delivery-map-container h4 {
    font-size: 1.2rem;
    margin-bottom: 15px;
    color: #333;
}

.driver-info {
    margin-top: 15px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 5px;
}

.driver-info div {
    margin-bottom: 5px;
    color: #555;
}

.driver-info div:last-child {
    margin-bottom: 0;
}
</style>

<?php if ($tracking && $order['order_status'] === 'on_delivery'): ?>
<script>
// This would be implemented with your map API of choice (Google Maps, Mapbox, etc.)
function initDeliveryMap() {
    // Example using Google Maps
    const map = new google.maps.Map(document.getElementById('delivery-map'), {
        center: {lat: <?= $tracking['current_location']['lat'] ?>, lng: <?= $tracking['current_location']['lng'] ?>},
        zoom: 15
    });
    
    new google.maps.Marker({
        position: {lat: <?= $tracking['current_location']['lat'] ?>, lng: <?= $tracking['current_location']['lng'] ?>},
        map: map,
        title: "Tài xế của bạn"
    });
    
    // Add destination marker if you have that data
}
</script>
<?php endif; ?>