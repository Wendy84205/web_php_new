<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Pagination variables
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filter variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT user_id, username, email, first_name, last_name, phone, address, is_active, created_at 
          FROM users 
          WHERE role = 'customer'";

$params = [];
$where = [];

// Apply search filter
if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

// Apply status filter
if ($status !== 'all') {
    $where[] = "is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
}

// Combine where clauses
if (!empty($where)) {
    $query .= " AND " . implode(" AND ", $where);
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM users WHERE role = 'customer'";
if (!empty($where)) {
    $countQuery .= " AND " . implode(" AND ", $where);
}

$total = $pdo->prepare($countQuery);
$total->execute($params);
$totalCustomers = $total->fetchColumn();

// Add sorting and pagination
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
array_push($params, $perPage, $offset);

// Fetch customers
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pagination
$totalPages = ceil($totalCustomers / $perPage);
$prevPage = ($page > 1) ? $page - 1 : null;
$nextPage = ($page < $totalPages) ? $page + 1 : null;

// Handle customer status toggle
if (isset($_POST['toggle_status'])) {
    $customerId = intval($_POST['customer_id']);
    $newStatus = intval($_POST['new_status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ? AND role = 'customer'");
        $stmt->execute([$newStatus, $customerId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Customer status updated successfully.";
            header("Location: customers.php?page=" . $page . "&search=" . urlencode($search) . "&status=" . $status);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Failed to update customer status: " . $e->getMessage();
    }
}

// Page header
$pageTitle = "Manage Customers";
require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Search customers..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="customers.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Customers Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Total Customers: <?= $totalCustomers ?></span>
                    <a href="customer-add.php" class="btn btn-success btn-sm">Add New Customer</a>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No customers found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?= $customer['user_id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($customer['first_name'] . ' ' . htmlspecialchars($customer['last_name'])) ?>
                                                <br>
                                                <small class="text-muted">@<?= htmlspecialchars($customer['username']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($customer['email']) ?></td>
                                            <td><?= htmlspecialchars($customer['phone']) ?></td>
                                            <td><?= date('M j, Y', strtotime($customer['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $customer['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $customer['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="customer-view.php?id=<?= $customer['user_id'] ?>" 
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="customer-edit.php?id=<?= $customer['user_id'] ?>" 
                                                       class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="customer_id" value="<?= $customer['user_id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $customer['is_active'] ? 0 : 1 ?>">
                                                        <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $customer['is_active'] ? 'warning' : 'success' ?>" 
                                                                title="<?= $customer['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                            <i class="fas fa-<?= $customer['is_active'] ? 'times' : 'check' ?>"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($prevPage): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="customers.php?page=<?= $prevPage ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">Previous</span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="customers.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($nextPage): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="customers.php?page=<?= $nextPage ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">Next</span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>