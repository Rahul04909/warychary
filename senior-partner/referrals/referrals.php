<?php
session_start();
if (!isset($_SESSION['senior_partner_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../../database/config.php';
$db = new Database();
$conn = $db->getConnection();

$senior_partner_id = $_SESSION['senior_partner_id'];

// Get senior partner information
$stmt = $conn->prepare("SELECT * FROM senior_partners WHERE id = ?");
$stmt->execute([$senior_partner_id]);
$senior_partner = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle date filter
$selected_period = isset($_GET['period']) ? $_GET['period'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$date_condition = "";
$search_condition = "";
$status_condition = "";
$params = [$senior_partner_id];

// Date filtering
switch ($selected_period) {
    case 'today':
        $date_condition = " AND DATE(p.created_at) = CURDATE()";
        break;
    case 'week':
        $date_condition = " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'year':
        $date_condition = " AND YEAR(p.created_at) = YEAR(NOW())";
        break;
}

// Search filtering
if (!empty($search_query)) {
    $search_condition = " AND (p.name LIKE ? OR p.email LIKE ? OR p.phone LIKE ? OR p.state LIKE ? OR p.district LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

// Status filtering (based on earning activity)
if ($status_filter === 'active') {
    $status_condition = " AND EXISTS (SELECT 1 FROM partner_earnings pe WHERE pe.partner_id = p.id AND pe.earning_amount > 0)";
} elseif ($status_filter === 'inactive') {
    $status_condition = " AND NOT EXISTS (SELECT 1 FROM partner_earnings pe WHERE pe.partner_id = p.id AND pe.earning_amount > 0)";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total
    FROM partners p
    WHERE p.referral_code IN (
        SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
    )
    $date_condition
    $search_condition
    $status_condition
";

$count_params = [$senior_partner_id, $senior_partner_id, $senior_partner_id];
if (!empty($search_query)) {
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($count_params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get referrals with detailed information
$query = "
    SELECT 
        p.*,
        COUNT(DISTINCT u.id) as total_users_referred,
        COALESCE(SUM(pe.earning_amount), 0) as total_earnings,
        COUNT(DISTINCT pe.id) as total_transactions,
        MAX(pe.created_at) as last_earning_date
    FROM partners p
    LEFT JOIN users u ON u.referral_code = p.referral_code
    LEFT JOIN partner_earnings pe ON pe.partner_id = p.id
    WHERE p.referral_code IN (
        SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
    )
    $date_condition
    $search_condition
    $status_condition
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT $records_per_page OFFSET $offset
";

$query_params = [$senior_partner_id, $senior_partner_id, $senior_partner_id];
if (!empty($search_query)) {
    $query_params = array_merge($query_params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

$stmt = $conn->prepare($query);
$stmt->execute($query_params);
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT p.id) as total_partners,
        COALESCE(SUM(pe.earning_amount), 0) as total_partner_earnings,
        COUNT(DISTINCT CASE WHEN pe.earning_amount > 0 THEN p.id END) as active_partners,
        COUNT(DISTINCT CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.id END) as new_this_month
    FROM partners p
    LEFT JOIN partner_earnings pe ON pe.partner_id = p.id
    WHERE p.referral_code IN (
        SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
    )
";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([$senior_partner_id, $senior_partner_id, $senior_partner_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Referrals - Senior Partner Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/senior-partner-style.css">
    
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stats-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stats-card.warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stats-card.info {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .referral-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-left: 10px;
            margin-right: 10px;
        }
        
        .referral-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .partner-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .earning-amount {
            font-size: 1.25rem;
            font-weight: 600;
            color: #28a745;
        }
        
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .container-fluid {
            padding: 0 15px;
        }
        
        .main-content {
            padding: 20px;
            margin-left: var(--sidebar-width);
            margin-right: var(--sidebar-width);
        }
        
        .no-referrals {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .no-referrals i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <?php include '../sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">My Referrals</h1>
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-3">Welcome, <?php echo htmlspecialchars($senior_partner['name']); ?></span>
                        <img src="<?php echo $senior_partner['image'] ? '../uploads/' . $senior_partner['image'] : '../assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="rounded-circle" width="40" height="40">
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people-fill fs-2 me-3"></i>
                                <div>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_partners'] ?? 0); ?></h3>
                                    <p class="mb-0">Total Partners</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card success">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-check-fill fs-2 me-3"></i>
                                <div>
                                    <h3 class="mb-0"><?php echo number_format($stats['active_partners'] ?? 0); ?></h3>
                                    <p class="mb-0">Active Partners</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card warning">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-currency-rupee fs-2 me-3"></i>
                                <div>
                                    <h3 class="mb-0">₹<?php echo number_format($stats['total_partner_earnings'] ?? 0, 2); ?></h3>
                                    <p class="mb-0">Total Earnings</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card info">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-plus-fill fs-2 me-3"></i>
                                <div>
                                    <h3 class="mb-0"><?php echo number_format($stats['new_this_month'] ?? 0); ?></h3>
                                    <p class="mb-0">New This Month</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="period" class="form-label">Time Period</label>
                            <select class="form-select" id="period" name="period">
                                <option value="all" <?php echo $selected_period === 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $selected_period === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $selected_period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $selected_period === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="year" <?php echo $selected_period === 'year' ? 'selected' : ''; ?>>This Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Partners</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Partners</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Partners</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by name, email, phone, location..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Referrals List -->
                <?php if (empty($referrals)): ?>
                    <div class="no-referrals">
                        <i class="bi bi-people"></i>
                        <h4>No Referrals Found</h4>
                        <p>You haven't referred any partners yet, or no partners match your current filters.</p>
                        <a href="../marketing-tools.php" class="btn btn-primary">
                            <i class="bi bi-megaphone"></i> Get Marketing Tools
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($referrals as $partner): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="referral-card">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo $partner['image'] ? '../../uploads/partners/' . $partner['image'] : '../../assets/images/default-avatar.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($partner['name']); ?>" 
                                             class="partner-avatar me-3">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($partner['name']); ?></h5>
                                            <small class="text-muted">ID: <?php echo htmlspecialchars($partner['partner_id']); ?></small>
                                        </div>
                                        <span class="status-badge <?php echo $partner['total_earnings'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $partner['total_earnings'] > 0 ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="earning-amount">₹<?php echo number_format($partner['total_earnings'] ?? 0, 2); ?></div>
                            <small class="text-muted">Total Earning</small>
                        </div>
                                        <div class="col-4">
                                            <div class="fw-bold"><?php echo $partner['total_users_referred'] ?? 0; ?></div>
                                            <small class="text-muted">Users Referred</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="fw-bold"><?php echo $partner['total_transactions'] ?? 0; ?></div>
                                            <small class="text-muted">Transactions</small>
                                        </div>
                                    </div>
                                    
                                    <div class="border-top pt-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Email:</small><br>
                                                <small style="word-break: break-all; word-wrap: break-word;"><?php echo htmlspecialchars($partner['email']); ?></small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Phone:</small><br>
                                                <small><?php echo htmlspecialchars($partner['phone']); ?></small>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <small class="text-muted">Location:</small><br>
                                                <small><?php echo htmlspecialchars($partner['district'] . ', ' . $partner['state']); ?></small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Joined:</small><br>
                                                <small><?php echo date('M d, Y', strtotime($partner['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        <?php if ($partner['last_earning_date']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Last Activity:</small><br>
                                                <small><?php echo date('M d, Y', strtotime($partner['last_earning_date'])); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <nav aria-label="Referrals pagination">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="bi bi-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                Next <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center text-muted mt-3">
                        Showing <?php echo (($page - 1) * $records_per_page) + 1; ?> to 
                        <?php echo min($page * $records_per_page, $total_records); ?> of 
                        <?php echo $total_records; ?> referrals
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/senior-partner.js"></script>
    
    <script>
        // Auto-submit form on filter change
        document.getElementById('period').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('status').addEventListener('change', function() {
            this.form.submit();
        });
        
        // Search with debounce
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>