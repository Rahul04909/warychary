<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['senior_partner_id'])) {
    header("Location: index.php");
    exit();
}

// Get user data from session
$senior_partner_id = $_SESSION['senior_partner_id'];
$senior_partner_name = $_SESSION['senior_partner_name'];
$senior_partner_email = $_SESSION['senior_partner_email'];

// Database connection
require_once '../database/config.php';
$database = new Database();
$db = $database->getConnection();

// Get real-time statistics
try {
    // Total earnings for this senior partner
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(earning_amount), 0) as total_earnings,
            COUNT(*) as total_transactions
        FROM senior_partner_earnings 
        WHERE senior_partner_id = ?
    ");
    $stmt->execute([$senior_partner_id]);
    $earnings_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Active referrals (partners referred by this senior partner)
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_partners,
            COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_this_month,
            COUNT(CASE WHEN pe.earning_amount > 0 THEN 1 END) as active_partners
        FROM partners p
        LEFT JOIN partner_earnings pe ON pe.partner_id = p.id
        WHERE p.referred_by_senior_partner = ?
    ");
    $stmt->execute([$senior_partner_id]);
    $partner_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Pending referrals (partners with pending status)
    $stmt = $db->prepare("
        SELECT COUNT(*) as pending_partners
        FROM partners 
        WHERE referred_by_senior_partner = ? AND status = 'pending'
    ");
    $stmt->execute([$senior_partner_id]);
    $pending_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get total referrals (partners) referred by this senior partner - using same logic as referrals.php
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) as total
        FROM partners p
        WHERE p.referral_code IN (
            SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
            UNION
            SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
            UNION
            SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
        )
    ");
    $stmt->execute([$senior_partner_id, $senior_partner_id, $senior_partner_id]);
    $total_referrals = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get active referrals (partners with earnings)
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) as total 
        FROM partners p 
        INNER JOIN partner_earnings pe ON p.id = pe.partner_id 
        WHERE p.referral_code IN (
            SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
            UNION
            SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
            UNION
            SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
        ) AND pe.earning_amount > 0
    ");
    $stmt->execute([$senior_partner_id, $senior_partner_id, $senior_partner_id]);
    $active_referrals = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get pending referrals
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) as total 
        FROM partners p 
        WHERE p.referral_code IN (
            SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
            UNION
            SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
            UNION
            SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
        ) AND p.id NOT IN (SELECT DISTINCT partner_id FROM partner_earnings WHERE earning_amount > 0)
    ");
    $stmt->execute([$senior_partner_id, $senior_partner_id, $senior_partner_id]);
    $pending_referrals = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Conversion rate calculation (active referrals vs total referrals)
    $conversion_rate = $total_referrals > 0 ? 
        ($active_referrals / $total_referrals) * 100 : 0;

    // Monthly earnings trend for chart
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%M') as month_name,
            SUM(earning_amount) as monthly_earnings
        FROM senior_partner_earnings 
        WHERE senior_partner_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$senior_partner_id]);
    $monthly_earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent activity
    $stmt = $db->prepare("
        SELECT 
            spe.created_at,
            spe.earning_amount,
            p.name as partner_name,
            'Commission Earned' as activity_type
        FROM senior_partner_earnings spe
        LEFT JOIN partners p ON spe.partner_id = p.id
        WHERE spe.senior_partner_id = ?
        ORDER BY spe.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$senior_partner_id]);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Referral sources data
    $stmt = $db->prepare("
        SELECT 
            CASE 
                WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Recent'
                WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'This Month'
                WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 'Last 3 Months'
                ELSE 'Older'
            END as source_period,
            COUNT(*) as count
        FROM partners p
        WHERE p.referral_code IN (
            SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
            UNION
            SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
            UNION
            SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
        )
        GROUP BY source_period
    ");
    $stmt->execute([$senior_partner_id, $senior_partner_id, $senior_partner_id]);
    $referral_sources = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Default values in case of error
    $earnings_stats = ['total_earnings' => 0, 'total_transactions' => 0];
    $partner_stats = ['total_partners' => 0, 'new_this_month' => 0, 'active_partners' => 0];
    $conversion_rate = 0;
    $pending_stats = ['pending_partners' => 0];
    $total_referrals = 0;
    $pending_referrals = 0;
    $active_referrals = 0;
    $monthly_earnings = [];
    $recent_activities = [];
    $referral_sources = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior Partner Dashboard - WaryChary</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="assets/css/senior-partner-style.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Mobile Menu Toggle Button -->
    <button id="mobile-menu-toggle" class="d-md-none position-fixed top-0 end-0 btn btn-link text-white p-3" style="z-index: 1100;">
        <i class="bi bi-list fs-4"></i>
    </button>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="content-header">
                        <h1 class="mb-4">Welcome Back, <?php echo htmlspecialchars($senior_partner_name); ?>!</h1>
                        <p class="text-muted">Here's your partnership performance overview</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="icon-circle">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <h3>₹<?php echo number_format($earnings_stats['total_earnings'], 2); ?></h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="icon-circle">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3><?php echo number_format($total_referrals); ?></h3>
                         <p>Total Referrals</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="icon-circle">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h3><?php echo number_format($conversion_rate, 1); ?>%</h3>
                        <p>Conversion Rate</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="icon-circle">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h3><?php echo number_format($pending_referrals); ?></h3>
                         <p>Pending Referrals</p>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Earnings Overview</h5>
                            <canvas id="earningsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Referral Sources</h5>
                            <canvas id="referralSourcesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Recent Activity</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Activity</th>
                                            <th>Partner</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_activities)): ?>
                                            <?php foreach ($recent_activities as $activity): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d', strtotime($activity['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['partner_name'] ?? 'N/A'); ?></td>
                                                    <td>₹<?php echo number_format($activity['earning_amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No recent activity found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Charts -->
    <script>
        // Earnings Chart with real data
        const earningsCtx = document.getElementById('earningsChart').getContext('2d');
        
        // Prepare chart data from PHP
        const monthlyData = <?php echo json_encode($monthly_earnings); ?>;
        const chartLabels = monthlyData.map(item => item.month_name || 'N/A');
        const chartData = monthlyData.map(item => parseFloat(item.monthly_earnings) || 0);
        
        // If no data, show placeholder
        const finalLabels = chartLabels.length > 0 ? chartLabels : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const finalData = chartData.length > 0 ? chartData : [0, 0, 0, 0, 0, 0];
        
        new Chart(earningsCtx, {
            type: 'line',
            data: {
                labels: finalLabels,
                datasets: [{
                    label: 'Monthly Earnings (₹)',
                    data: finalData,
                    borderColor: '#3498db',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(52, 152, 219, 0.1)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
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
                }
            }
        });

        // Referral Sources Chart with real data
        const referralCtx = document.getElementById('referralSourcesChart').getContext('2d');
        
        // Prepare referral sources data from PHP
        const referralData = <?php echo json_encode($referral_sources); ?>;
        const sourceLabels = referralData.map(item => item.source_period);
        const sourceCounts = referralData.map(item => parseInt(item.count));
        
        // Default data if no referrals
        const defaultLabels = ['Recent', 'This Month', 'Last 3 Months', 'Older'];
        const defaultCounts = [0, 0, 0, 0];
        
        const finalSourceLabels = sourceLabels.length > 0 ? sourceLabels : defaultLabels;
        const finalSourceCounts = sourceCounts.length > 0 ? sourceCounts : defaultCounts;
        
        new Chart(referralCtx, {
            type: 'doughnut',
            data: {
                labels: finalSourceLabels,
                datasets: [{
                    data: finalSourceCounts,
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#f1c40f',
                        '#e74c3c'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const closeSidebarBtn = document.getElementById('close-sidebar');
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mainContent = document.querySelector('.main-content');

            // Mobile menu toggle
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                    mainContent.classList.toggle('sidebar-active');
                });
            }

            // Close button inside sidebar
            if (closeSidebarBtn) {
                closeSidebarBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('sidebar-active');
                });
            }

            // Close sidebar when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 768) {
                    const isClickInsideSidebar = sidebar.contains(e.target);
                    const isClickOnToggleBtn = mobileMenuToggle.contains(e.target);
                    
                    if (!isClickInsideSidebar && !isClickOnToggleBtn) {
                        sidebar.classList.remove('active');
                        mainContent.classList.remove('sidebar-active');
                    }
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('sidebar-active');
                }
            });
        });
    </script>
</body>
</html>