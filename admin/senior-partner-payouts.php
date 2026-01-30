<?php
session_start();
require_once '../database/config.php';

$database = new Database();
$db = $database->getConnection();

// Initialize variables
$senior_partners = [];
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
            
            foreach ($selected_partners as $senior_partner_id) {
                // Get senior partner current earnings
                $partner_stmt = $db->prepare("SELECT total_earnings, name FROM senior_partners WHERE id = ?");
                $partner_stmt->execute([$senior_partner_id]);
                $partner = $partner_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($partner && $partner['total_earnings'] > 0) {
                    $payout_amount = $partner['total_earnings'];
                    
                    // Get bank details for this senior partner
                    $bank_stmt = $db->prepare("SELECT * FROM bank_details WHERE partner_id = ?");
                    $bank_stmt->execute([$senior_partner_id]);
                    $bank_details = $bank_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Insert payout history
                    $payout_stmt = $db->prepare("
                        INSERT INTO senior_partner_payout_history 
                        (senior_partner_id, payout_amount, previous_earnings, payout_date, payout_method, 
                         transaction_id, bank_name, account_number, ifsc_code, account_holder_name, 
                         status, notes, processed_by) 
                        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'completed', ?, ?)
                    ");
                    
                    $payout_stmt->execute([
                        $senior_partner_id,
                        $payout_amount,
                        $payout_amount,
                        $payment_method,
                        $transaction_reference,
                        $bank_details['bank_name'] ?? null,
                        $bank_details['account_number'] ?? null,
                        $bank_details['ifsc_code'] ?? null,
                        $bank_details['account_holder_name'] ?? null,
                        $notes,
                        $_SESSION['admin_name'] ?? 'Admin'
                    ]);
                    
                    // Reset senior partner total_earnings to 0
                    $update_stmt = $db->prepare("UPDATE senior_partners SET total_earnings = 0.00 WHERE id = ?");
                    $update_stmt->execute([$senior_partner_id]);
                    
                    $processed_count++;
                    $total_payout_amount += $payout_amount;
                }
            }
            
            $db->commit();
            $success_message = "Successfully processed payout for {$processed_count} senior partners. Total amount: ₹" . number_format($total_payout_amount, 2);
            
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "Error processing payout: " . $e->getMessage();
        }
    } else {
        $error_message = "Please select senior partners and specify payout month.";
    }
}

// Handle form submission for filtering
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $selected_month = $_GET['month'] ?? '';
    $selected_year = $_GET['year'] ?? '';
    $search_query = $_GET['search'] ?? '';
}

// Build query conditions - only show senior partners with earnings > 0
$conditions = ["sp.total_earnings > 0"];
$params = [];

if (!empty($search_query)) {
    $conditions[] = "(sp.name LIKE :search OR sp.email LIKE :search OR sp.partner_id LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$where_clause = 'WHERE ' . implode(' AND ', $conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM senior_partners sp $where_clause";
$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_partners = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_partners / $per_page);

// Get senior partners data with bank details (only those with earnings > 0)
$sql = "SELECT sp.*, 
               bd.bank_name, 
               bd.account_number, 
               bd.ifsc_code, 
               bd.account_holder_name,
               COUNT(p.id) as referred_partners_count
        FROM senior_partners sp 
        LEFT JOIN bank_details bd ON sp.id = bd.partner_id
        LEFT JOIN partners p ON sp.id = p.referred_by_senior_partner
        $where_clause 
        GROUP BY sp.id
        ORDER BY sp.total_earnings DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$senior_partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total payable amount
$payable_sql = "SELECT SUM(sp.total_earnings) as total_payable FROM senior_partners sp $where_clause";
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
    <title>Senior Partner Payout Management - Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
        .payout-header {
            background: linear-gradient(135deg, #7c43b9 0%, #9c5bc7 100%);
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
            background-color: #7c43b9;
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
        .btn-payout {
            background: linear-gradient(135deg, #7c43b9 0%, #9c5bc7 100%);
            border: none;
            color: white;
            font-weight: 600;
        }
        .btn-payout:hover {
            background: linear-gradient(135deg, #6a3a9e 0%, #8a4fb5 100%);
            color: white;
        }
        .earnings-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .bank-details {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .select-all-checkbox {
            transform: scale(1.2);
        }
        .partner-checkbox {
            transform: scale(1.1);
        }
        .alert-custom {
            border-radius: 10px;
            border: none;
        }
        .pagination .page-link {
            color: #7c43b9;
            border-color: #dee2e6;
        }
        .pagination .page-item.active .page-link {
            background-color: #7c43b9;
            border-color: #7c43b9;
        }
        .pagination .page-link:hover {
            color: #6a3a9e;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">

        <!-- Payout Header -->
        <div class="payout-header">
            <div class="container-fluid">
                <h1 class="mb-2"><i class="bi bi-cash-stack"></i> Senior Partner Payout Management</h1>
                <p class="mb-0">Manage and process payouts for senior partners with earnings</p>
            </div>
        </div>

        <div class="container-fluid">
            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Summary Statistics -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card p-4 text-center">
                        <div class="display-6 text-primary mb-2">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="text-primary"><?php echo number_format($total_partners); ?></h3>
                        <p class="text-muted mb-0">Senior Partners with Earnings</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card p-4 text-center">
                        <div class="display-6 text-success mb-2">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                        <h3 class="text-success">₹<?php echo number_format($total_payable_amount, 2); ?></h3>
                        <p class="text-muted mb-0">Total Payable Amount</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card p-4 text-center">
                        <div class="display-6 text-info mb-2">
                            <i class="bi bi-calendar-month"></i>
                        </div>
                        <h3 class="text-info"><?php echo date('F Y'); ?></h3>
                        <p class="text-muted mb-0">Current Month</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search Senior Partners</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by name, email, or partner ID..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-payout w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <a href="senior-partner-payouts.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Payout Processing Form -->
            <?php if (!empty($senior_partners)): ?>
            <form method="POST" id="payoutForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="payout_month" class="form-label">Payout Month</label>
                        <input type="month" class="form-control" id="payout_month" name="payout_month" 
                               value="<?php echo $current_month; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="upi">UPI</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="transaction_reference" class="form-label">Transaction Reference</label>
                        <input type="text" class="form-control" id="transaction_reference" name="transaction_reference" 
                               placeholder="Enter transaction ID/reference">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="process_payout" class="btn btn-payout w-100" 
                                onclick="return confirmPayout()">
                            <i class="bi bi-cash-stack"></i> Process Selected Payouts
                        </button>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                  placeholder="Add any notes about this payout batch..."></textarea>
                    </div>
                </div>

                <!-- Senior Partners Table -->
                <div class="payout-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" class="form-check-input select-all-checkbox" 
                                               id="selectAll" onchange="toggleAllCheckboxes()">
                                    </th>
                                    <th>Partner Details</th>
                                    <th>Contact Info</th>
                                    <th>Earnings</th>
                                    <th>Referred Partners</th>
                                    <th>Bank Details</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($senior_partners as $partner): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input partner-checkbox" 
                                                   name="selected_partners[]" value="<?php echo $partner['id']; ?>"
                                                   onchange="updateSelectAllCheckbox()">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <?php if (!empty($partner['image'])): ?>
                                                        <img src="../uploads/partners/<?php echo htmlspecialchars($partner['image']); ?>" 
                                                             class="rounded-circle" width="40" height="40" alt="Partner">
                                                    <?php else: ?>
                                                        <i class="bi bi-person-fill text-muted"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($partner['name']); ?></div>
                                                    <small class="text-muted">ID: <?php echo htmlspecialchars($partner['partner_id']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="small"><?php echo htmlspecialchars($partner['email']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($partner['phone'] ?? 'N/A'); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="earnings-badge">₹<?php echo number_format($partner['total_earnings'], 2); ?></span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <span class="badge bg-info"><?php echo $partner['referred_partners_count']; ?></span>
                                                <div class="small text-muted">Partners</div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($partner['bank_name']): ?>
                                                <div class="bank-details">
                                                    <div><strong><?php echo htmlspecialchars($partner['bank_name']); ?></strong></div>
                                                    <div>A/C: <?php echo htmlspecialchars($partner['account_number']); ?></div>
                                                    <div>IFSC: <?php echo htmlspecialchars($partner['ifsc_code']); ?></div>
                                                    <div>Holder: <?php echo htmlspecialchars($partner['account_holder_name']); ?></div>
                                                    <?php if (isset($partner['upi_id']) && $partner['upi_id']): ?>
                                                        <div>UPI: <?php echo htmlspecialchars($partner['upi_id']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Not Provided</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="viewPartnerDetails(<?php echo $partner['id']; ?>)">
                                                        <i class="bi bi-eye"></i> View Details
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="viewPayoutHistory(<?php echo $partner['id']; ?>)">
                                                        <i class="bi bi-clock-history"></i> Payout History
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <div class="payout-table">
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">No Senior Partners with Earnings Found</h4>
                        <p class="text-muted">There are currently no senior partners with earnings available for payout.</p>
                        <?php if (!empty($search_query)): ?>
                            <a href="senior-partner-payouts.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Clear Search
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Senior Partners pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search_query); ?>">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search_query); ?>">
                                    Next <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle all checkboxes
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.partner-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Update select all checkbox based on individual checkboxes
        function updateSelectAllCheckbox() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.partner-checkbox');
            const checkedBoxes = document.querySelectorAll('.partner-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                selectAll.indeterminate = false;
                selectAll.checked = false;
            } else if (checkedBoxes.length === checkboxes.length) {
                selectAll.indeterminate = false;
                selectAll.checked = true;
            } else {
                selectAll.indeterminate = true;
                selectAll.checked = false;
            }
        }

        // Confirm payout processing
        function confirmPayout() {
            const checkedBoxes = document.querySelectorAll('.partner-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                alert('Please select at least one senior partner for payout.');
                return false;
            }

            const payoutMonth = document.getElementById('payout_month').value;
            const paymentMethod = document.getElementById('payment_method').value;
            
            let totalAmount = 0;
            checkedBoxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const earningsText = row.querySelector('.earnings-badge').textContent;
                const amount = parseFloat(earningsText.replace('₹', '').replace(',', ''));
                totalAmount += amount;
            });

            const message = `Are you sure you want to process payout for ${checkedBoxes.length} senior partner(s)?\n\n` +
                          `Total Amount: ₹${totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}\n` +
                          `Payout Month: ${payoutMonth}\n` +
                          `Payment Method: ${paymentMethod}\n\n` +
                          `This action will reset their earnings to ₹0.00 and cannot be undone.`;

            return confirm(message);
        }

        // View partner details (placeholder function)
        function viewPartnerDetails(partnerId) {
            alert('View partner details functionality - Partner ID: ' + partnerId);
            // Implement modal or redirect to partner details page
        }

        // View payout history (placeholder function)
        function viewPayoutHistory(partnerId) {
            alert('View payout history functionality - Partner ID: ' + partnerId);
            // Implement modal or redirect to payout history page
        }

        // Initialize checkbox states on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectAllCheckbox();
        });
    </script>
</body>
</html>