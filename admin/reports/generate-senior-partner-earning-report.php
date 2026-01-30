<?php
session_start();

// Check if admin is logged in (uncomment when admin authentication is implemented)
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: ../login.php");
//     exit();
// }

require_once '../../database/config.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$database = new Database();
$db = $database->getConnection();

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $month = $_GET['month'] ?? date('Y-m');
    $search = $_GET['search'] ?? '';
    
    // Get senior partners data for export
    $sql = "SELECT 
                sp.id as partner_id,
                sp.name,
                sp.email,
                sp.phone,
                COALESCE(SUM(spe.earning_amount), 0) as total_earnings,
                bd.bank_name,
                bd.account_number,
                bd.ifsc_code,
                bd.account_holder_name
            FROM senior_partners sp
            LEFT JOIN senior_partner_earnings spe ON sp.id = spe.senior_partner_id 
                AND DATE_FORMAT(spe.created_at, '%Y-%m') = :month
            LEFT JOIN bank_details bd ON sp.id = bd.partner_id
            WHERE 1=1";
    
    $params = [':month' => $month];
    
    if (!empty($search)) {
        $sql .= " AND (sp.name LIKE :search OR sp.email LIKE :search2 OR sp.phone LIKE :search3)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
    }
    
    $sql .= " GROUP BY sp.id ORDER BY total_earnings DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create Excel file
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = [
        'A1' => 'Partner ID',
        'B1' => 'Name',
        'C1' => 'Email',
        'D1' => 'Phone',
        'E1' => 'Total Earnings (₹)',
        'F1' => 'Bank Name',
        'G1' => 'Account Number',
        'H1' => 'IFSC Code',
        'I1' => 'Account Holder Name'
    ];
    
    foreach ($headers as $cell => $header) {
        $sheet->setCellValue($cell, $header);
    }
    
    // Style headers
    $sheet->getStyle('A1:I1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7c43b9']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    
    // Add data
    $row = 2;
    foreach ($partners as $partner) {
        $sheet->setCellValue('A' . $row, $partner['partner_id']);
        $sheet->setCellValue('B' . $row, $partner['name']);
        $sheet->setCellValue('C' . $row, $partner['email']);
        $sheet->setCellValue('D' . $row, $partner['phone']);
        $sheet->setCellValue('E' . $row, '₹' . number_format($partner['total_earnings'], 2));
        $sheet->setCellValue('F' . $row, $partner['bank_name'] ?? 'Not Provided');
        $sheet->setCellValue('G' . $row, $partner['account_number'] ?? 'Not Provided');
        $sheet->setCellValue('H' . $row, $partner['ifsc_code'] ?? 'Not Provided');
        $sheet->setCellValue('I' . $row, $partner['account_holder_name'] ?? 'Not Provided');
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set filename and download
    $filename = 'Senior_Partner_Earnings_Report_' . $month . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Get filter parameters
$selected_month = $_GET['month'] ?? date('Y-m');
$search_query = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get senior partners with earnings data
$sql = "SELECT 
            sp.id as partner_id,
            sp.name,
            sp.email,
            sp.phone,
            COALESCE(SUM(spe.earning_amount), 0) as total_earnings,
            bd.bank_name,
            bd.account_number,
            bd.ifsc_code,
            bd.account_holder_name,
            COUNT(spe.id) as total_transactions
        FROM senior_partners sp
        LEFT JOIN senior_partner_earnings spe ON sp.id = spe.senior_partner_id 
            AND DATE_FORMAT(spe.created_at, '%Y-%m') = :month
        LEFT JOIN bank_details bd ON sp.id = bd.partner_id
        WHERE 1=1";

$count_sql = "SELECT COUNT(DISTINCT sp.id) as total
              FROM senior_partners sp
              LEFT JOIN senior_partner_earnings spe ON sp.id = spe.senior_partner_id 
                  AND DATE_FORMAT(spe.created_at, '%Y-%m') = :month
              LEFT JOIN bank_details bd ON sp.id = bd.partner_id
              WHERE 1=1";

$params = [':month' => $selected_month];

if (!empty($search_query)) {
    $search_condition = " AND (sp.name LIKE :search OR sp.email LIKE :search2 OR sp.phone LIKE :search3)";
    $sql .= $search_condition;
    $count_sql .= $search_condition;
    $params[':search'] = "%$search_query%";
    $params[':search2'] = "%$search_query%";
    $params[':search3'] = "%$search_query%";
}

$sql .= " GROUP BY sp.id ORDER BY total_earnings DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
// Bind other parameters first
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
// Bind LIMIT and OFFSET as integers
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_stmt = $db->prepare($count_sql);
$count_params = array_filter($params, function($key) {
    return !in_array($key, [':limit', ':offset']);
}, ARRAY_FILTER_USE_KEY);
$count_stmt->execute($count_params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// Get summary statistics
$summary_sql = "SELECT 
                    COUNT(DISTINCT sp.id) as total_partners,
                    COALESCE(SUM(spe.earning_amount), 0) as total_earnings,
                    COUNT(spe.id) as total_transactions
                FROM senior_partners sp
                LEFT JOIN senior_partner_earnings spe ON sp.id = spe.senior_partner_id 
                    AND DATE_FORMAT(spe.created_at, '%Y-%m') = :month";

$summary_stmt = $db->prepare($summary_sql);
$summary_stmt->execute([':month' => $selected_month]);
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior Partner Earning Report - Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        .report-header {
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
        .earnings-table {
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
        .export-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            .stat-card {
                margin-bottom: 1rem;
            }
            .filter-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-toggle" class="btn btn-primary d-md-none mb-3">
                <i class="bi bi-list"></i>
            </button>

            <!-- Report Header -->
            <div class="report-header text-center">
                <h1 class="mb-2"><i class="bi bi-graph-up-arrow"></i> Senior Partner Earning Report</h1>
                <p class="mb-0">Comprehensive earnings analysis for <?php echo date('F Y', strtotime($selected_month . '-01')); ?></p>
            </div>

            <!-- Summary Statistics -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card p-4 text-center">
                        <div class="display-6 text-primary mb-2">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="text-primary"><?php echo number_format($summary['total_partners']); ?></h3>
                        <p class="text-muted mb-0">Total Partners</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card p-4 text-center">
                        <div class="display-6 text-success mb-2">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                        <h3 class="text-success">₹<?php echo number_format($summary['total_earnings'], 2); ?></h3>
                        <p class="text-muted mb-0">Total Earnings</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card p-4 text-center">
                        <div class="display-6 text-info mb-2">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <h3 class="text-info"><?php echo number_format($summary['total_transactions']); ?></h3>
                        <p class="text-muted mb-0">Total Transactions</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="month" class="form-label">Select Month</label>
                        <input type="month" class="form-control" id="month" name="month" value="<?php echo $selected_month; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Partners</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="?export=excel&month=<?php echo $selected_month; ?>&search=<?php echo urlencode($search_query); ?>" 
                           class="btn export-btn w-100">
                            <i class="bi bi-file-earmark-excel"></i> Export to Excel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Earnings Table -->
            <div class="earnings-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Partner ID</th>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Email</th>
                                <th class="d-none d-lg-table-cell">Phone</th>
                                <th>Total Earnings</th>
                                <th class="d-none d-xl-table-cell">Bank Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($partners)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                            No senior partners found for the selected criteria.
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($partners as $partner): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $partner['partner_id']; ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="bi bi-person-fill text-muted"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($partner['name']); ?></div>
                                                    <small class="text-muted d-md-none"><?php echo htmlspecialchars($partner['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <small><?php echo htmlspecialchars($partner['email']); ?></small>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            <?php echo htmlspecialchars($partner['phone']); ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">₹<?php echo number_format($partner['total_earnings'], 2); ?></div>
                                            <small class="text-muted"><?php echo $partner['total_transactions']; ?> transactions</small>
                                        </td>
                                        <td class="d-none d-xl-table-cell">
                                            <?php if ($partner['bank_name']): ?>
                                                <div class="small">
                                                    <div><strong><?php echo htmlspecialchars($partner['bank_name']); ?></strong></div>
                                                    <div>A/C: <?php echo htmlspecialchars($partner['account_number']); ?></div>
                                                    <div>IFSC: <?php echo htmlspecialchars($partner['ifsc_code']); ?></div>
                                                    <div>Holder: <?php echo htmlspecialchars($partner['account_holder_name']); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Not Provided</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="viewDetails(<?php echo $partner['partner_id']; ?>)">
                                                        <i class="bi bi-eye"></i> View Details
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="viewTransactions(<?php echo $partner['partner_id']; ?>)">
                                                        <i class="bi bi-list-ul"></i> View Transactions
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&month=<?php echo $selected_month; ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&month=<?php echo $selected_month; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&month=<?php echo $selected_month; ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/admin-script.js"></script>
    
    <script>
        function viewDetails(partnerId) {
            // Implement view details functionality
            alert('View details for partner ID: ' + partnerId);
        }
        
        function viewTransactions(partnerId) {
            // Implement view transactions functionality
            alert('View transactions for partner ID: ' + partnerId);
        }
        
        // Auto-submit form on month change
        document.getElementById('month').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>