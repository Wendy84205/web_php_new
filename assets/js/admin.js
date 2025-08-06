document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('active');
        });
    }
    
    // Dashboard charts
    if (document.getElementById('ordersChart')) {
        renderDashboardCharts();
    }
    
    // Data tables
    if (document.querySelector('.admin-table')) {
        initDataTables();
    }
    
    // Form validation
    const adminForms = document.querySelectorAll('.admin-form');
    adminForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // Image upload preview
    const imageUploads = document.querySelectorAll('.image-upload');
    imageUploads.forEach(upload => {
        upload.addEventListener('change', function() {
            const preview = this.closest('.form-group').querySelector('.image-preview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // Toggle password visibility
    const passwordToggles = document.querySelectorAll('.toggle-password');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('visible');
        });
    });
    
    // Initialize datepickers
    const datepickers = document.querySelectorAll('.datepicker');
    datepickers.forEach(dp => {
        new Pikaday({
            field: dp,
            format: 'YYYY-MM-DD',
            i18n: {
                previousMonth: 'Previous Month',
                nextMonth: 'Next Month',
                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                weekdays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                weekdaysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
            }
        });
    });
    
    // Functions
    function renderDashboardCharts() {
        // Orders chart
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Orders',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    backgroundColor: 'rgba(142, 36, 170, 0.2)',
                    borderColor: 'rgba(142, 36, 170, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Revenue chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['Food', 'Drinks', 'Desserts', 'Combos'],
                datasets: [{
                    label: 'Revenue by Category',
                    data: [12500000, 5800000, 3200000, 7500000],
                    backgroundColor: [
                        'rgba(142, 36, 170, 0.7)',
                        'rgba(106, 27, 154, 0.7)',
                        'rgba(67, 160, 71, 0.7)',
                        'rgba(251, 140, 0, 0.7)'
                    ],
                    borderColor: [
                        'rgba(142, 36, 170, 1)',
                        'rgba(106, 27, 154, 1)',
                        'rgba(67, 160, 71, 1)',
                        'rgba(251, 140, 0, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₫' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₫' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    
    function initDataTables() {
        $('.admin-table').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            pageLength: 25
        });
    }
    
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                isValid = false;
                
                // Add error message if not exists
                if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.textContent = 'This field is required';
                    field.parentNode.insertBefore(errorMsg, field.nextSibling);
                }
            } else {
                field.classList.remove('error');
                const errorMsg = field.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('error-message')) {
                    errorMsg.remove();
                }
            }
        });
        
        return isValid;
    }
    
    // Order status update
    const statusSelects = document.querySelectorAll('.order-status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            
            updateOrderStatus(orderId, newStatus);
        });
    });
    
    function updateOrderStatus(orderId, newStatus) {
        // Show loading
        const statusCell = document.querySelector(`.order-status[data-order-id="${orderId}"]`);
        const originalStatus = statusCell.innerHTML;
        statusCell.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Simulate API call
        setTimeout(() => {
            // In real app, this would be a fetch call
            // fetch(`api/update-order-status.php?order_id=${orderId}&status=${newStatus}`)
            
            // Update UI
            statusCell.className = `order-status ${newStatus}`;
            statusCell.innerHTML = `
                <span class="status-text">${formatStatus(newStatus)}</span>
                <small>${new Date().toLocaleString()}</small>
            `;
            
            // Show notification
            showNotification('Order status updated successfully', 'success');
        }, 1000);
    }
    
    function formatStatus(status) {
        return status.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="icon-${type === 'success' ? 'check' : 'error'}"></i>
            ${message}
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }, 10);
    }
});