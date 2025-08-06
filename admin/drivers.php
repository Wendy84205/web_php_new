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
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Search and filter variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$availability = isset($_GET['availability']) ? $_GET['availability'] : 'all';

// Build query
$query = "SELECT 
            u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
            u.is_active, u.created_at,
            d.driver_id, d.vehicle_type, d.vehicle_number, d.license_number, 
            d.is_available, d.rating
          FROM users u
          JOIN drivers d ON u.user_id = d.driver_id
          WHERE u.role = 'driver'";

$params = [];
$where = [];

// Apply search filter
if (!empty($search)) {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR 
                u.last_name LIKE ? OR u.phone LIKE ? OR d.vehicle_number LIKE ? OR 
                d.license_number LIKE ?)";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, 
               $searchTerm, $searchTerm, $searchTerm);
}

// Apply status filter
if ($status !== 'all') {
    $where[] = "u.is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
}

// Apply availability filter
if ($availability !== 'all') {
    $where[] = "d.is_available = ?";
    $params[] = ($availability === 'available') ? 1 : 0;
}

// Combine where clauses
if (!empty($where)) {
    $query .= " AND " . implode(" AND ", $where);
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) 
               FROM users u
               JOIN drivers d ON u.user_id = d.driver_id
               WHERE u.role = 'driver'";
if (!empty($where)) {
    $countQuery .= " AND " . implode(" AND ", $where);
}

$total = $pdo->prepare($countQuery);
$total->execute($params);
$totalDrivers = $total->fetchColumn();

// Add sorting and pagination
$query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
array_push($params, $perPage, $offset);

// Fetch drivers
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pagination
$totalPages = ceil($totalDrivers / $perPage);
$prevPage = ($page > 1) ? $page - 1 : null;
$nextPage = ($page < $totalPages) ? $page + 1 : null;

// Handle driver status toggle
if (isset($_POST['toggle_status'])) {
    $driverId = intval($_POST['driver_id']);
    $newStatus = intval($_POST['new_status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ? AND role = 'driver'");
        $stmt->execute([$newStatus, $driverId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Driver status updated successfully.";
            header("Location: drivers.php?page=" . $page . "&search=" . urlencode($search) . 
                  "&status=" . $status . "&availability=" . $availability);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Failed to update driver status: " . $e->getMessage();
    }
}

// Handle driver availability toggle
if (isset($_POST['toggle_availability'])) {
    $driverId = intval($_POST['driver_id']);
    $newAvailability = intval($_POST['new_availability']);
    
    try {
        $stmt = $pdo->prepare("UPDATE drivers SET is_available = ? WHERE driver_id = ?");
        $stmt->execute([$newAvailability, $driverId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Driver availability updated successfully.";
            header("Location: drivers.php?page=" . $page . "&search=" . urlencode($search) . 
                  "&status=" . $status . "&availability=" . $availability);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Failed to update driver availability: " . $e->getMessage();
    }
}

// Page header
$pageTitle = "Manage Drivers";
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
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="search" placeholder="Search drivers..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="availability">
                                <option value="all" <?= $availability === 'all' ? 'selected' : '' ?>>All Availability</option>
                                <option value="available" <?= $availability === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="unavailable" <?= $availability === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="drivers.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Drivers Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Total Drivers: <?= $totalDrivers ?></span>
                    <a href="driver-add.php" class="btn btn-success btn-sm">Add New Driver</a>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Driver</th>
                                    <th>Vehicle Info</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Availability</th>
                                    <th>Rating</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($drivers)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No drivers found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($drivers as $driver): ?>
                                        <tr>
                                            <td><?= $driver['driver_id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($driver['first_name'] . ' ' . htmlspecialchars($driver['last_name'])) ?>
                                                <br>
                                                <small class="text-muted">@<?= htmlspecialchars($driver['username']) ?></small>
                                            </td>
                                            <td>
                                                <strong>Type:</strong> <?= htmlspecialchars($driver['vehicle_type']) ?><br>
                                                <strong>Number:</strong> <?= htmlspecialchars($driver['vehicle_number']) ?><br>
                                                <strong>License:</strong> <?= htmlspecialchars($driver['license_number']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($driver['email']) ?><br>
                                                <?= htmlspecialchars($driver['phone']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $driver['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $driver['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $driver['is_available'] ? 'info' : 'secondary' ?>">
                                                    <?= $driver['is_available'] ? 'Available' : 'Unavailable' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i <= round($driver['rating']) ? 'text-warning' : 'text-secondary' ?>"></i>
                                                    <?php endfor; ?>
                                                    <small class="text-muted">(<?= number_format($driver['rating'], 1) ?>)</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="driver-view.php?id=<?= $driver['driver_id'] ?>" 
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="driver-edit.php?id=<?= $driver['driver_id'] ?>" 
                                                       class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="driver_id" value="<?= $driver['driver_id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $driver['is_active'] ? 0 : 1 ?>">
                                                        <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $driver['is_active'] ? 'warning' : 'success' ?>" 
                                                                title="<?= $driver['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                            <i class="fas fa-<?= $driver['is_active'] ? 'times' : 'check' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="driver_id" value="<?= $driver['driver_id'] ?>">
                                                        <input type="hidden" name="new_availability" value="<?= $driver['is_available'] ? 0 : 1 ?>">
                                                        <button type="submit" name="toggle_availability" class="btn btn-sm btn-<?= $driver['is_available'] ? 'secondary' : 'info' ?>" 
                                                                title="<?= $driver['is_available'] ? 'Set Unavailable' : 'Set Available' ?>">
                                                            <i class="fas fa-<?= $driver['is_available'] ? 'ban' : 'check-circle' ?>"></i>
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
                                        <a class="page-link" href="drivers.php?page=<?= $prevPage ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>&availability=<?= $availability ?>">
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
                                        <a class="page-link" href="drivers.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>&availability=<?= $availability ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($nextPage): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="drivers.php?page=<?= $nextPage ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>&availability=<?= $availability ?>">
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