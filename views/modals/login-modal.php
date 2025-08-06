<?php
// views/modals/login-modal.php
// Reusable login modal
?>

<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đăng nhập</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <div class="form-group">
                        <label for="loginEmail">Email</label>
                        <input type="email" class="form-control" id="loginEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Mật khẩu</label>
                        <input type="password" class="form-control" id="loginPassword" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="#" id="forgotPassword">Quên mật khẩu?</a>
                </div>
                
                <div class="social-login mt-4">
                    <p class="text-center">Hoặc đăng nhập bằng</p>
                    <div class="social-buttons">
                        <button class="btn btn-facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                        <button class="btn btn-google">
                            <i class="fab fa-google"></i> Google
                        </button>
                    </div>
                </div>
                
                <div class="register-link text-center mt-3">
                    Chưa có tài khoản? <a href="#" id="showRegister">Đăng ký ngay</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#loginModal .modal-content {
    border-radius: 10px;
    border: none;
}

#loginModal .modal-header {
    border-bottom: none;
    padding-bottom: 0;
}

#loginModal .modal-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
    width: 100%;
    text-align: center;
}

#loginModal .close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 1.5rem;
}

#loginModal .form-control {
    height: 45px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

#loginModal .btn-primary {
    background-color: #e67e22;
    border-color: #e67e22;
    height: 45px;
    font-weight: bold;
    border-radius: 5px;
}

#loginModal .btn-primary:hover {
    background-color: #d35400;
    border-color: #d35400;
}

#loginModal #forgotPassword {
    color: #666;
}

#loginModal #forgotPassword:hover {
    color: #e67e22;
    text-decoration: none;
}

.social-login p {
    color: #666;
    position: relative;
    margin-bottom: 20px;
}

.social-login p::before,
.social-login p::after {
    content: "";
    position: absolute;
    height: 1px;
    width: 30%;
    background-color: #ddd;
    top: 50%;
}

.social-login p::before {
    left: 0;
}

.social-login p::after {
    right: 0;
}

.social-buttons {
    display: flex;
    gap: 10px;
}

.btn-facebook, .btn-google {
    flex: 1;
    height: 45px;
    border-radius: 5px;
    font-weight: bold;
}

.btn-facebook {
    background-color: #3b5998;
    color: white;
}

.btn-google {
    background-color: #db4437;
    color: white;
}

.register-link {
    color: #666;
}

.register-link a {
    color: #e67e22;
    font-weight: bold;
}

.register-link a:hover {
    text-decoration: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle login form submission
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        const rememberMe = document.getElementById('rememberMe').checked;
        
        // Here you would typically make an AJAX call to your login endpoint
        console.log('Login attempt:', { email, password, rememberMe });
        
        // For demo purposes, we'll just close the modal after a short delay
        setTimeout(() => {
            $('#loginModal').modal('hide');
        }, 1000);
    });
    
    // Handle "Forgot password" click
    document.getElementById('forgotPassword').addEventListener('click', function(e) {
        e.preventDefault();
        $('#loginModal').modal('hide');
        // You would typically show a password reset modal here
        alert('Chức năng quên mật khẩu sẽ được hiển thị ở đây');
    });
    
    // Handle "Show register" click
    document.getElementById('showRegister').addEventListener('click', function(e) {
        e.preventDefault();
        $('#loginModal').modal('hide');
        // You would typically show a registration modal here
        alert('Chức năng đăng ký sẽ được hiển thị ở đây');
    });
});
</script>