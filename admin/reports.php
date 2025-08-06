<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Date range filter (default to current month)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales_summary';

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
}

// Ensure end date is not before start date
if (strtotime($end_date) < strtotime($start_date)) {
    $end_date = $start_date;
}

// Initialize report data
$reportData = [];
$chartData = [];

try {
    switch ($report_type) {
        case 'sales_summary':
            // Sales summary report
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(o.order_date) AS order_day,
                    COUNT(*) AS order_count,
                    SUM(o.total_amount) AS total_sales,
                    AVG(o.total_amount) AS avg_order_value
                FROM orders o
                WHERE o.order_date BETWEEN ? AND ? + INTERVAL 1 DAY
                AND o.order_status = 'delivered'
                GROUP BY DATE(o.order_date)
                ORDER BY order_day
            ");
            $stmt->execute([$start_date, $end_date]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prepare chart data
            foreach ($reportData as $row) {
                $chartData['labels'][] = $row['order_day'];
                $chartData['sales'][] = $row['total_sales'];
                $chartData['orders'][] = $row['order_count'];
            }
            break;

        case 'product_performance':
            // Product performance report
            $stmt = $pdo->prepare("
                SELECT 
                    mi.item_id,
                    mi.name,
                    mi.category_id,
                    mc.name AS category_name,
                    SUM(oi.quantity) AS total_quantity,
                    SUM(oi.quantity * oi.unit_price) AS total_revenue,
                    COUNT(DISTINCT o.order_id) AS order_count
                FROM order_items oi
                JOIN menu_items mi ON oi.item_id = mi.item_id
                JOIN menu_categories mc ON mi.category_id = mc.category_id
                JOIN orders o ON oi.order_id = o.order_id
                WHERE o.order_date BETWEEN ? AND ? + INTERVAL 1 DAY
                AND o.order_status = 'delivered'
                GROUP BY mi.item_id
                ORDER BY total_revenue DESC
                LIMIT 20
            ");
            $stmt->execute([$start_date, $end_date]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prepare chart data
            foreach ($reportData as $row) {
                $chartData['labels'][] = $row['name'];
                $chartData['revenue'][] = $row['total_revenue'];
                $chartData['quantity'][] = $row['total_quantity'];
            }
            break;

        case 'customer_orders':
            // Customer orders report
            $stmt = $pdo->prepare("
                SELECT 
                    u.user_id,
                    u.username,
                    u.first_name,
                    u.last_name,
                    u.email,
                    COUNT(o.order_id) AS order_count,
                    SUM(o.total_amount) AS total_spent,
                    MAX(o.order_date) AS last_order_date
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.order_date BETWEEN ? AND ? + INTERVAL 1 DAY
                AND o.order_status = 'delivered'
                GROUP BY u.user_id
                ORDER BY total_spent DESC
                LIMIT 20
            ");
            $stmt->execute([$start_date, $end_date]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'delivery_performance':
            // Delivery performance report
            $stmt = $pdo->prepare("
                SELECT 
                    d.driver_id,
                    u.first_name,
                    u.last_name,
                    COUNT(o.order_id) AS delivery_count,
                    AVG(TIMESTAMPDIFF(MINUTE, 
                        (SELECT MIN(created_at) FROM order_status_history 
                         WHERE order_id = o.order_id AND status = 'ready'), 
                        (SELECT MIN(created_at) FROM order_status_history 
                         WHERE order_id = o.order_id AND status = 'delivered'))) AS avg_delivery_time,
                    AVG(o.total_amount) AS avg_order_value,
                    SUM(o.total_amount) AS total_value
                FROM orders o
                JOIN drivers d ON o.driver_id = d.driver_id
                JOIN users u ON d.driver_id = u.user_id
                WHERE o.order_date BETWEEN ? AND ? + INTERVAL 1 DAY
                AND o.order_status = 'delivered'
                GROUP BY d.driver_id
                ORDER BY delivery_count DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prepare chart data
            foreach ($reportData as $row) {
                $chartData['labels'][] = $row['first_name'] . ' ' . $row['last_name'];
                $chartData['deliveries'][] = $row['delivery_count'];
                $chartData['avg_time'][] = $row['avg_delivery_time'];
            }
            break;

        default:
            $report_type = 'sales_summary';
            header("Location: report.php?report_type=sales_summary&start_date=$start_date&end_date=$end_date");
            exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Calculate totals for summary cards
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_orders,
            SUM(total_amount) AS total_sales,
            AVG(total_amount) AS avg_order_value,
            COUNT(DISTINCT user_id) AS unique_customers
        FROM orders
        WHERE order_date BETWEEN ? AND ? + INTERVAL 1 DAY
        AND order_status = 'delivered'
    ");
    $stmt->execute([$start_date, $end_date]);
    $summaryData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $summaryData = [
        'total_orders' => 0,
        'total_sales' => 0,
        'avg_order_value' => 0,
        'unique_customers' => 0
    ];
}

// Page header
$pageTitle = "Sales Reports";
require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            
            <!-- Report Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type">
                                <option value="sales_summary" <?= $report_type === 'sales_summary' ? 'selected' : '' ?>>Sales Summary</option>
                                <option value="product_performance" <?= $report_type === 'product_performance' ? 'selected' : '' ?>>Product Performance</option>
                                <option value="customer_orders" <?= $report_type === 'customer_orders' ? 'selected' : '' ?>>Customer Orders</option>
                                <option value="delivery_performance" <?= $report_type === 'delivery_performance' ? 'selected' : '' ?>>Delivery Performance</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?= htmlspecialchars($start_date) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?= htmlspecialchars($end_date) ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Generate</button>
                            <button type="button" id="export-btn" class="btn btn-success">Export</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Orders</h5>
                            <h2 class="card-text"><?= number_format($summaryData['total_orders']) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <h2 class="card-text">$<?= number_format($summaryData['total_sales'], 2) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Avg. Order Value</h5>
                            <h2 class="card-text">$<?= number_format($summaryData['avg_order_value'], 2) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Unique Customers</h5>
                            <h2 class="card-text"><?= number_format($summaryData['unique_customers']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chart Section -->
            <?php if (!empty($chartData)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="chart-container" style="height: 400px;">
                            <canvas id="reportChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Report Data Table -->
            <div class="card">
                <div class="card-header">
                    <?= ucfirst(str_replace('_', ' ', $report_type)) ?> Report
                    <span class="float-end">
                        <?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <?php switch ($report_type):
                                        case 'sales_summary': ?>
                                            <th>Date</th>
                                            <th>Orders</th>
                                            <th>Total Sales</th>
                                            <th>Avg. Order Value</th>
                                            <?php break; ?>
                                        <?php case 'product_performance': ?>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Quantity Sold</th>
                                            <th>Total Revenue</th>
                                            <th>Orders</th>
                                            <?php break; ?>
                                        <?php case 'customer_orders': ?>
                                            <th>Customer</th>
                                            <th>Email</th>
                                            <th>Orders</th>
                                            <th>Total Spent</th>
                                            <th>Last Order</th>
                                            <?php break; ?>
                                        <?php case 'delivery_performance': ?>
                                            <th>Driver</th>
                                            <th>Deliveries</th>
                                            <th>Avg. Delivery Time (min)</th>
                                            <th>Avg. Order Value</th>
                                            <th>Total Value</th>
                                            <?php break; ?>
                                    <?php endswitch; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No data available for the selected period</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <?php switch ($report_type):
                                                case 'sales_summary': ?>
                                                    <td><?= date('M j, Y', strtotime($row['order_day'])) ?></td>
                                                    <td><?= $row['order_count'] ?></td>
                                                    <td>$<?= number_format($row['total_sales'], 2) ?></td>
                                                    <td>$<?= number_format($row['avg_order_value'], 2) ?></td>
                                                    <?php break; ?>
                                                <?php case 'product_performance': ?>
                                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                                                    <td><?= $row['total_quantity'] ?></td>
                                                    <td>$<?= number_format($row['total_revenue'], 2) ?></td>
                                                    <td><?= $row['order_count'] ?></td>
                                                    <?php break; ?>
                                                <?php case 'customer_orders': ?>
                                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                                    <td><?= $row['order_count'] ?></td>
                                                    <td>$<?= number_format($row['total_spent'], 2) ?></td>
                                                    <td><?= date('M j, Y', strtotime($row['last_order_date'])) ?></td>
                                                    <?php break; ?>
                                                <?php case 'delivery_performance': ?>
                                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                                    <td><?= $row['delivery_count'] ?></td>
                                                    <td><?= number_format($row['avg_delivery_time'], 1) ?></td>
                                                    <td>$<?= number_format($row['avg_order_value'], 2) ?></td>
                                                    <td>$<?= number_format($row['total_value'], 2) ?></td>
                                                    <?php break; ?>
                                            <?php endswitch; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Export button functionality
document.getElementById('export-btn').addEventListener('click', function() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.location.href = 'export-report.php?' + params.toString();
});

// Initialize chart if data exists
<?php if (!empty($chartData)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('reportChart').getContext('2d');
    
    <?php switch ($report_type):
        case 'sales_summary': ?>
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartData['labels']) ?>,
                    datasets: [
                        {
                            label: 'Total Sales ($)',
                            data: <?= json_encode($chartData['sales']) ?>,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            yAxisID: 'y',
                            tension: 0.3
                        },
                        {
                            label: 'Number of Orders',
                            data: <?= json_encode($chartData['orders']) ?>,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            yAxisID: 'y1',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Sales ($)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Orders'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            <?php break; ?>
        <?php case 'product_performance': ?>
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chartData['labels']) ?>,
                    datasets: [
                        {
                            label: 'Revenue ($)',
                            data: <?= json_encode($chartData['revenue']) ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Quantity Sold',
                            data: <?= json_encode($chartData['quantity']) ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Quantity'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            <?php break; ?>
        <?php case 'delivery_performance': ?>
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chartData['labels']) ?>,
                    datasets: [
                        {
                            label: 'Deliveries',
                            data: <?= json_encode($chartData['deliveries']) ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Avg. Time (min)',
                            data: <?= json_encode($chartData['avg_time']) ?>,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Deliveries'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Time (minutes)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            <?php break; ?>
    <?php endswitch; ?>
});
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>