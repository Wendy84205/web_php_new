<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Khởi tạo kết nối database
$db = getDBConnection();
function getDBConnection()
{
    $host = 'localhost';
    $dbname = 'com_nieu';
    $username = 'root';
    $password = '08042005';

    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user_id'];
$user = null;
$addresses = [];
$orders = [];
$error = '';

try {
    // Get user information
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Get user addresses
    $stmt = $db->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user orders
    $stmt = $db->prepare("
        SELECT o.order_id, o.order_number, o.order_date, o.total_amount, o.order_status, 
               COUNT(oi.order_item_id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "An error occurred while fetching your account information.";
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);

        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, phone = ?, updated_at = NOW() 
                WHERE user_id = ?
            ");
            $stmt->execute([$first_name, $last_name, $phone, $user_id]);

            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: my-account.php");
            exit();

        } catch (PDOException $e) {
            $error = "An error occurred while updating your profile.";
        }
    }

    if (isset($_POST['update_password'])) {
        // Update password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!password_verify($current_password, $user['password_hash'])) {
            $error = "Current password is incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            try {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->execute([$new_hash, $user_id]);

                $_SESSION['success'] = "Password updated successfully!";
                header("Location: my-account.php");
                exit();

            } catch (PDOException $e) {
                $error = "An error occurred while updating your password.";
            }
        }
    }

    if (isset($_POST['add_address'])) {
        // Add new address
        $address_type = $_POST['address_type'];
        $address_line1 = trim($_POST['address_line1']);
        $address_line2 = trim($_POST['address_line2']);
        $city = trim($_POST['city']);
        $district = trim($_POST['district']);
        $ward = trim($_POST['ward']);
        $phone = trim($_POST['phone']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        $notes = trim($_POST['notes']);

        // Basic validation
        if (empty($address_line1) || empty($city) || empty($district) || empty($ward) || empty($phone)) {
            $error = "Please fill in all required address fields.";
        } else {
            try {
                // If setting as default, first unset any existing default
                if ($is_default) {
                    $stmt = $db->prepare("UPDATE customer_addresses SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }

                // For simplicity, we're using a fixed location. In a real app, you'd geocode the address.
                $location = "POINT(10.762622, 106.660172)"; // Example coordinates in Ho Chi Minh City

                $stmt = $db->prepare("
                    INSERT INTO customer_addresses 
                    (user_id, address_type, address_line1, address_line2, city, district, ward, phone, is_default, notes, location)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ST_GeomFromText(?))
                ");
                $stmt->execute([
                    $user_id,
                    $address_type,
                    $address_line1,
                    $address_line2,
                    $city,
                    $district,
                    $ward,
                    $phone,
                    $is_default,
                    $notes,
                    $location
                ]);

                $_SESSION['success'] = "Address added successfully!";
                header("Location: my-account.php");
                exit();

            } catch (PDOException $e) {
                $error = "An error occurred while adding your address.";
            }
        }
    }
}

// Set page title
$page_title = "My Account";

// Include header
include 'includes/header.php';
?>
<style>
    /* CSS cho trang my-account.php */

    /* Biến màu sắc */
    :root {
        --primary-color: #4361ee;
        --primary-light: #3f37c9;
        --secondary-color: #3a0ca3;
        --success-color: #4cc9f0;
        --danger-color: #f72585;
        --warning-color: #f8961e;
        --info-color: #4895ef;
        --light-color: #f8f9fa;
        --dark-color: #212529;
        --gray-color: #6c757d;
        --light-gray: #e9ecef;
        --white-color: #ffffff;
        --border-radius: 0.375rem;
        --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    /* Reset và base styles */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fa;
        color: var(--dark-color);
        line-height: 1.6;
        margin: 0;
        padding: 0;
    }

    /* Layout chính */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -15px;
        margin-left: -15px;
    }

    .col-md-4,
    .col-md-6,
    .col-md-8 {
        position: relative;
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
    }

    @media (min-width: 768px) {
        .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }

        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }

        .col-md-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
        }
    }

    /* Cards */
    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: var(--white-color);
        background-clip: border-box;
        border: 1px solid rgba(0, 0, 0, 0.125);
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
        box-shadow: var(--box-shadow);
    }

    .card-header {
        padding: 1rem 1.25rem;
        margin-bottom: 0;
        background-color: rgba(0, 0, 0, 0.03);
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }

    .card-header:first-child {
        border-radius: calc(var(--border-radius) - 1px) calc(var(--border-radius) - 1px) 0 0;
    }

    .card-header.bg-primary {
        background-color: var(--primary-color) !important;
        color: var(--white-color);
    }

    .card-body {
        flex: 1 1 auto;
        padding: 1.25rem;
    }

    .card-footer {
        padding: 0.75rem 1.25rem;
        background-color: rgba(0, 0, 0, 0.03);
        border-top: 1px solid rgba(0, 0, 0, 0.125);
    }

    .card-footer:last-child {
        border-radius: 0 0 calc(var(--border-radius) - 1px) calc(var(--border-radius) - 1px);
    }

    /* Avatar */
    .avatar-lg {
        width: 80px;
        height: 80px;
        font-size: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: var(--light-gray);
        color: var(--dark-color);
        margin: 0 auto;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    .badge.bg-primary {
        background-color: var(--primary-color);
        color: var(--white-color);
    }

    .badge.bg-secondary {
        background-color: var(--gray-color);
        color: var(--white-color);
    }

    .badge.bg-success {
        background-color: var(--success-color);
        color: var(--white-color);
    }

    .badge.bg-danger {
        background-color: var(--danger-color);
        color: var(--white-color);
    }

    .badge.bg-warning {
        background-color: var(--warning-color);
        color: var(--dark-color);
    }

    .badge.bg-info {
        background-color: var(--info-color);
        color: var(--white-color);
    }

    /* Buttons */
    .btn {
        display: inline-block;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        text-align: center;
        text-decoration: none;
        vertical-align: middle;
        cursor: pointer;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        border-radius: var(--border-radius);
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out,
            border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .btn-primary {
        color: var(--white-color);
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-light);
        border-color: var(--primary-light);
    }

    .btn-outline-primary {
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        color: var(--white-color);
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-outline-secondary {
        color: var(--gray-color);
        border-color: var(--gray-color);
    }

    .btn-outline-secondary:hover {
        color: var(--white-color);
        background-color: var(--gray-color);
        border-color: var(--gray-color);
    }

    .btn-outline-danger {
        color: var(--danger-color);
        border-color: var(--danger-color);
    }

    .btn-outline-danger:hover {
        color: var(--white-color);
        background-color: var(--danger-color);
        border-color: var(--danger-color);
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 0.2rem;
    }

    .btn-close {
        box-sizing: content-box;
        width: 1em;
        height: 1em;
        padding: 0.25em 0.25em;
        color: #000;
        background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
        border: 0;
        border-radius: 0.25rem;
        opacity: 0.5;
    }

    /* Forms */
    .form-label {
        margin-bottom: 0.5rem;
        display: inline-block;
    }

    .form-control {
        display: block;
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        appearance: none;
        border-radius: var(--border-radius);
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-control:focus {
        color: #212529;
        background-color: #fff;
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .form-select {
        display: block;
        width: 100%;
        padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
        border: 1px solid #ced4da;
        border-radius: var(--border-radius);
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        appearance: none;
    }

    .form-check {
        display: block;
        min-height: 1.5rem;
        padding-left: 1.5em;
        margin-bottom: 0.125rem;
    }

    .form-check-input {
        width: 1em;
        height: 1em;
        margin-top: 0.25em;
        vertical-align: top;
        background-color: #fff;
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        border: 1px solid rgba(0, 0, 0, 0.25);
        appearance: none;
    }

    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    /* Alerts */
    .alert {
        position: relative;
        padding: 1rem 1rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: var(--border-radius);
    }

    .alert-success {
        color: #0f5132;
        background-color: #d1e7dd;
        border-color: #badbcc;
    }

    .alert-danger {
        color: #842029;
        background-color: #f8d7da;
        border-color: #f5c2cb;
    }

    /* Tables */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        vertical-align: top;
        border-color: #dee2e6;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table>thead {
        vertical-align: bottom;
    }

    .table>tbody {
        vertical-align: inherit;
    }

    .table> :not(:first-child) {
        border-top: 2px solid currentColor;
    }

    /* List group */
    .list-group {
        display: flex;
        flex-direction: column;
        padding-left: 0;
        margin-bottom: 0;
        border-radius: var(--border-radius);
    }

    .list-group-item {
        position: relative;
        display: block;
        padding: 0.5rem 1rem;
        color: #212529;
        text-decoration: none;
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .list-group-item:first-child {
        border-top-left-radius: inherit;
        border-top-right-radius: inherit;
    }

    .list-group-item:last-child {
        border-bottom-right-radius: inherit;
        border-bottom-left-radius: inherit;
    }

    .list-group-item+.list-group-item {
        border-top-width: 0;
    }

    .list-group-flush {
        border-radius: 0;
    }

    .list-group-flush>.list-group-item {
        border-width: 0 0 1px;
    }

    .list-group-flush>.list-group-item:last-child {
        border-bottom-width: 0;
    }

    /* Modal */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1050;
        display: none;
        width: 100%;
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        outline: 0;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-dialog {
        position: relative;
        width: auto;
        margin: 0.5rem;
        pointer-events: none;
    }

    .modal.fade .modal-dialog {
        transition: transform 0.3s ease-out;
        transform: translate(0, -50px);
    }

    .modal.show .modal-dialog {
        transform: none;
    }

    .modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        pointer-events: auto;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0, 0, 0, 0.2);
        border-radius: var(--border-radius);
        outline: 0;
    }

    .modal-header {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1rem;
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: calc(var(--border-radius) - 1px);
        border-top-right-radius: calc(var(--border-radius) - 1px);
    }

    .modal-title {
        margin-bottom: 0;
        line-height: 1.5;
    }

    .modal-body {
        position: relative;
        flex: 1 1 auto;
        padding: 1rem;
    }

    .modal-footer {
        display: flex;
        flex-wrap: wrap;
        flex-shrink: 0;
        align-items: center;
        justify-content: flex-end;
        padding: 0.75rem;
        border-top: 1px solid #dee2e6;
        border-bottom-right-radius: calc(var(--border-radius) - 1px);
        border-bottom-left-radius: calc(var(--border-radius) - 1px);
    }

    /* Utility classes */
    .text-center {
        text-align: center !important;
    }

    .text-muted {
        color: var(--gray-color) !important;
    }

    .mb-0 {
        margin-bottom: 0 !important;
    }

    .mb-1 {
        margin-bottom: 0.25rem !important;
    }

    .mb-2 {
        margin-bottom: 0.5rem !important;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }

    .mb-4 {
        margin-bottom: 1.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .mt-5 {
        margin-top: 3rem !important;
    }

    .mb-5 {
        margin-bottom: 3rem !important;
    }

    .ms-2 {
        margin-left: 0.5rem !important;
    }

    .w-100 {
        width: 100% !important;
    }

    /* Responsive adjustments */
    @media (min-width: 576px) {
        .modal-dialog {
            max-width: 500px;
            margin: 1.75rem auto;
        }
    }

    @media (max-width: 767.98px) {
        .card-footer .btn {
            margin-bottom: 0.5rem;
            width: 100%;
        }

        .card-footer .btn:last-child {
            margin-bottom: 0;
        }
    }
</style>
<div class="container mt-5 mb-5">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']);
        unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Profile</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-initial rounded-circle bg-light text-dark d-flex align-items-center justify-content-center"
                                style="width: 80px; height: 80px; font-size: 2rem;">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-muted">Member since <?php echo format_date($user['created_at'], false); ?></p>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Orders</span>
                            <span class="badge bg-primary rounded-pill"><?php echo count($orders); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Addresses</span>
                            <span class="badge bg-primary rounded-pill"><?php echo count($addresses); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Security</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-primary w-100">Update
                            Password</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                    value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                    value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small class="text-muted">Contact support to change your email</small>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <button class="btn btn-primary" id="openModalBtn">
                <i class="fas fa-plus"></i> Add New Address
            </button>

            <!-- Modal Add Address -->
            <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="addAddressForm" method="post" action="save_address.php">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Address Type</label>
                                    <select class="form-select" name="address_type" required>
                                        <option value="Home">Home</option>
                                        <option value="Work">Work</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control" name="address_line1" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Address Line 2 (Optional)</label>
                                    <input type="text" class="form-control" name="address_line2">
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">District</label>
                                        <input type="text" class="form-control" name="district" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Ward</label>
                                        <input type="text" class="form-control" name="ward" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Delivery Notes (Optional)</label>
                                    <textarea class="form-control" name="notes" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Location (required)</label>
                                    <input type="text" class="form-control" name="location"
                                        placeholder="Ví dụ: Nhà riêng, gần công viên..." required>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1"
                                        id="setDefaultAddress">
                                    <label class="form-check-label" for="setDefaultAddress">
                                        Set as default address
                                    </label>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Address</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <p class="text-muted">You haven't placed any orders yet.</p>
                        <a href="menu.php" class="btn btn-primary">Browse Menu</a>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo format_date($order['order_date']); ?></td>
                                            <td><?php echo $order['item_count']; ?></td>
                                            <td><?php echo format_currency($order['total_amount']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php
                                                    switch ($order['order_status']) {
                                                        case 'pending':
                                                            echo 'bg-secondary';
                                                            break;
                                                        case 'confirmed':
                                                            echo 'bg-info';
                                                            break;
                                                        case 'preparing':
                                                            echo 'bg-warning';
                                                            break;
                                                        case 'ready':
                                                            echo 'bg-primary';
                                                            break;
                                                        case 'on_delivery':
                                                            echo 'bg-primary';
                                                            break;
                                                        case 'delivered':
                                                            echo 'bg-success';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'bg-danger';
                                                            break;
                                                        default:
                                                            echo 'bg-secondary';
                                                    }
                                                    ?>
                                                ">
                                                    <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order-tracking.php?order_id=<?php echo $order['order_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="order-history.php" class="btn btn-outline-primary">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="address_type" class="form-label">Address Type</label>
                        <select class="form-select" id="address_type" name="address_type" required>
                            <option value="home">Home</option>
                            <option value="work">Work</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="address_line1" class="form-label">Address Line 1</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                    </div>
                    <div class="mb-3">
                        <label for="address_line2" class="form-label">Address Line 2 (Optional)</label>
                        <input type="text" class="form-control" id="address_line2" name="address_line2">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>
                        <div class="col-md-4">
                            <label for="district" class="form-label">District</label>
                            <input type="text" class="form-control" id="district" name="district" required>
                        </div>
                        <div class="col-md-4">
                            <label for="ward" class="form-label">Ward</label>
                            <input type="text" class="form-control" id="ward" name="ward" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Delivery Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                        <label class="form-check-label" for="is_default">Set as default address</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_address" class="btn btn-primary">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- FontAwesome (biểu tượng) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Khởi tạo và mở modal khi nhấn nút
    document.addEventListener('DOMContentLoaded', function () {
        const openBtn = document.getElementById('openModalBtn');
        const modalElement = document.getElementById('addAddressModal');
        const modal = new bootstrap.Modal(modalElement);

        openBtn.addEventListener('click', () => {
            modal.show();
        });
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>