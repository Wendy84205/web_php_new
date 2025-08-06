document.addEventListener('DOMContentLoaded', function () {
    // Initialize form validation
    const checkoutForm = document.getElementById('checkout-form');
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const deliveryTimeSelect = document.getElementById('delivery_time');

    // Set default delivery time to nearest 30 minutes
    const now = new Date();
    const roundedMinutes = Math.ceil(now.getMinutes() / 30) * 30;
    now.setMinutes(roundedMinutes);
    if (roundedMinutes >= 60) {
        now.setHours(now.getHours() + 1);
        now.setMinutes(0);
    }
    const defaultTime = now.toISOString().slice(0, 16);
    deliveryTimeSelect.min = defaultTime;
    deliveryTimeSelect.value = defaultTime;

    // Address selection
    const addressSelect = document.getElementById('address_id');
    const newAddressForm = document.getElementById('new-address-form');

    if (addressSelect) {
        addressSelect.addEventListener('change', function () {
            if (this.value === 'new') {
                newAddressForm.style.display = 'block';
            } else {
                newAddressForm.style.display = 'none';
            }
        });
    }

    // Payment method selection
    paymentMethods.forEach(method => {
        method.addEventListener('change', function () {
            document.querySelectorAll('.payment-method-details').forEach(detail => {
                detail.style.display = 'none';
            });

            const detailsId = this.id + '_details';
            const detailsElement = document.getElementById(detailsId);
            if (detailsElement) {
                detailsElement.style.display = 'block';
            }
        });
    });

    // Form submission
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate form
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });

            if (isValid) {
                // Process checkout
                processCheckout(this);
            } else {
                alert('Please fill in all required fields');
            }
        });
    }

    function processCheckout(form) {
        const formData = new FormData(form);
        const cart = JSON.parse(localStorage.getItem('comNieuCart')) || [];

        // Add cart items to form data
        formData.append('cart_items', JSON.stringify(cart));

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';

        // Simulate API call (replace with actual fetch)
        setTimeout(() => {
            // For demo purposes, just redirect to confirmation
            localStorage.removeItem('comNieuCart');
            window.location.href = 'order-confirmation.php?order_id=' + Math.floor(Math.random() * 1000000);

            // In real implementation:
            fetch('api/process-order.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        localStorage.removeItem('comNieuCart');
                        window.location.href = 'order-confirmation.php?order_id=' + data.order_id;
                    } else {
                        alert('Error: ' + data.message);
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalBtnText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                });
        }, 1500);
    }
});