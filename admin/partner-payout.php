<?php
session_start();
require_once '../database/config.php';

$database = new Database();
$db = $database->getConnection();

// Initialize variables
$partners = [];
$total_partners = 0;
$total_payable_amount = 0;
$selected_month = '';
$selected_year = '';
$search_query = '';
$per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;
$success_message = '';
$error_message = '';

// Handle payout processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payout'])) {
    $selected_partners = $_POST['selected_partners'] ?? [];
    $payout_month = $_POST['payout_month'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'bank_transfer';
    $transaction_reference = $_POST['transaction_reference'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (!empty($selected_partners) && !empty($payout_month)) {
        try {
            $db->beginTransaction();
            
            $processed_count = 0;
            $total_payout_amount = 0;
            
            foreach ($selected_partners as $partner_id) {
                // Get partner current earnings
                $partner_stmt = $db->prepare("SELECT total_earnings, name FROM partners WHERE id = ?");
                $partner_stmt->execute([$partner_id]);
                $partner = $partner_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($partner && $partner['total_earnings'] > 0) {
                    $payout_amount = $partner['total_earnings'];
                    
                    // Insert payout history
                    $payout_stmt = $db->prepare("
                        INSERT INTO partner_payout_history 
                        (partner_id, payout_amount, payout_date, payout_month, earnings_before_payout, 
                         earnings_after_payout, payment_method, transaction_reference, notes, processed_by, status) 
                        VALUES (?, ?, CURDATE(), ?, ?, 0.00, ?, ?, ?, ?, 'completed')
                    ");
                    
                    $payout_stmt->execute([
                        $partner_id,
                        $payout_amount,
                        $payout_month,
                        $payout_amount,
                        $payment_method,
                        $transaction_reference,
                        $notes,
                        $_SESSION['admin_name'] ?? 'Admin'
                    ]);
                    
                    // Reset partner total_earnings to 0
                    $update_stmt = $db->prepare("UPDATE partners SET total_earnings = 0.00 WHERE id = ?");
                    $update_stmt->execute([$partner_id]);
                    
                    $processed_count++;
                    $total_payout_amount += $payout_amount;
                }
            }
            
            $db->commit();
            $success_message = "Successfully processed payout for {$processed_count} partners. Total amount: ₹" . number_format($total_payout_amount, 2);
            
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "Error processing payout: " . $e->getMessage();
        }
    } else {
        $error_message = "Please select partners and specify payout month.";
    }
}

// Handle form submission for filtering
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $selected_month = $_GET['month'] ?? '';
    $selected_year = $_GET['year'] ?? '';
    $search_query = $_GET['search'] ?? '';
}

// Build query conditions - only show partners with earnings > 0
$conditions = ["p.total_earnings > 0"];
$params = [];

if (!empty($search_query)) {
    $conditions[] = "(p.name LIKE :search OR p.email LIKE :search OR p.partner_id LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$where_clause = 'WHERE ' . implode(' AND ', $conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM partners p $where_clause";
$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_partners = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_partners / $per_page);

// Get partners data with bank details (only those with earnings > 0)
$sql = "SELECT p.*, 
               pbd.bank_name, 
               pbd.account_number, 
               pbd.ifsc_code, 
               pbd.account_holder_name,
               sp.name as senior_partner_name
        FROM partners p 
        LEFT JOIN partner_bank_details pbd ON p.id = pbd.partner_id 
        LEFT JOIN senior_partners sp ON p.referred_by_senior_partner = sp.id
        $where_clause 
        ORDER BY p.total_earnings DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total payable amount
$payable_sql = "SELECT SUM(p.total_earnings) as total_payable FROM partners p $where_clause";
$payable_stmt = $db->prepare($payable_sql);
foreach ($params as $key => $value) {
    $payable_stmt->bindValue($key, $value);
}
$payable_stmt->execute();
$total_payable_amount = $payable_stmt->fetch(PDO::FETCH_ASSOC)['total_payable'] ?? 0;

// Get current month and year for default payout month
$current_month = date('Y-m');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Payout Management - Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
        .payout-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .payout-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #28a745;
            color: white;
            border: none;
            font-weight: 600;
        }
        .table td {
            vertical-align: middle;
            border-color: #e9ecef;
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .payout-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
        }
        .process-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .process-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .process-btn:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        
        .earnings-amount {
            font-weight: 600;
            color: #28a745;
            font-size: 1.1rem;
        }
        
        .bank-details {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .select-all-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            .stat-card {
                margin-bottom: 1rem;
            }
            .filter-section, .payout-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-toggle" class="btn btn-primary d-md-none mb-3">
                <i class="bi bi-list"></i>
            </button>

            <!-- Payout Header -->
            <div class="payout-header text-center">
                <h1 class="mb-2"><i class="bi bi-cash-coin"></i> Partner Payout Management</h1>
                <p class="mb-0">Process monthly payouts for partners with pending earnings</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Partners with Earnings</h6>
                                <h2 class="mb-0 text-primary fw-bold"><?php echo number_format($total_partners); ?></h2>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-people-fill" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Payable Amount</h6>
                                <h2 class="mb-0 text-success fw-bold">₹<?php echo number_format($total_payable_amount, 2); ?></h2>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-currency-rupee" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Average Earning</h6>
                                <h2 class="mb-0 text-info fw-bold">₹<?php echo $total_partners > 0 ? number_format($total_payable_amount / $total_partners, 2) : '0.00'; ?></h2>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-graph-up-arrow" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <h5 class="mb-3"><i class="bi bi-funnel-fill me-2"></i>Filter Partners</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search Partners</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               value="<?php echo htmlspecialchars($search_query); ?>" 
                               placeholder="Search by name, email, or partner ID">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <a href="partner-payout.php" class="btn btn-outline-secondary d-block">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <?php if (!empty($partners)): ?>
            <!-- Payout Processing Section -->
            <div class="payout-section">
                <h5 class="mb-3"><i class="bi bi-cash-coin me-2"></i>Process Payout</h5>
                <form method="POST" id="payoutForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="payout_month" class="form-label">Payout Month</label>
                            <input type="month" name="payout_month" id="payout_month" class="form-control" 
                                   value="<?php echo $current_month; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="transaction_reference" class="form-label">Transaction Reference</label>
                            <input type="text" name="transaction_reference" id="transaction_reference" 
                                   class="form-control" placeholder="Optional reference">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="process_payout" class="process-btn d-block" 
                                    id="processPayoutBtn" disabled>
                                <i class="bi bi-cash-coin me-1"></i>Process Payout
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" 
                                      placeholder="Add any notes for this payout batch"></textarea>
                        </div>
                    </div>

                    <!-- Partners Table -->
                    <div class="payout-table mt-4">
                        <div class="select-all-section">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label fw-bold" for="selectAll">
                                    Select All Partners (<span id="selectedCount">0</span> selected)
                                </label>
                                <span class="ms-3 text-muted">Total Selected Amount: ₹<span id="selectedAmount">0.00</span></span>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="50">Select</th>
                                        <th>Partner Details</th>
                                        <th>Bank Details</th>
                                        <th>Earnings</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($partners as $partner): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input partner-checkbox" type="checkbox" 
                                                       name="selected_partners[]" value="<?php echo $partner['id']; ?>"
                                                       data-amount="<?php echo $partner['total_earnings']; ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($partner['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    ID: <?php echo htmlspecialchars($partner['partner_id']); ?><br>
                                                    <?php echo htmlspecialchars($partner['email']); ?><br>
                                                    <?php echo htmlspecialchars($partner['phone']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="bank-details">
                                                <?php if (!empty($partner['bank_name'])): ?>
                                                    <strong><?php echo htmlspecialchars($partner['bank_name']); ?></strong><br>
                                                    A/C: <?php echo htmlspecialchars($partner['account_number']); ?><br>
                                                    IFSC: <?php echo htmlspecialchars($partner['ifsc_code']); ?><br>
                                                    <small><?php echo htmlspecialchars($partner['account_holder_name']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-danger">
                                                        <i class="bi bi-exclamation-triangle"></i> No bank details
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="earnings-amount">
                                                ₹<?php echo number_format($partner['total_earnings'], 2); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $partner['status']; ?>">
                                                <?php echo ucfirst($partner['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Partners pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <?php else: ?>
            <!-- No Partners Message -->
            <div class="text-center py-5">
                <i class="bi bi-cash-coin text-muted" style="font-size: 4rem;"></i>
                <h3 class="text-muted mt-3">No Partners with Pending Earnings</h3>
                <p class="text-muted">All partners have been paid or there are no earnings to process.</p>
                <a href="reports/generate-partner-earning-report.php" class="btn btn-primary">
                    <i class="bi bi-file-earmark-text me-1"></i>View Earning Reports
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const partnerCheckboxes = document.querySelectorAll('.partner-checkbox');
            const selectedCountSpan = document.getElementById('selectedCount');
            const selectedAmountSpan = document.getElementById('selectedAmount');
            const processPayoutBtn = document.getElementById('processPayoutBtn');
            const payoutForm = document.getElementById('payoutForm');

            // Update selected count and amount
            function updateSelection() {
                let selectedCount = 0;
                let selectedAmount = 0;

                partnerCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedCount++;
                        selectedAmount += parseFloat(checkbox.dataset.amount);
                    }
                });

                selectedCountSpan.textContent = selectedCount;
                selectedAmountSpan.textContent = selectedAmount.toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // Enable/disable process button
                processPayoutBtn.disabled = selectedCount === 0;

                // Update select all checkbox state
                if (selectedCount === 0) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = false;
                } else if (selectedCount === partnerCheckboxes.length) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = true;
                } else {
                    selectAllCheckbox.indeterminate = true;
                }
            }

            // Select all functionality
            selectAllCheckbox.addEventListener('change', function() {
                partnerCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelection();
            });

            // Individual checkbox change
            partnerCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelection);
            });

            // Form submission confirmation
            payoutForm.addEventListener('submit', function(e) {
                const selectedCount = document.querySelectorAll('.partner-checkbox:checked').length;
                const selectedAmount = selectedAmountSpan.textContent;
                
                if (selectedCount === 0) {
                    e.preventDefault();
                    alert('Please select at least one partner for payout.');
                    return;
                }

                const confirmation = confirm(
                    `Are you sure you want to process payout for ${selectedCount} partner(s)?\n\n` +
                    `Total Amount: ₹${selectedAmount}\n\n` +
                    `This action will:\n` +
                    `• Reset partner earnings to ₹0.00\n` +
                    `• Create payout history records\n` +
                    `• Cannot be undone`
                );

                if (!confirmation) {
                    e.preventDefault();
                }
            });

            // Initialize
            updateSelection();
        });
    </script>
</body>
</html>