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

// Handle AJAX requests for approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $partner_id = $_POST['partner_id'] ?? null;
    $action = $_POST['action'];
    
    if (!$partner_id) {
        echo json_encode(['success' => false, 'message' => 'Partner ID is required']);
        exit();
    }
    
    try {
        if ($action === 'approve') {
            // Update partner status to approved (we'll add status field logic)
            $stmt = $conn->prepare("UPDATE partners SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$partner_id]);
            
            echo json_encode(['success' => true, 'message' => 'Partner approved successfully']);
        } elseif ($action === 'reject') {
            // Update partner status to rejected
            $stmt = $conn->prepare("UPDATE partners SET status = 'rejected', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$partner_id]);
            
            echo json_encode(['success' => true, 'message' => 'Partner rejected successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle search and filtering
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_period = isset($_GET['period']) ? $_GET['period'] : 'all';

// Pagination
$records_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Build query conditions
$date_condition = "";
$search_condition = "";
$params = [$senior_partner_id, $senior_partner_id, $senior_partner_id];

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

// Get total count for pagination
$count_query = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM partners p
    WHERE p.referral_code IN (
        SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
    )
    AND (p.status IS NULL OR p.status = 'pending')
    $date_condition
    $search_condition
";

$count_params = [$senior_partner_id, $senior_partner_id, $senior_partner_id];
if (!empty($search_query)) {
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($count_params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get pending partners
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
    AND (p.status IS NULL OR p.status = 'pending')
    $date_condition
    $search_condition
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
$pending_partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics for pending partners
$stats_query = "
    SELECT 
        COUNT(*) as total_pending,
        COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_this_month,
        COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_this_week
    FROM partners p
    WHERE p.referral_code IN (
        SELECT sp.partner_id FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.email FROM senior_partners sp WHERE sp.id = ?
        UNION
        SELECT sp.phone FROM senior_partners sp WHERE sp.id = ?
    )
    AND (p.status IS NULL OR p.status = 'pending')
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
    <title>Pending Partners - Senior Partner Dashboard</title>
    
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
        
        .partner-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-left: 10px;
            margin-right: 10px;
            border-left: 4px solid #ffc107;
        }
        
        .partner-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .partner-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .earning-amount {
            font-size: 1.25rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            border: none;
            color: white;
        }
        
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .no-partners {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .no-partners i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .container-fluid {
            padding: 0 15px;
        }
        
        .main-content {
            padding: 20px;
            margin-left: var(--sidebar-width);
            margin-right: 15px;
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
                    <h1 class="h3 mb-0">Pending Partners</h1>
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-3">Welcome, <?php echo htmlspecialchars($senior_partner['name']); ?></span>
                        <img src="<?php echo $senior_partner['image'] ? '../uploads/' . $senior_partner['image'] : '../assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="rounded-circle" width="40" height="40">
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_pending'] ?? 0); ?></h3>
                                    <p class="mb-0">Total Pending</p>
                                </div>
                                <i class="bi bi-clock-history fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo number_format($stats['new_this_month'] ?? 0); ?></h3>
                                    <p class="mb-0">New This Month</p>
                                </div>
                                <i class="bi bi-calendar-month fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo number_format($stats['new_this_week'] ?? 0); ?></h3>
                                    <p class="mb-0">New This Week</p>
                                </div>
                                <i class="bi bi-calendar-week fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Time Period</label>
                            <select name="period" class="form-select">
                                <option value="all" <?php echo $selected_period === 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $selected_period === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $selected_period === 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $selected_period === 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="year" <?php echo $selected_period === 'year' ? 'selected' : ''; ?>>This Year</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Search Partners</label>
                            <input type="text" class="form-control" name="search" placeholder="Search by name, email, phone, location..." 
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

                <!-- Pending Partners List -->
                <?php if (empty($pending_partners)): ?>
                    <div class="no-partners">
                        <i class="bi bi-people"></i>
                        <h4>No Pending Partners Found</h4>
                        <p>There are no partners waiting for approval, or no partners match your current filters.</p>
                        <a href="referrals.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to All Referrals
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($pending_partners as $partner): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="partner-card" data-partner-id="<?php echo $partner['id']; ?>">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo $partner['image'] ? '../../uploads/partners/' . $partner['image'] : '../../assets/images/default-avatar.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($partner['name']); ?>" 
                                             class="partner-avatar me-3">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($partner['name']); ?></h5>
                                            <small class="text-muted">ID: <?php echo htmlspecialchars($partner['partner_id']); ?></small>
                                        </div>
                                        <span class="status-badge status-pending">
                                            Pending
                                        </span>
                                    </div>
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <div class="earning-amount">₹<?php echo number_format($partner['total_earnings'] ?? 0, 2); ?></div>
                                            <small class="text-muted">Potential Earning</small>
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
                                    
                                    <div class="border-top pt-3 mb-3">
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
                                                <small class="text-muted">Applied:</small><br>
                                                <small><?php echo date('M d, Y', strtotime($partner['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-approve flex-fill" onclick="approvePartner(<?php echo $partner['id']; ?>)">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                        <button type="button" class="btn btn-reject flex-fill" onclick="rejectPartner(<?php echo $partner['id']; ?>)">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <nav aria-label="Partners pagination">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&period=<?php echo $selected_period; ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&period=<?php echo $selected_period; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&period=<?php echo $selected_period; ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function approvePartner(partnerId) {
            if (confirm('Are you sure you want to approve this partner?')) {
                $.ajax({
                    url: 'pending.php',
                    method: 'POST',
                    data: {
                        action: 'approve',
                        partner_id: partnerId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the partner card from the page
                            $('[data-partner-id="' + partnerId + '"]').closest('.col-md-6').fadeOut(300, function() {
                                $(this).remove();
                                // Check if no more partners
                                if ($('.partner-card').length === 0) {
                                    location.reload();
                                }
                            });
                            
                            // Show success message
                            showAlert('success', response.message);
                        } else {
                            showAlert('danger', response.message);
                        }
                    },
                    error: function() {
                        showAlert('danger', 'An error occurred while processing the request.');
                    }
                });
            }
        }
        
        function rejectPartner(partnerId) {
            if (confirm('Are you sure you want to reject this partner? This action cannot be undone.')) {
                $.ajax({
                    url: 'pending.php',
                    method: 'POST',
                    data: {
                        action: 'reject',
                        partner_id: partnerId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the partner card from the page
                            $('[data-partner-id="' + partnerId + '"]').closest('.col-md-6').fadeOut(300, function() {
                                $(this).remove();
                                // Check if no more partners
                                if ($('.partner-card').length === 0) {
                                    location.reload();
                                }
                            });
                            
                            // Show success message
                            showAlert('warning', response.message);
                        } else {
                            showAlert('danger', response.message);
                        }
                    },
                    error: function() {
                        showAlert('danger', 'An error occurred while processing the request.');
                    }
                });
            }
        }
        
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Insert alert at the top of main content
            $('.main-content').prepend(alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
</body>
</html>