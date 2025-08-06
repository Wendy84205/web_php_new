document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all payment forms
            document.querySelectorAll('.payment-method-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Show selected payment form
            const formId = this.id + '-form';
            const paymentForm = document.getElementById(formId);
            if (paymentForm) {
                paymentForm.style.display = 'block';
            }
        });
    });
    
    // Momo payment handler
    const momoForm = document.getElementById('momo-form');
    if (momoForm) {
        momoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            processMomoPayment(this);
        });
    }
    
    // VNPay payment handler
    const vnpayForm = document.getElementById('vnpay-form');
    if (vnpayForm) {
        vnpayForm.addEventListener('submit', function(e) {
            e.preventDefault();
            processVNPayPayment(this);
        });
    }
    
    function processMomoPayment(form) {
        const formData = new FormData(form);
        const phone = formData.get('momo_phone');
        const amount = formData.get('amount');
        
        // Validate phone number
        if (!/^(0|\+84)\d{9,10}$/.test(phone)) {
            alert('Please enter a valid Vietnamese phone number');
            return;
        }
        
        // Show processing UI
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        
        // Simulate API call (replace with actual implementation)
        setTimeout(() => {
            // In real app, this would redirect to MoMo payment gateway
            // For demo, we'll simulate a successful payment
            simulatePaymentCallback('momo', true);
            
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }, 2000);
    }
    
    function processVNPayPayment(form) {
        const formData = new FormData(form);
        const bankCode = formData.get('vnpay_bank_code');
        
        // Show processing UI
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        
        // Simulate API call (replace with actual implementation)
        setTimeout(() => {
            // In real app, this would redirect to VNPay payment gateway
            // For demo, we'll simulate a successful payment
            simulatePaymentCallback('vnpay', true);
            
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }, 2000);
    }
    
    function simulatePaymentCallback(method, success) {
        // In real app, this would be handled by the payment gateway callback
        // For demo, we'll update the UI directly
        const paymentStatus = document.getElementById('payment-status');
        
        if (success) {
            paymentStatus.innerHTML = `
                <div class="alert alert-success">
                    <i class="icon-check"></i>
                    Payment via ${method.toUpperCase()} successful!
                    Redirecting to order confirmation...
                </div>
            `;
            
            // Redirect to confirmation page
            setTimeout(() => {
                window.location.href = 'order-confirmation.php?payment_method=' + method;
            }, 3000);
        } else {
            paymentStatus.innerHTML = `
                <div class="alert alert-danger">
                    <i class="icon-error"></i>
                    Payment failed. Please try another method.
                </div>
            `;
        }
    }
});