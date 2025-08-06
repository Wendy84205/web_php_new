document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    function initMap() {
        const orderId = document.getElementById('order-map').dataset.orderId;
        
        // Default to restaurant location
        const restaurantLocation = { lat: 16.047079, lng: 108.206230 }; // Da Nang coordinates
        const map = new google.maps.Map(document.getElementById('order-map'), {
            zoom: 13,
            center: restaurantLocation
        });
        
        // Add restaurant marker
        new google.maps.Marker({
            position: restaurantLocation,
            map: map,
            title: 'Com Nieu Restaurant',
            icon: {
                url: 'assets/images/restaurant-marker.png',
                scaledSize: new google.maps.Size(40, 40)
            }
        });
        
        // Get order location (simulated - in real app would fetch from API)
        simulateDeliveryTracking(map, orderId);
    }
    
    function simulateDeliveryTracking(map, orderId) {
        // In real app, this would be a WebSocket or API polling
        // For demo, we'll simulate movement
        
        const customerLocation = getRandomLocationNearby({ lat: 16.047079, lng: 108.206230 }, 5);
        
        // Add customer marker
        new google.maps.Marker({
            position: customerLocation,
            map: map,
            title: 'Delivery Destination',
            icon: {
                url: 'assets/images/home-marker.png',
                scaledSize: new google.maps.Size(40, 40)
            }
        });
        
        // Simulate driver movement
        const driverMarker = new google.maps.Marker({
            position: getRandomLocationNearby({ lat: 16.047079, lng: 108.206230 }, 2),
            map: map,
            title: 'Your Driver',
            icon: {
                url: 'assets/images/driver-marker.png',
                scaledSize: new google.maps.Size(40, 40)
            }
        });
        
        // Animate driver to customer
        const interval = setInterval(() => {
            const currentPos = driverMarker.getPosition();
            const newLat = currentPos.lat() + (customerLocation.lat - currentPos.lat()) / 10;
            const newLng = currentPos.lng() + (customerLocation.lng - currentPos.lng()) / 10;
            
            driverMarker.setPosition({ lat: newLat, lng: newLng });
            
            // Check if close to destination
            if (google.maps.geometry.spherical.computeDistanceBetween(
                driverMarker.getPosition(), 
                new google.maps.LatLng(customerLocation)
            ) < 100) {
                clearInterval(interval);
                updateDeliveryStatus('delivered');
            }
        }, 1000);
        
        // Draw route
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true
        });
        
        directionsService.route({
            origin: restaurantLocation,
            destination: customerLocation,
            travelMode: 'DRIVING'
        }, (response, status) => {
            if (status === 'OK') {
                directionsRenderer.setDirections(response);
            }
        });
    }
    
    function getRandomLocationNearby(center, radiusKm) {
        const radiusDegrees = radiusKm / 111.32; // approx km per degree
        const randomRadius = Math.random() * radiusDegrees;
        const randomAngle = Math.random() * 2 * Math.PI;
        
        return {
            lat: center.lat + randomRadius * Math.cos(randomAngle),
            lng: center.lng + randomRadius * Math.sin(randomAngle)
        };
    }
    
    function updateDeliveryStatus(status) {
        const statusElement = document.querySelector('.delivery-status');
        const statusText = document.querySelector('.status-text');
        
        statusElement.className = 'delivery-status ' + status;
        
        switch(status) {
            case 'preparing':
                statusText.textContent = 'Preparing your order';
                break;
            case 'on_way':
                statusText.textContent = 'Driver on the way';
                break;
            case 'nearby':
                statusText.textContent = 'Driver nearby';
                break;
            case 'delivered':
                statusText.textContent = 'Delivered! Enjoy your meal!';
                break;
        }
    }
    
    // Load Google Maps API
    if (document.getElementById('order-map')) {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=geometry&callback=initMap`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
        
        window.initMap = initMap;
    }
});