<?php
// session_start();

// Check if admin is logged in - Commented out for direct access
// if (!isset($_SESSION['admin_id'])) {
//     header('Location: ../login.php');
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

// Initialize variables
$partners = [];
$total_partners = 0;
$total_earnings = 0;
$selected_month = '';
$selected_year = '';
$search_query = '';
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_month = $_POST['month'] ?? '';
    $selected_year = $_POST['year'] ?? '';
    $search_query = $_POST['search'] ?? '';
    
    // Handle Excel export
    if (isset($_POST['export_excel'])) {
        exportToExcel($db, $selected_month, $selected_year, $search_query);
        exit();
    }
}

// Get filter values from GET parameters for pagination
$selected_month = $_GET['month'] ?? $selected_month;
$selected_year = $_GET['year'] ?? $selected_year;
$search_query = $_GET['search'] ?? $search_query;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($selected_month) && !empty($selected_year)) {
    $conditions[] = "MONTH(p.created_at) = :month AND YEAR(p.created_at) = :year";
    $params[':month'] = $selected_month;
    $params[':year'] = $selected_year;
}

if (!empty($search_query)) {
    $conditions[] = "(p.name LIKE :search OR p.email LIKE :search OR p.partner_id LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM partners p $where_clause";
$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_partners = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_partners / $per_page);

// Get partners data with bank details
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
        ORDER BY p.created_at DESC 
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

// Calculate total earnings for current filter
$earnings_sql = "SELECT SUM(p.total_earnings) as total_earnings FROM partners p $where_clause";
$earnings_stmt = $db->prepare($earnings_sql);
foreach ($params as $key => $value) {
    $earnings_stmt->bindValue($key, $value);
}
$earnings_stmt->execute();
$total_earnings = $earnings_stmt->fetch(PDO::FETCH_ASSOC)['total_earnings'] ?? 0;

function exportToExcel($db, $month, $year, $search) {
    // Build conditions for export
    $conditions = [];
    $params = [];
    
    if (!empty($month) && !empty($year)) {
        $conditions[] = "MONTH(p.created_at) = :month AND YEAR(p.created_at) = :year";
        $params[':month'] = $month;
        $params[':year'] = $year;
    }
    
    if (!empty($search)) {
        $conditions[] = "(p.name LIKE :search OR p.email LIKE :search OR p.partner_id LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get all partners data for export
    $sql = "SELECT p.partner_id, p.name, p.email, p.phone, p.gender, p.state, p.district, 
                   p.earning, p.total_earnings, p.status, p.created_at,
                   pbd.bank_name, pbd.account_number, pbd.ifsc_code, pbd.account_holder_name,
                   sp.name as senior_partner_name
            FROM partners p 
            LEFT JOIN partner_bank_details pbd ON p.id = pbd.partner_id 
            LEFT JOIN senior_partners sp ON p.referred_by_senior_partner = sp.id
            $where_clause 
            ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
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
        'E1' => 'Gender',
        'F1' => 'State',
        'G1' => 'District',
        'H1' => 'Current Earning',
        'I1' => 'Total Earnings',
        'J1' => 'Status',
        'K1' => 'Bank Name',
        'L1' => 'Account Number',
        'M1' => 'IFSC Code',
        'N1' => 'Account Holder',
        'O1' => 'Senior Partner',
        'P1' => 'Joined Date'
    ];
    
    foreach ($headers as $cell => $header) {
        $sheet->setCellValue($cell, $header);
    }
    
    // Style headers
    $sheet->getStyle('A1:P1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
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
        $sheet->setCellValue('E' . $row, ucfirst($partner['gender']));
        $sheet->setCellValue('F' . $row, $partner['state']);
        $sheet->setCellValue('G' . $row, $partner['district']);
        $sheet->setCellValue('H' . $row, '₹' . number_format($partner['earning'], 2));
        $sheet->setCellValue('I' . $row, '₹' . number_format($partner['total_earnings'], 2));
        $sheet->setCellValue('J' . $row, ucfirst($partner['status']));
        $sheet->setCellValue('K' . $row, $partner['bank_name'] ?? 'Not Added');
        $sheet->setCellValue('L' . $row, $partner['account_number'] ?? 'Not Added');
        $sheet->setCellValue('M' . $row, $partner['ifsc_code'] ?? 'Not Added');
        $sheet->setCellValue('N' . $row, $partner['account_holder_name'] ?? 'Not Added');
        $sheet->setCellValue('O' . $row, $partner['senior_partner_name'] ?? 'Direct');
        $sheet->setCellValue('P' . $row, date('d-m-Y', strtotime($partner['created_at'])));
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'P') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set filename
    $filename = 'Partner_Earning_Report_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Earning Report - Admin Panel</title>
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
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        
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
                <h1 class="mb-2"><i class="bi bi-people-fill"></i> Partner Earning Report</h1>
                <p class="mb-0">Comprehensive earnings analysis and partner management</p>
            </div>
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Partners</h6>
                                <h2 class="mb-0 text-primary fw-bold"><?php echo number_format($total_partners); ?></h2>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-people-fill" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Earnings</h6>
                                <h2 class="mb-0 text-success fw-bold">₹<?php echo number_format($total_earnings, 2); ?></h2>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-currency-rupee" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Average Earning</h6>
                                <h2 class="mb-0 text-info fw-bold">₹<?php echo $total_partners > 0 ? number_format($total_earnings / $total_partners, 2) : '0.00'; ?></h2>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-graph-up-arrow" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Export Report</h6>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
                                    <input type="hidden" name="year" value="<?php echo htmlspecialchars($selected_year); ?>">
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button type="submit" name="export_excel" class="export-btn">
                                        <i class="bi bi-download me-1"></i>Excel
                                    </button>
                                </form>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-file-earmark-excel" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <h5 class="mb-3"><i class="bi bi-funnel-fill me-2"></i>Filter Options</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="month" class="form-label">Month</label>
                        <select name="month" id="month" class="form-select">
                            <option value="">All Months</option>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($selected_month == $i) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year" class="form-label">Year</label>
                        <select name="year" id="year" class="form-select">
                            <option value="">All Years</option>
                            <?php for($year = date('Y'); $year >= 2020; $year--): ?>
                                <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Partner</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Search by name, email, or phone..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Partners Table -->
            <div class="earnings-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Partner ID</th>
                                <th>Partner Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Location</th>
                                <th>Current Earning</th>
                                <th>Total Earnings</th>
                                <th>Bank Details</th>
                                <th>Senior Partner</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($partners)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-2 mb-0">No partners found matching your criteria</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($partners as $partner): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($partner['partner_id']); ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <?php echo strtoupper(substr($partner['name'], 0, 1)); ?>
                                                </div>
                                                <?php echo htmlspecialchars($partner['name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($partner['email']); ?></td>
                                        <td><?php echo htmlspecialchars($partner['phone']); ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($partner['district'] . ', ' . $partner['state']); ?>
                                            </small>
                                        </td>
                                        <td><span class="text-success fw-bold">₹<?php echo number_format($partner['earning'], 2); ?></span></td>
                                        <td><span class="text-primary fw-bold">₹<?php echo number_format($partner['total_earnings'], 2); ?></span></td>
                                        <td>
                                            <?php if ($partner['bank_name']): ?>
                                                <small>
                                                    <strong><?php echo htmlspecialchars($partner['bank_name']); ?></strong><br>
                                                    <?php echo htmlspecialchars($partner['account_number']); ?><br>
                                                    <span class="text-muted"><?php echo htmlspecialchars($partner['ifsc_code']); ?></span>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">Not Added</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($partner['senior_partner_name']): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($partner['senior_partner_name']); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Direct</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $partner['status']; ?>">
                                                <?php echo ucfirst($partner['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($partner['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>&month=<?php echo urlencode($selected_month); ?>&year=<?php echo urlencode($selected_year); ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&month=<?php echo urlencode($selected_month); ?>&year=<?php echo urlencode($selected_year); ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>&month=<?php echo urlencode($selected_month); ?>&year=<?php echo urlencode($selected_year); ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>