<?php
session_start();
if (!isset($_SESSION['partner_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../../database/config.php';
$db = new Database();
$conn = $db->getConnection();

$partner_id = $_SESSION['partner_id'];

// Get partner information
$stmt = $conn->prepare("SELECT name, referral_code, partner_id as referred_by_senior_partner FROM partners WHERE id = ?");
$stmt->execute([$partner_id]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle date filter
$selected_period = isset($_GET['period']) ? $_GET['period'] : 'all';
$date_condition = "";
$params = [$partner['referral_code']];

switch ($selected_period) {
    case 'today':
        $date_condition = " AND DATE(created_at) = CURDATE()";
        break;
    case 'week':
        $date_condition = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'year':
        $date_condition = " AND YEAR(created_at) = YEAR(NOW())";
        break;
}

// Get all referrals with order information
$stmt = $conn->prepare("
    SELECT 
        u.*,
        COUNT(o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        COALESCE(SUM(pe.earning_amount), 0) as total_earnings_from_user,
        MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN partner_earnings pe ON u.id = pe.user_id AND pe.partner_id = ?
    WHERE u.referred_by_partner = ?" . $date_condition . "
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute(array_merge([$partner_id], [$partner_id], array_slice($params, 1)));
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get referral statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_referrals,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as monthly_referrals,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as weekly_referrals,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_referrals
    FROM users 
    WHERE referred_by_partner = ?
");
$stmt->execute([$partner_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get referrals with orders (active customers)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT u.id) as active_referrals
    FROM users u
    INNER JOIN orders o ON u.id = o.user_id
    WHERE u.referred_by_partner = ?
");
$stmt->execute([$partner_id]);
$active_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get monthly referral trend for chart
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as referral_count
    FROM users 
    WHERE referred_by_partner = ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute([$partner_id]);
$monthly_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate conversion rate
$conversion_rate = $stats['total_referrals'] > 0 ? 
    ($active_stats['active_referrals'] / $stats['total_referrals']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Referrals - Partner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/partner-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .referrals-header {
            background: linear-gradient(135deg, #6f42c1, #8e44ad);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .referral-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .referral-card:hover {
            transform: translateY(-2px);
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .referral-item {
            border-left: 4px solid #6f42c1;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s ease;
        }
        
        .referral-item:hover {
            background: #e9ecef;
            border-left-color: #8e44ad;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #6f42c1;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 400px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .no-referrals {
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
        
        .referral-code-section {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .copy-code {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .copy-code:hover {
            background: rgba(255, 255, 255, 0.3);
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
            
            .referrals-header {
                padding: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 350px;
            }
            
            .chart-wrapper {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content">
        <button class="btn btn-primary d-md-none mb-3" id="mobile-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Header -->
        <div class="referrals-header">
            <h1><i class="fas fa-users me-3"></i>My Referrals</h1>
            <p class="mb-0">Track and manage your referred customers</p>
        </div>
        
        <!-- Referral Code Section -->
        <div class="referral-code-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5><i class="fas fa-share-alt me-2"></i>Your Referral Code</h5>
                    <p class="mb-0">Share this code with friends and family to earn commissions</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex align-items-center justify-content-md-end">
                        <span class="me-2 fw-bold fs-5"><?php echo htmlspecialchars($partner['referral_code']); ?></span>
                        <button class="copy-code" onclick="copyReferralCode()">
                            <i class="fas fa-copy me-1"></i>Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="referral-card">
                <div class="d-flex align-items-center">
                    <div class="icon-circle me-3">
                        <i class="fas fa-users text-primary"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo $stats['total_referrals']; ?></h3>
                        <p class="text-muted mb-0">Total Referrals</p>
                    </div>
                </div>
            </div>
            
            <div class="referral-card">
                <div class="d-flex align-items-center">
                    <div class="icon-circle me-3">
                        <i class="fas fa-shopping-cart text-success"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo $active_stats['active_referrals']; ?></h3>
                        <p class="text-muted mb-0">Active Customers</p>
                    </div>
                </div>
            </div>
            
            <div class="referral-card">
                <div class="d-flex align-items-center">
                    <div class="icon-circle me-3">
                        <i class="fas fa-percentage text-warning"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($conversion_rate, 1); ?>%</h3>
                        <p class="text-muted mb-0">Conversion Rate</p>
                    </div>
                </div>
            </div>
            
            <div class="referral-card">
                <div class="d-flex align-items-center">
                    <div class="icon-circle me-3">
                        <i class="fas fa-calendar-day text-info"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo $stats['monthly_referrals']; ?></h3>
                        <p class="text-muted mb-0">This Month</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5><i class="fas fa-filter me-2"></i>Filter Referrals</h5>
                </div>
                <div class="col-md-6">
                    <select class="form-select" onchange="filterReferrals(this.value)">
                        <option value="all" <?php echo $selected_period === 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $selected_period === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $selected_period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $selected_period === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="year" <?php echo $selected_period === 'year' ? 'selected' : ''; ?>>This Year</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Monthly Trend Chart -->
        <?php if (!empty($monthly_trend)): ?>
        <div class="chart-container">
            <h5><i class="fas fa-chart-line me-2"></i>Monthly Referral Trend</h5>
            <div class="chart-wrapper">
                <canvas id="referralChart"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Referrals List -->
        <div class="referral-card">
            <h5><i class="fas fa-list me-2"></i>Referral Details</h5>
            
            <?php if (!empty($referrals)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Joined Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referrals as $referral): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($referral['image']): ?>
                                                <img src="../../images/users/<?php echo htmlspecialchars($referral['image']); ?>" 
                                                     alt="User" class="user-avatar me-3">
                                            <?php else: ?>
                                                <div class="user-avatar me-3 d-flex align-items-center justify-content-center bg-primary text-white">
                                                    <?php echo strtoupper(substr($referral['name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($referral['name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($referral['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-phone text-muted me-1"></i>
                                            <?php echo htmlspecialchars($referral['phone']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($referral['district'] . ', ' . $referral['state']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?php echo date('M d, Y', strtotime($referral['created_at'])); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('h:i A', strtotime($referral['created_at'])); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($referral['total_orders'] > 0): ?>
                                            <span class="badge bg-success status-badge">Active Customer</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning status-badge">Pending Order</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3 p-3 bg-light rounded">
                    <div class="row text-center">
                        <div class="col-md-6">
                            <strong>Total Referrals: <?php echo count($referrals); ?></strong>
                        </div>
                        <div class="col-md-6">
                            <strong>Active: <?php echo $active_stats['active_referrals']; ?></strong>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-referrals">
                    <i class="fas fa-user-plus fa-3x mb-3 text-muted"></i>
                    <h5>No Referrals Found</h5>
                    <p>
                        <?php if ($selected_period && $selected_period !== 'all'): ?>
                            No referrals found for the selected period.
                        <?php else: ?>
                            You haven't referred anyone yet. Start sharing your referral code to grow your network!
                        <?php endif; ?>
                    </p>
                    <div class="mt-3">
                        <button class="btn btn-primary me-2" onclick="copyReferralCode()">
                            <i class="fas fa-copy me-2"></i>Copy Referral Code
                        </button>
                        <a href="../../index.php" class="btn btn-outline-primary">
                            <i class="fas fa-home me-2"></i>Visit Website
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($monthly_trend)): ?>
    <script>
        // Monthly Referral Trend Chart
        const ctx = document.getElementById('referralChart').getContext('2d');
        const monthlyData = <?php echo json_encode(array_reverse($monthly_trend)); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Monthly Referrals',
                    data: monthlyData.map(item => parseInt(item.referral_count)),
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
                            stepSize: 1
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
        // Copy referral code function
        function copyReferralCode() {
            const referralCode = '<?php echo $partner['referral_code']; ?>';
            navigator.clipboard.writeText(referralCode).then(function() {
                // Show success message
                const button = document.querySelector('.copy-code');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
                button.style.background = 'rgba(40, 167, 69, 0.3)';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = 'rgba(255, 255, 255, 0.2)';
                }, 2000);
            });
        }
        
        // Filter referrals function
        function filterReferrals(period) {
            window.location.href = '?period=' + period;
        }
        
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