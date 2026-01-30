<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once '../database/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle receipt generation (PDF or Direct Print)
if ((isset($_POST['generate_receipt']) || isset($_GET['print_receipt'])) && (isset($_POST['order_id']) || isset($_GET['order_id']))) {
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : $_GET['order_id'];
    $print_mode = isset($_GET['print_receipt']) ? 'direct' : 'pdf';
    
    try {
        // Fetch order details
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            $error_message = "Order not found!";
        } else {
            if ($print_mode === 'direct') {
                // Direct printing
                printShippingLabel($order);
                $_SESSION['success_message'] = "Shipping label printed successfully!";
                header('Location: manage-orders.php');
                exit();
            } else {
                // Generate PDF
                generateCourierReceipt($order);
                exit();
            }
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching order: " . $e->getMessage();
    }
}

// Fetch all orders for selection
try {
    $stmt = $db->prepare("SELECT id, guest_name, guest_email, guest_phone, guest_full_address, guest_state, guest_district, guest_pincode, product_name, product_price, quantity, total_amount, order_status, created_at, tracking_number, courier_name, payment_status FROM orders ORDER BY created_at DESC");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}

/**
 * Print a 4x6 inch shipping label
 * 
 * @param array $order Order details
 */
function printShippingLabel($order) {
    try {
        // Create a temporary file to store the print output
        $tempFile = sys_get_temp_dir() . '/shipping_label_' . $order['id'] . '.txt';
        
        // Create connector and printer
        $connector = new FilePrintConnector($tempFile);
        $printer = new Printer($connector);
        
        // Set to 4x6 inch format (approximate in dots)
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        
        // Print logo placeholder
        $printer->setTextSize(2, 2);
        $printer->text("LOGO\n");
        $printer->setTextSize(1, 1);
        $printer->text("------------------------\n");
        
        // Print product name
        $printer->setTextSize(1, 2);
        $printer->text("Product Name\n");
        $printer->setTextSize(1, 1);
        $printer->text($order['product_name'] . "\n\n");
        
        // Print From address
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("From:\n");
        $printer->text("Company Name: WaryChary Care\n");
        $printer->text("Address: 123 Main Street\n");
        $printer->text("City: Mumbai\n");
        $printer->text("Zip: 400001\n");
        $printer->text("------------------------\n");
        
        // Print To address
        $printer->text("To:\n");
        $printer->text("Name: " . $order['guest_name'] . "\n");
        $printer->text("Address: " . $order['guest_full_address'] . "\n");
        $printer->text("City: " . $order['guest_district'] . "\n");
        $printer->text("State: " . $order['guest_state'] . "\n");
        $printer->text("Zip: " . $order['guest_pincode'] . "\n");
        $printer->text("Phone: " . $order['guest_phone'] . "\n");
        $printer->text("------------------------\n\n");
        
        // Print barcode placeholder
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("123ABCD456EFG\n");
        $printer->setBarcodeHeight(80);
        $printer->setBarcodeWidth(3);
        $printer->barcode($order['id'] . time(), Printer::BARCODE_CODE39);
        $printer->text("\n");
        
        // Print shipping icons
        $printer->text("   [ICON]   [ICON]   [ICON]   \n\n");
        
        // Print order number and delivery instructions
        $printer->text("Item No: " . str_pad($order['id'], 7, '0', STR_PAD_LEFT) . "\n");
        $printer->text("Delivery Instruction\n");
        $printer->text("Important Information\n");
        
        // Print QR code placeholder
        $printer->text("\n[QR CODE]\n");
        $printer->qrCode($order['id'] . '-' . $order['guest_name'], Printer::QR_ECLEVEL_L, 8);
        
        // Cut the receipt
        $printer->cut();
        
        // Close the printer
        $printer->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Printing error: " . $e->getMessage());
        return false;
    }
}

function generateCourierReceipt($order) {
    // Configure dompdf
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    
    $dompdf = new Dompdf($options);
    
    // Create HTML content for receipt
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 15mm;
                size: A4;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 11px;
                line-height: 1.3;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #6f42c1;
                padding-bottom: 8px;
                margin-bottom: 15px;
            }
            .company-name {
                font-size: 18px;
                font-weight: bold;
                color: #6f42c1;
                margin-bottom: 3px;
            }
            .receipt-title {
                font-size: 14px;
                font-weight: bold;
                color: #333;
                margin-bottom: 3px;
            }
            .receipt-info {
                font-size: 10px;
                color: #666;
            }
            .section {
                margin-bottom: 12px;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .section-title {
                font-size: 12px;
                font-weight: bold;
                color: #6f42c1;
                margin-bottom: 5px;
                border-bottom: 1px solid #eee;
                padding-bottom: 2px;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 3px;
                font-size: 10px;
            }
            .info-label {
                font-weight: bold;
                width: 35%;
            }
            .info-value {
                width: 65%;
                text-align: left;
            }
            .two-column {
                display: flex;
                gap: 10px;
            }
            .column {
                flex: 1;
            }
            .total-section {
                background-color: #f8f9fa;
                border: 2px solid #6f42c1;
                text-align: center;
                padding: 8px;
                margin: 10px 0;
            }
            .total-amount {
                font-size: 14px;
                font-weight: bold;
                color: #6f42c1;
            }
            .footer {
                text-align: center;
                font-size: 9px;
                color: #666;
                margin-top: 15px;
                padding-top: 8px;
                border-top: 1px solid #ddd;
            }
            .signature-section {
                display: flex;
                justify-content: space-between;
                margin-top: 20px;
                font-size: 10px;
            }
            .signature-box {
                text-align: center;
                width: 45%;
                border-top: 1px solid #333;
                padding-top: 5px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="company-name">WARY CHARY CARE</div>
            <div class="receipt-title">COURIER RECEIPT</div>
            <div class="receipt-info">
                Receipt #: CR-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . ' | 
                Date: ' . date('d/m/Y H:i') . '
            </div>
        </div>

        <div class="two-column">
            <div class="column">
                <div class="section">
                    <div class="section-title">SENDER DETAILS</div>
                    <div class="info-row">
                        <span class="info-label">Company:</span>
                        <span class="info-value">Wary Chary Care</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value">Business Address</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Contact:</span>
                        <span class="info-value">+91-XXXXXXXXXX</span>
                    </div>
                </div>
            </div>
            
            <div class="column">
                <div class="section">
                    <div class="section-title">RECIPIENT DETAILS</div>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value">' . htmlspecialchars($order['guest_name'] ?? '') . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">' . htmlspecialchars($order['guest_email'] ?? '') . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">' . htmlspecialchars($order['guest_phone'] ?? '') . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value">' . htmlspecialchars($order['guest_full_address'] ?? '') . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location:</span>
                        <span class="info-value">' . htmlspecialchars($order['guest_district'] ?? '') . ', ' . htmlspecialchars($order['guest_state'] ?? '') . ' - ' . htmlspecialchars($order['guest_pincode'] ?? '') . '</span>
                    </div>
                </div>
            </div>
            
            <div class="column">
                <div class="section">
                    <div class="section-title">ORDER DETAILS</div>
                    <div class="info-row">
                        <span class="info-label">Order ID:</span>
                        <span class="info-value">#' . $order['id'] . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value">' . date('d/m/Y', strtotime($order['created_at'])) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">' . ucfirst($order['order_status']) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Courier:</span>
                        <span class="info-value">' . (!empty($order['courier_name']) ? htmlspecialchars($order['courier_name']) : 'Not Assigned') . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tracking:</span>
                        <span class="info-value">' . (!empty($order['tracking_number']) ? htmlspecialchars($order['tracking_number']) : 'Not Available') . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment:</span>
                        <span class="info-value">' . ucfirst($order['payment_status']) . '</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="two-column">
            <div class="column">
                <div class="section">
                    <div class="section-title">PRODUCT DETAILS</div>
                    <div class="info-row">
                        <span class="info-label">Product Name:</span>
                        <span class="info-value">' . htmlspecialchars($order['product_name'] ?? '') . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Unit Price:</span>
                        <span class="info-value">Rs. ' . number_format($order['product_price'] ?? 0, 2) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Quantity:</span>
                        <span class="info-value">' . ($order['quantity'] ?? 1) . '</span>
                    </div>
                </div>
            </div>
            
            <div class="column">
                <div class="section">
                    <div class="section-title">PAYMENT SUMMARY</div>
                    <div class="info-row">
                        <span class="info-label">Subtotal:</span>
                        <span class="info-value">Rs. ' . number_format(($order['product_price'] ?? 0) * ($order['quantity'] ?? 1), 2) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Shipping:</span>
                        <span class="info-value">Rs. 0.00</span>
                    </div>
                    <div class="total-section">
                        <div class="total-amount">TOTAL: Rs. ' . number_format($order['total_amount'], 2) . '</div>
                    </div>
                </div>
            </div>
        </div>';
            
    // Add tracking number barcode section if available
    if (!empty($order['tracking_number'])) {
        $html .= '
            <div class="barcode-section">
                <div style="font-weight: bold; margin-bottom: 10px; text-align: center;">TRACKING NUMBER</div>
                <div class="tracking-number" style="text-align: center;">' . htmlspecialchars($order['tracking_number']) . '</div>
                <div style="font-size: 10px; margin-top: 5px; text-align: center;">Scan or enter this number for tracking</div>
            </div>';
    }
    
    $html .= '
            <!-- Footer -->
            <div class="footer">
                <div>WaryChary Care - Committed to Your Health & Wellness</div>
                <div>This is a computer generated receipt. For queries, contact: support@warycharycare.com</div>
                <div>Generated on: ' . date('d/m/Y H:i:s') . ' | Receipt ID: WCC-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . '</div>
            </div>
        </div>
    </body>
    </html>';
    
    // Load HTML content
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render PDF
    $dompdf->render();
    
    // Output PDF for download
    $filename = 'Courier_Receipt_Order_' . $order['id'] . '_' . date('Y-m-d') . '.pdf';
    $dompdf->stream($filename, array('Attachment' => true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courier Receipt - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-style.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .receipt-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-id {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .order-date {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .order-details {
            padding: 1.5rem;
        }
        
        .customer-info, .product-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.2rem;
            height: 100%;
        }
        
        .customer-info h6, .product-info h6 {
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 0.5rem;
        }
        
        .customer-info p, .product-info p {
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .receipt-actions {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-filters {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .btn-generate {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-confirmed { background-color: #d1ecf1; color: #0c5460; }
        .status-processing { background-color: #d4edda; color: #155724; }
        .status-dispatched { background-color: #cce5ff; color: #004085; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Courier Receipt Generator</h1>
            <div class="text-muted">
                <i class="bi bi-receipt"></i> Generate Professional Receipts
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search Orders</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by order ID, customer name, or email..." 
                           value="<?php echo htmlspecialchars($search ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-generate d-block w-100">
                        <i class="bi bi-search"></i> Search Orders
                    </button>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <a href="courier-receipt.php" class="btn btn-outline-secondary d-block w-100">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-receipt" style="font-size: 4rem; color: #6c757d;"></i>
                <h4 class="mt-3 text-muted">No orders found</h4>
                <p class="text-muted">Try adjusting your search criteria or check if orders exist</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="flex-grow-1">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <div class="order-date">
                                <i class="bi bi-calendar3"></i> 
                                <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div class="status-badge status-<?php echo $order['order_status']; ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="customer-info">
                                    <h6 class="mb-2"><i class="bi bi-person-circle"></i> Customer Information</h6>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['guest_name'] ?? ''); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['guest_email'] ?? ''); ?></p>
                                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['guest_phone'] ?? ''); ?></p>
                                    <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($order['guest_full_address'] ?? ''); ?></p>
                                    <p class="mb-0"><strong>State:</strong> <?php echo htmlspecialchars($order['guest_state'] ?? ''); ?>, <strong>District:</strong> <?php echo htmlspecialchars($order['guest_district'] ?? ''); ?>, <strong>PIN:</strong> <?php echo htmlspecialchars($order['guest_pincode'] ?? ''); ?></p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="product-info">
                                    <h6 class="mb-2"><i class="bi bi-box-seam"></i> Product & Shipping Information</h6>
                                    <p class="mb-1"><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name'] ?? ''); ?></p>
                                    <p class="mb-1"><strong>Price:</strong> Rs. <?php echo number_format($order['product_price'] ?? 0, 2); ?></p>
                                    <p class="mb-1"><strong>Quantity:</strong> <?php echo $order['quantity'] ?? 1; ?></p>
                                    <p class="mb-1"><strong>Total Amount:</strong> Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                                    <?php if (!empty($order['tracking_number'])): ?>
                                        <p class="mb-1"><strong>Tracking:</strong> <?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($order['courier_name'])): ?>
                                        <p class="mb-0"><strong>Courier:</strong> <?php echo htmlspecialchars($order['courier_name'] ?? ''); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Receipt Actions -->
                    <div class="receipt-actions">
                        <div class="order-summary">
                            <span class="text-muted">Total: </span>
                            <span class="fw-bold text-success">Rs. <?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="generate_receipt" class="btn btn-generate">
                                <i class="bi bi-file-earmark-pdf"></i> Generate Receipt
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#ordersTable tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('show')) {
                    alert.classList.remove('show');
                }
            });
        }, 5000);
    </script>
</body>
</html>