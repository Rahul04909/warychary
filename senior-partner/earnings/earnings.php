<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['senior_partner_id'])) {
    header("Location: ../index.php");
    exit();
}

include '../../database/config.php';

$database = new Database();
$db = $database->getConnection();

// Get senior partner data from session
$senior_partner_id = $_SESSION['senior_partner_id'];
$senior_partner_name = $_SESSION['senior_partner_name'];
$senior_partner_email = $_SESSION['senior_partner_email'];

// Debug: Add some debugging information (remove in production)
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($debug_mode) {
    echo "<div class='alert alert-info'>";
    echo "<h5>Debug Information:</h5>";
    echo "<p>Senior Partner ID: " . $senior_partner_id . "</p>";
    echo "<p>Senior Partner Name: " . $senior_partner_name . "</p>";
    echo "<p>Senior Partner Email: " . $senior_partner_email . "</p>";
    echo "</div>";
}

// Get filter parameters
$selected_period = $_GET['period'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build date filter condition
$date_condition = "";
$date_params = [];

switch ($selected_period) {
    case 'today':
        $date_condition = "AND DATE(spe.created_at) = CURDATE()";
        break;
    case 'week':
        $date_condition = "AND spe.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "AND spe.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'quarter':
        $date_condition = "AND spe.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        break;
    case 'year':
        $date_condition = "AND spe.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        break;
}

// Build search condition
$search_condition = "";
$search_params = [];
if (!empty($search_query)) {
    $search_condition = "AND (u.name LIKE :search OR u.email LIKE :search OR p.name LIKE :search)";
    $search_params[':search'] = '%' . $search_query . '%';
}

// Get total earnings for the senior partner
try {
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(spe.earning_amount), 0) as total_earnings,
            COUNT(DISTINCT spe.partner_id) as total_partners,
            COUNT(spe.id) as total_transactions,
            COALESCE(AVG(spe.earning_amount), 0) as avg_earning_per_transaction
        FROM senior_partner_earnings spe 
        WHERE spe.senior_partner_id = :senior_partner_id 
        $date_condition
    ");
    
    $params = array_merge([':senior_partner_id' => $senior_partner_id], $date_params);
    $stmt->execute($params);
    $earnings_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug information
    if ($debug_mode) {
        echo "<div class='alert alert-warning'>";
        echo "<h5>Earnings Stats Query Debug:</h5>";
        echo "<p>Query executed successfully</p>";
        echo "<p>Total Earnings: " . $earnings_stats['total_earnings'] . "</p>";
        echo "<p>Total Partners: " . $earnings_stats['total_partners'] . "</p>";
        echo "<p>Total Transactions: " . $earnings_stats['total_transactions'] . "</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    if ($debug_mode) {
        echo "<div class='alert alert-danger'>";
        echo "<h5>Earnings Stats Query Error:</h5>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    $earnings_stats = [
        'total_earnings' => 0,
        'total_partners' => 0,
        'total_transactions' => 0,
        'avg_earning_per_transaction' => 0
    ];
}

// Get earnings history with pagination
try {
    $stmt = $db->prepare("
        SELECT 
            spe.*,
            u.name as customer_name,
            u.email as customer_email,
            p.name as partner_name,
            p.partner_id as partner_code,
            o.total_amount as order_total
        FROM senior_partner_earnings spe
        LEFT JOIN users u ON spe.user_id = u.id
        LEFT JOIN partners p ON spe.partner_id = p.id
        LEFT JOIN orders o ON spe.order_id = o.id
        WHERE spe.senior_partner_id = :senior_partner_id 
        $date_condition 
        $search_condition
        ORDER BY spe.created_at DESC
        LIMIT " . intval($per_page) . " OFFSET " . intval($offset) . "
    ");
    
    $params = array_merge(
        [':senior_partner_id' => $senior_partner_id],
        $date_params,
        $search_params
    );
    
    $stmt->execute($params);
    $earnings_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug information
    if ($debug_mode) {
        echo "<div class='alert alert-success'>";
        echo "<h5>Earnings History Query Debug:</h5>";
        echo "<p>Query executed successfully</p>";
        echo "<p>Records found: " . count($earnings_history) . "</p>";
        echo "<p>Parameters: " . json_encode($params) . "</p>";
        if (!empty($earnings_history)) {
            echo "<p>First record: " . json_encode($earnings_history[0]) . "</p>";
        }
        echo "</div>";
    }
    
} catch (PDOException $e) {
    if ($debug_mode) {
        echo "<div class='alert alert-danger'>";
        echo "<h5>Earnings History Query Error:</h5>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    $earnings_history = [];
}

// Get total count for pagination
try {
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM senior_partner_earnings spe
        LEFT JOIN users u ON spe.user_id = u.id
        LEFT JOIN partners p ON spe.partner_id = p.id
        WHERE spe.senior_partner_id = :senior_partner_id 
        $date_condition 
        $search_condition
    ");
    
    $params = array_merge([':senior_partner_id' => $senior_partner_id], $date_params, $search_params);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
} catch (PDOException $e) {
    $total_records = 0;
    $total_pages = 1;
}

// Get monthly earnings trend for chart
try {
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(spe.created_at, '%Y-%m') as month,
            SUM(spe.earning_amount) as monthly_earnings,
            COUNT(spe.id) as monthly_transactions
        FROM senior_partner_earnings spe
        WHERE spe.senior_partner_id = :senior_partner_id 
        AND spe.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(spe.created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    
    $stmt->execute([':senior_partner_id' => $senior_partner_id]);
    $monthly_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $monthly_trend = [];
}

// Get top performing partners
try {
    $stmt = $db->prepare("
        SELECT 
            p.name as partner_name,
            p.partner_id as partner_code,
            SUM(spe.earning_amount) as total_earnings_from_partner,
            COUNT(spe.id) as total_transactions
        FROM senior_partner_earnings spe
        LEFT JOIN partners p ON spe.partner_id = p.id
        WHERE spe.senior_partner_id = :senior_partner_id 
        GROUP BY spe.partner_id
        ORDER BY total_earnings_from_partner DESC
        LIMIT 5
    ");
    
    $stmt->execute([':senior_partner_id' => $senior_partner_id]);
    $top_partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $top_partners = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Earnings - Senior Partner Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="../assets/css/senior-partner-style.css" rel="stylesheet">
    
    <style>
        .earnings-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .earnings-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .earning-amount {
            font-weight: bold;
            color: #28a745;
        }
        
        .partner-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            height: 400px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .top-partners-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .partner-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .partner-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>

    <!-- Mobile Menu Toggle Button -->
    <button id="mobile-menu-toggle" class="d-md-none position-fixed top-0 end-0 btn btn-link text-white p-3" style="z-index: 1100;">
        <i class="bi bi-list fs-4"></i>
    </button>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="content-header">
                        <h1 class="mb-2">My Earnings</h1>
                        <p class="text-muted">Track your earnings and performance metrics</p>
                    </div>
                </div>
            </div>

            <!-- Earnings Summary Cards -->
            <div class="earnings-card">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value">₹<?php echo number_format($earnings_stats['total_earnings'], 2); ?></div>
                            <div class="stat-label">Total Earnings</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $earnings_stats['total_partners']; ?></div>
                            <div class="stat-label">Active Partners</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $earnings_stats['total_transactions']; ?></div>
                            <div class="stat-label">Total Transactions</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value">₹<?php echo number_format($earnings_stats['avg_earning_per_transaction'], 2); ?></div>
                            <div class="stat-label">Avg. per Transaction</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Top Partners Row -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="chart-container">
                        <h5 class="mb-3">Monthly Earnings Trend</h5>
                        <div class="chart-wrapper">
                            <canvas id="earningsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="top-partners-card">
                        <h5 class="mb-3">Top Performing Partners</h5>
                        <?php if (!empty($top_partners)): ?>
                            <?php foreach ($top_partners as $partner): ?>
                                <div class="partner-item">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($partner['partner_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($partner['partner_code']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="earning-amount">₹<?php echo number_format($partner['total_earnings_from_partner'], 2); ?></div>
                                        <small class="text-muted"><?php echo $partner['total_transactions']; ?> transactions</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No partner data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Time Period</label>
                        <select name="period" class="form-select">
                            <option value="all" <?php echo $selected_period === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $selected_period === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $selected_period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="month" <?php echo $selected_period === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="quarter" <?php echo $selected_period === 'quarter' ? 'selected' : ''; ?>>Last 90 Days</option>
                            <option value="year" <?php echo $selected_period === 'year' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by customer, partner name or email..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <a href="earnings.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Earnings History Table -->
            <div class="earnings-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Partner</th>
                                <th>Order Amount</th>
                                <th>Earning %</th>
                                <th>My Earning</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($earnings_history)): ?>
                                <?php foreach ($earnings_history as $earning): ?>
                                    <tr>
                                        <td>
                                            <div><?php echo date('M d, Y', strtotime($earning['created_at'])); ?></div>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($earning['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($earning['customer_name'] ?? 'N/A'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($earning['customer_email'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($earning['partner_name'] ?? 'N/A'); ?></div>
                                            <span class="partner-badge"><?php echo htmlspecialchars($earning['partner_code'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <strong>₹<?php echo number_format($earning['order_amount'] ?? 0, 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $earning['earning_percentage']; ?>%</span>
                                        </td>
                                        <td>
                                            <strong class="earning-amount">₹<?php echo number_format($earning['earning_amount'], 2); ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                        <h5>No Earnings Found</h5>
                                        <p class="text-muted">
                                            <?php if ($selected_period !== 'all' || !empty($search_query)): ?>
                                                No earnings found for the selected criteria.
                                            <?php else: ?>
                                                You haven't earned anything yet. Start referring partners to grow your earnings!
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted">
                            Showing <?php echo min($offset + 1, $total_records); ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                        </div>
                        <nav>
                            <ul class="pagination mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/senior-partner.js"></script>

    <?php if (!empty($monthly_trend)): ?>
    <script>
        // Monthly Earnings Trend Chart
        const ctx = document.getElementById('earningsChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_trend); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Monthly Earnings (₹)',
                    data: monthlyData.map(item => parseFloat(item.monthly_earnings)),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Earnings: ₹' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                elements: {
                    point: {
                        hoverRadius: 8
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>