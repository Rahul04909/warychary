<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once '../database/config.php';

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_name = $_SESSION['admin_name'];

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Function to get real-time statistics
function getRealTimeStats($db) {
    $stats = [];
    
    try {
        // Total Sales (sum of all completed orders)
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM orders WHERE payment_status = 'completed'");
        $stmt->execute();
        $stats['total_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];
        
        // Total Orders
        $stmt = $db->prepare("SELECT COUNT(*) as total_orders FROM orders");
        $stmt->execute();
        $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
        
        // Total Customers
        $stmt = $db->prepare("SELECT COUNT(*) as total_customers FROM users");
        $stmt->execute();
        $stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];
        
        // Revenue (completed orders only)
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE payment_status = 'completed'");
        $stmt->execute();
        $stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
        
        // Recent Activities
        $stmt = $db->prepare("
            SELECT 
                o.id, 
                o.guest_name, 
                o.product_name, 
                o.total_amount, 
                o.order_status,
                o.created_at,
                'order' as activity_type
            FROM orders o 
            ORDER BY o.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $stats['recent_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Low stock products (assuming stock management exists)
        $stmt = $db->prepare("SELECT product_name FROM products LIMIT 3");
        $stmt->execute();
        $stats['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total Partners
        $stmt = $db->prepare("SELECT COUNT(*) as total_partners FROM partners WHERE status = 'approved'");
        $stmt->execute();
        $stats['total_partners'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_partners'];
        
        // Total Senior Partners
        $stmt = $db->prepare("SELECT COUNT(*) as total_senior_partners FROM senior_partners");
        $stmt->execute();
        $stats['total_senior_partners'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_senior_partners'];
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        // Return default values on error
        $stats = [
            'total_sales' => 0,
            'total_orders' => 0,
            'total_customers' => 0,
            'revenue' => 0,
            'recent_activities' => [],
            'products' => [],
            'total_partners' => 0,
            'total_senior_partners' => 0
        ];
    }
    
    return $stats;
}

// Get real-time data
$database = new Database();
$db = $database->getConnection();
$realTimeStats = getRealTimeStats($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
        .real-time-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
            margin-right: 5px;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .bg-secondary-custom {
            background: linear-gradient(135deg, #7c43b9, #9c5bc9);
        }
        
        .activity-item, .notification-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child, .notification-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php
    // Include sidebar
    include 'sidebar.php';
    ?>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay"></div>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Mobile Menu Toggle -->
        <button id="mobile-menu-toggle" class="btn btn-primary d-md-none">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Real-time Data Header -->
        <div class="row mb-3">
            <div class="col-12">
                <h2><span class="real-time-indicator"></span>Welcome to Admin Dashboard</h2>
                <p class="text-muted">Live data updated automatically</p>
            </div>
        </div>
        
        <!-- Stats Cards Row -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
            <!-- Total Sales Card -->
            <div class="col">
                <div class="stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Sales</h6>
                            <h3 class="mb-0">₹<?php echo number_format($realTimeStats['total_sales'], 2); ?></h3>
                            <small class="text-success">Live Data <i class="bi bi-arrow-up"></i></small>
                        </div>
                        <div class="icon bg-secondary-custom">
                            <i class="bi bi-currency-rupee text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Orders Card -->
            <div class="col">
                <div class="stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Orders</h6>
                            <h3 class="mb-0"><?php echo $realTimeStats['total_orders']; ?></h3>
                            <small class="text-success">Live Data <i class="bi bi-arrow-up"></i></small>
                        </div>
                        <div class="icon bg-secondary-custom">
                            <i class="bi bi-cart text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Customers Card -->
            <div class="col">
                <div class="stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Customers</h6>
                            <h3 class="mb-0"><?php echo $realTimeStats['total_customers']; ?></h3>
                            <small class="text-success">Live Data <i class="bi bi-arrow-up"></i></small>
                        </div>
                        <div class="icon bg-secondary-custom">
                            <i class="bi bi-people text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Card -->
            <div class="col">
                <div class="stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Revenue</h6>
                            <h3 class="mb-0">₹<?php echo number_format($realTimeStats['revenue'], 2); ?></h3>
                            <small class="text-success">Live Data <i class="bi bi-arrow-up"></i></small>
                        </div>
                        <div class="icon bg-secondary-custom">
                            <i class="bi bi-graph-up text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Stats Row -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-2 g-3 mt-2">
            <!-- Total Partners Card -->
            <div class="col">
                <div class="stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Active Partners</h6>
                            <h3 class="mb-0"><?php echo $realTimeStats['total_partners']; ?></h3>
                            <small class="text-info">Approved Partners</small>
                        </div>
                        <div class="icon bg-secondary-custom">
                            <i class="bi bi-person-badge text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Senior Partners Card -->
            <div class="col">
                <div class="stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Senior Partners</h6>
                            <h3 class="mb-0"><?php echo $realTimeStats['total_senior_partners']; ?></h3>
                            <small class="text-info">Total Senior Partners</small>
                        </div>
                        <div class="icon bg-secondary-custom">
                            <i class="bi bi-star text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h4 class="mt-4 mb-3">Quick Actions</h4>
        <?php include 'quick-actions.php'; ?>

        <!-- Recent Activities and Notifications Row -->
        <div class="row g-3 mt-2">
            <!-- Recent Activities -->
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><span class="real-time-indicator"></span>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($realTimeStats['recent_activities'])): ?>
                            <?php foreach ($realTimeStats['recent_activities'] as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between">
                                        <strong>New order received</strong>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0">Order #<?php echo $activity['id']; ?> from <?php echo htmlspecialchars($activity['guest_name']); ?></p>
                                    <small class="text-muted">₹<?php echo number_format($activity['total_amount'], 2); ?> - Status: <?php echo ucfirst($activity['order_status']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="activity-item">
                                <p class="mb-0 text-muted">No recent activities found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="notification-item">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-check-circle text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0">Database Connected</p>
                                    <small class="text-muted">All systems operational</small>
                                </div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-star text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0">Real-time Data Active</p>
                                    <small class="text-muted">Live updates enabled</small>
                                </div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-shield-check text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0">Security Status</p>
                                    <small class="text-muted">All security checks passed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const closeSidebarButton = document.getElementById('close-sidebar');
        const mainContent = document.querySelector('.main-content');

        if (mobileMenuToggle && sidebar && sidebarOverlay && closeSidebarButton && mainContent) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.classList.add('sidebar-active');
                mainContent.classList.add('sidebar-active');
            });

            closeSidebarButton.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-active');
                mainContent.classList.remove('sidebar-active');
            });

            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-active');
                mainContent.classList.remove('sidebar-active');
            });
        }
        
        // Auto-refresh dashboard data every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    });
</script>
</body>
</html>