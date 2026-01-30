<?php
session_start();
if (!isset($_SESSION['partner_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../database/config.php';
$db = new Database();
$conn = $db->getConnection();

$partner_id = $_SESSION['partner_id'];

// Get partner information
$stmt = $conn->prepare("SELECT name, referral_code FROM partners WHERE id = ?");
$stmt->execute([$partner_id]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle month filter
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_condition = "";
$params = [$partner_id];

if ($selected_month && $selected_month !== 'all') {
    $month_condition = " AND DATE_FORMAT(pe.created_at, '%Y-%m') = ?";
    $params[] = $selected_month;
}

// Get earnings data with order details
$stmt = $conn->prepare("
    SELECT 
        pe.*,
        o.product_name,
        o.quantity,
        o.guest_name,
        o.guest_email,
        o.guest_phone,
        u.name as user_name,
        u.email as user_email
    FROM partner_earnings pe
    LEFT JOIN orders o ON pe.order_id = o.id
    LEFT JOIN users u ON pe.user_id = u.id
    WHERE pe.partner_id = ?" . $month_condition . "
    ORDER BY pe.created_at DESC
");
$stmt->execute($params);
$earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total earnings for selected period
$total_earnings = array_sum(array_column($earnings, 'earning_amount'));

// Get monthly earnings summary for chart
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(earning_amount) as total_amount,
        COUNT(*) as total_orders
    FROM partner_earnings 
    WHERE partner_id = ? 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute([$partner_id]);
$monthly_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available months for filter dropdown
$stmt = $conn->prepare("
    SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as month
    FROM partner_earnings 
    WHERE partner_id = ?
    ORDER BY month DESC
");
$stmt->execute([$partner_id]);
$available_months = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overall statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(earning_amount) as lifetime_earnings,
        AVG(earning_amount) as avg_earning_per_order
    FROM partner_earnings 
    WHERE partner_id = ?
");
$stmt->execute([$partner_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings History - Partner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/partner-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .earnings-header {
            background: linear-gradient(135deg, #6f42c1, #8e44ad);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .earnings-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .earnings-card:hover {
            transform: translateY(-2px);
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .earning-item {
            border-left: 4px solid #6f42c1;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s ease;
        }
        
        .earning-item:hover {
            background: #e9ecef;
            border-left-color: #8e44ad;
        }
        
        .earning-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .earning-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 400px; /* Fixed height for chart container */
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px; /* Fixed height for chart wrapper */
            width: 100%;
        }
        
        .no-earnings {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .earnings-header {
                padding: 1.5rem;
            }
            
            .chart-container {
                height: 350px; /* Reduced height for mobile */
            }
            
            .chart-wrapper {
                height: 250px; /* Reduced height for mobile */
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: #6f42c1;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
        }
        
        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" id="mobile-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="earnings-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-wallet me-2"></i>Earnings History</h2>
                    <p class="mb-0">Track your commission earnings and performance</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <h3>₹<?php echo number_format($total_earnings, 2); ?></h3>
                    <small>
                        <?php if ($selected_month === 'all' || !$selected_month): ?>
                            Total Earnings
                        <?php else: ?>
                            Earnings for <?php echo date('F Y', strtotime($selected_month . '-01')); ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-circle">
                    <i class="fas fa-coins"></i>
                </div>
                <h3>₹<?php echo number_format($stats['lifetime_earnings'] ?? 0, 2); ?></h3>
                <p>Lifetime Earnings</p>
            </div>
            
            <div class="stat-card">
                <div class="icon-circle">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3><?php echo $stats['total_orders'] ?? 0; ?></h3>
                <p>Total Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="icon-circle">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>₹<?php echo number_format($stats['avg_earning_per_order'] ?? 0, 2); ?></h3>
                <p>Avg. per Order</p>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5><i class="fas fa-filter me-2"></i>Filter Earnings</h5>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="d-flex gap-2">
                        <select name="month" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?php echo ($selected_month === 'all' || !$selected_month) ? 'selected' : ''; ?>>All Time</option>
                            <?php foreach ($available_months as $month): ?>
                                <option value="<?php echo $month['month']; ?>" <?php echo ($selected_month === $month['month']) ? 'selected' : ''; ?>>
                                    <?php echo date('F Y', strtotime($month['month'] . '-01')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='earnings.php'">
                            <i class="fas fa-refresh"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Monthly Earnings Chart -->
        <?php if (!empty($monthly_summary)): ?>
        <div class="chart-container">
            <h5><i class="fas fa-chart-bar me-2"></i>Monthly Earnings Trend</h5>
            <div class="chart-wrapper">
                <canvas id="earningsChart"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Earnings List -->
        <div class="earnings-card">
            <h5><i class="fas fa-list me-2"></i>Earnings Details</h5>
            
            <?php if (!empty($earnings)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Order Details</th>
                                <th>Customer</th>
                                <th>Order Amount</th>
                                <th>Commission %</th>
                                <th>Earning Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($earnings as $earning): ?>
                                <tr>
                                    <td>
                                        <div class="earning-date">
                                            <?php echo date('M d, Y', strtotime($earning['created_at'])); ?>
                                            <br>
                                            <small><?php echo date('h:i A', strtotime($earning['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($earning['product_name'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small>Qty: <?php echo $earning['quantity'] ?? 'N/A'; ?></small>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if ($earning['user_name']): ?>
                                                <strong><?php echo htmlspecialchars($earning['user_name']); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($earning['user_email']); ?></small>
                                            <?php else: ?>
                                                <strong><?php echo htmlspecialchars($earning['guest_name'] ?? 'Guest'); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($earning['guest_email'] ?? 'N/A'); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>₹<?php echo number_format($earning['order_amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $earning['earning_percentage']; ?>%</span>
                                    </td>
                                    <td>
                                        <div class="earning-amount">₹<?php echo number_format($earning['earning_amount'], 2); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3 p-3 bg-light rounded">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <strong>Total Orders: <?php echo count($earnings); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <strong>Period Total: ₹<?php echo number_format($total_earnings, 2); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <strong>Avg. per Order: ₹<?php echo count($earnings) > 0 ? number_format($total_earnings / count($earnings), 2) : '0.00'; ?></strong>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-earnings">
                    <i class="fas fa-wallet fa-3x mb-3 text-muted"></i>
                    <h5>No Earnings Found</h5>
                    <p>
                        <?php if ($selected_month && $selected_month !== 'all'): ?>
                            No earnings recorded for <?php echo date('F Y', strtotime($selected_month . '-01')); ?>.
                        <?php else: ?>
                            You haven't earned any commissions yet. Start referring customers to earn!
                        <?php endif; ?>
                    </p>
                    <a href="../index.php" class="btn btn-primary">
                        <i class="fas fa-share-alt me-2"></i>Start Referring
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($monthly_summary)): ?>
    <script>
        // Monthly Earnings Chart
        const ctx = document.getElementById('earningsChart').getContext('2d');
        const monthlyData = <?php echo json_encode(array_reverse($monthly_summary)); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Monthly Earnings (₹)',
                    data: monthlyData.map(item => parseFloat(item.total_amount)),
                    borderColor: '#6f42c1',
                    backgroundColor: 'rgba(111, 66, 193, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 6,
                        hoverRadius: 8
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    </script>
    <?php endif; ?>
    
    <script>
        // Mobile sidebar toggle
        document.getElementById('mobile-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobile-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                document.querySelector('.main-content').classList.remove('sidebar-active');
            }
        });
    </script>
</body>
</html>