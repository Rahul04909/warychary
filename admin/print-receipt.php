<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_log.txt');

use Dompdf\Dompdf;
use Dompdf\Options;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Fallback function to generate a simple text-based "barcode" image
function generateTextBarcode($text) {
    // Create a simple image with the text
    $im = imagecreate(300, 50);
    $bg = imagecolorallocate($im, 255, 255, 255);
    $textcolor = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 10, 15, "Order #" . $text, $textcolor);
    
    // Capture the image data
    ob_start();
    imagepng($im);
    $imagedata = ob_get_clean();
    imagedestroy($im);
    
    return $imagedata;
}

// Function to generate QR code
function generateQrCodeImage($data) {
    try {
        // Original QR code generation code (commented out for testing)
        
        $options = new chillerlan\QRCode\QROptions([
            'version'      => 7, // QR Code version (adjust as needed)
            'outputType'   => chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => chillerlan\QRCode\QRCode::ECC_H,
            'scale'        => 10, // Adjust for size (e.g., 10 for 300px with version 7)
            'imageBase64'  => true,
            'foregroundColor' => [0, 0, 0, 0], // Black
            'backgroundColor' => [255, 255, 255, 0], // White
        ]);

        $qrcode = new chillerlan\QRCode\QRCode($options);
        $qrCodeImage = $qrcode->render($data);
        
        // The render method with imageBase64 true returns a data URI, we need to extract the base64 part
        $base64_image = explode(',', $qrCodeImage)[1];
        return base64_decode($base64_image);
        
    } catch (Exception $e) {
        error_log("QR Code generation failed: " . $e->getMessage());
        return null;
    }
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('Location: manage-orders.php');
    exit();
}

$order_id = $_GET['order_id'];

// Check if we're in preview mode or print mode
$preview_mode = isset($_GET['preview']) && $_GET['preview'] == 'true';

try {
    // Enable error logging
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_log("Processing order ID: " . $order_id);
    
    // Fetch order details
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error_message'] = "Order not found!";
        header('Location: manage-orders.php');
        exit();
    }
    
    // Generate barcode (with fallback if Picqer library is not available)
    $barcode_data = $order['id'] . time();
    
    // Generate QR code
    $order_details_url = "http://yourdomain.com/order_details.php?order_id=" . $order['id']; // Replace with actual URL
    $qrcode_image_data = generateQrCodeImage($order_details_url);
    $qrcode = $qrcode_image_data ? base64_encode($qrcode_image_data) : null;

    try {
        if (class_exists('Picqer\Barcode\BarcodeGeneratorPNG')) {
            // Use Picqer if available
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $barcode = base64_encode($generator->getBarcode($barcode_data, $generator::TYPE_CODE_128, 2, 50));
        } else {
            // Fallback: Use a text representation instead of an actual barcode
            error_log("Picqer Barcode library not found. Using fallback method.");
            $barcode = base64_encode(generateTextBarcode($barcode_data));
        }
    } catch (Exception $e) {
        // If barcode generation fails, use fallback
        error_log("Barcode generation failed: " . $e->getMessage() . ". Using fallback method.");
        $barcode = base64_encode(generateTextBarcode($barcode_data));
    }
    
    if ($preview_mode) {
        // Show preview
        displayReceiptPreview($order, $barcode, $qrcode);
    } else {
        // Generate PDF for printing
        generateReceiptPDF($order, $barcode, $qrcode);
        
        $_SESSION['success_message'] = "Shipping label generated successfully!";
        header('Location: manage-orders.php');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("PDO Error in print-receipt.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error occurred. Please contact support.";
    header('Location: manage-orders.php');
    exit();
} catch (\Dompdf\Exception $e) {
    error_log("DomPDF Error in print-receipt.php: " . $e->getMessage());
    $_SESSION['error_message'] = "PDF generation error. Please contact support.";
    header('Location: manage-orders.php');
    exit();
} catch (Exception $e) {
    error_log("General Error in print-receipt.php: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred. Please contact support.";
    header('Location: manage-orders.php');
    exit();
}

/**
 * Display a preview of the shipping label
 * 
 * @param array $order Order details
 * @param string $barcode Base64 encoded barcode image
 * @param string $qrcode_data Base64 encoded QR code image
 */
function displayReceiptPreview($order, $barcode, $qrcode_data) {
    // Include header
    include_once 'sidebar.php';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Preview - WaryChary Care Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="manage-orders.php">Orders</a></li>
                            <li class="breadcrumb-item active">Receipt Preview</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-tools">
                                    <a href="print-receipt.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-print"></i> Print Receipt
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8 mx-auto">
                                        <div class="receipt-preview card shadow-sm" style="width: 4in; padding: 15px; margin: 0 auto; font-family: 'Arial', sans-serif; background-color: white;">
                                            <!-- Header with Logo -->
                                            <div class="text-center mb-3">
                                                <img src="https://warychary.com/logo/warycharycare.png" alt="WaryChary Care" class="img-fluid" style="max-width: 120px;">
                                                <div class="small text-muted mt-2">WaryChary Care - Premium Health Products</div>
                                            </div>
                                            
                                            <!-- Divider -->
                                            <div class="border-bottom border-2 my-3"></div>
                                            
                                            <!-- Order Information -->
                                            <div class="text-center mb-3">
                                                <div class="fw-bold">Order #<?php echo str_pad($order['id'], 7, '0', STR_PAD_LEFT); ?></div>
                                                <div class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                                            </div>
                                            
                                            <!-- Product Information -->
                                            <div class="text-center mb-3 p-2 bg-light rounded">
                                                <div class="fw-bold"><?php echo $order['product_name']; ?></div>
                                                <div class="small text-muted">Qty: <?php echo $order['quantity']; ?> | Rs. <?php echo number_format($order['product_price'], 2); ?></div>
                                            </div>
                                            
                                            <!-- Shipping Information -->
                                            <div class="mb-3">
                                                <div class="row align-items-center">
                                                    <!-- From and To Addresses -->
                                                    <div class="col-12">
                                                        <div class="bg-light p-1 rounded small fw-bold mb-2">TO:</div>
                                                        <div class="small lh-sm mb-3">
                                                            <strong><?php echo $order['guest_name']; ?></strong><br>
                                                            <?php echo $order['guest_full_address']; ?><br>
                                                            <?php echo $order['guest_district']; ?>, <?php echo $order['guest_state']; ?><br>
                                                            <?php echo $order['guest_pincode']; ?><br>
                                                            Phone: <?php echo $order['guest_phone']; ?>
                                                        </div>
                                                        <div class="bg-light p-1 rounded small fw-bold mb-2">FROM:</div>
                                                        <div class="small lh-sm">
                                                            <strong>WaryChary Care</strong><br>
                                                            Hissar, Haryana, <br>
                                                            400001<br>
                                                            India
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Delivery Instructions -->
                                            <div class="mb-3 p-2 bg-light rounded small">
                                                <div class="fw-bold mb-1">Delivery Instructions:</div>
                                                <div>Please handle with care. Contact recipient before delivery.</div>
                                            </div>
                                            
                                            <!-- Footer -->
                                            <div class="text-center small text-muted mt-3">
                                                Thank you for choosing WaryChary Care!<br>
                                                For customer support: support@warychary.com
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/admin.js"></script>
</body>
</html>
    <?php
}

/**
 * Generate a PDF of the shipping label
 * 
 * @param array $order Order details
 * @param string $barcode Base64 encoded barcode image
 * @param string $qrcode_data Base64 encoded QR code image
 */
function generateReceiptPDF($order, $barcode, $qrcode_data) {
    try {
        // Create PDF options with safe defaults for cPanel
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false); // Disable remote for security
        $options->set('debugKeepTemp', false); 
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', __DIR__);
        
        // Use absolute paths with proper permissions
        $tempDir = sys_get_temp_dir();
        if (is_writable($tempDir)) {
            $options->set('tempDir', $tempDir);
        }
        
        // Simplified font configuration
        $fontDir = __DIR__ . '/../vendor/dompdf/dompdf/lib/fonts';
        if (is_dir($fontDir) && is_readable($fontDir)) {
            $options->set('fontDir', $fontDir);
            $options->set('fontCache', $fontDir);
        }
        
        // Create new PDF instance with error handling
        $dompdf = new Dompdf($options);
        $dompdf->setPaper([0, 0, 76.2 * 2 * 2.83, 127 * 25 * 2.83], 'portrait'); // Doubled width and height for thermal paper
    
         // Read logo image and base64 encode it
         $logoPath = __DIR__ . '/../logo/logo.png';
         $logoData = base64_encode(file_get_contents($logoPath));
         $logoMime = 'image/png'; // Assuming PNG format
    
     // Generate HTML content for the PDF
     $html = '
     <!DOCTYPE html>
     <html>
     <head>
         <meta charset="UTF-8">
         <title>Shipping Label</title>
         <style>
             @page {
                 margin: 3mm; /* Smaller margins for thermal print */
                 padding: 0;
                 size: 152.4mm 254mm; /* Doubled 3x5 inch */
             }
             body {
                 font-family: \'Helvetica Neue\', \'Helvetica\', Helvetica, Arial, sans-serif;
                 line-height: 1.6;
                 margin: 0 auto;
                 padding: 0;
                 color: #000000; /* Black text for thermal print */
                 font-size: 19px; /* Increased base font size */
                 text-align: center;
                 width: 100%;
                 max-width: 100%;
             }
             .receipt-container {
                 width: 100%;
                 max-width: 100%;
                 margin: 0 auto;
                 padding: 5px; /* Smaller padding */
                 box-sizing: border-box;
                 border: 1px solid #eee;
                 box-shadow: 0 0 10px rgba(0, 0, 0, .15);
                 font-size: 20px;
                 line-height: 20px;
             }
             body {
                 font-family: \'Arial\', sans-serif;
                 margin: 0 auto;
                 padding: 0;
                 color: #000; /* Black text for thermal print */
                 font-size: 14px; /* Increased base font size */
                 text-align: center;
                 max-width: 176.2mm; /* Max width for thermal paper */
             }
             .receipt-container {
                 width: 100%;
                 margin: 0 auto;
                 padding: 5px; /* Smaller padding */
                 box-sizing: border-box;
             }
             .header {
                 text-align: center;
                 margin-bottom: 10px; /* Smaller margin */
                 border-bottom: 1px dashed #000; /* Black dashed line */
                 padding-bottom: 5px; /* Smaller padding */
             }
             .header-section {
                 text-align: center;
                 margin-bottom: 15px;
                 padding-bottom: 10px;
                 border-bottom: 1px dashed #000;
             }
             .company-info {
                 margin-top: 10px;
             }
             .company-tagline {
                 font-size: 14px;
                 color: #666;
                 margin-top: -20px;
             }
             .company-logo {
                 max-width: 60mm; /* Adjust logo size for thermal print */
                 height: auto;
                 margin-bottom: 5px;
             }
             .company-name {
                 font-size: 20px; /* Larger font size */
                 font-weight: bold;
                 margin-bottom: 3px;
             }
             .company-tagline {
                 font-size: 12px;
                 color: #333;
                 margin-top: -25px;
             
             }
             .divider {
                 border-top: 1px dashed #000;
                 margin: 10px 0;
             }
             .order-info {
                 text-align: center;
                 margin-bottom: 10px;
             }
             .order-number {
                 font-size: 18px; /* Larger font size */
                 font-weight: bold;
                 margin-bottom: 3px;
             }
             .order-date {
                 font-size: 12px;
                 color: #333;
             }
             .product-info {
                 text-align: center;
                 margin-bottom: 15px;
                 padding: 8px;
                 background-color: #eee; /* Light background for product info */
                 border-radius: 3px;
             }
             .product-name {
                 font-size: 16px; /* Larger font size */
                 font-weight: bold;
                 margin-bottom: 3px;
             }
             .product-details {
                 font-size: 14px;
                 color: #333;
             }
             .delivery-instructions {
                 margin: 10px 0;
                 padding: 8px;
                 background-color: #eee;
                 border-radius: 3px;
                 font-size: 12px;
                 text-align: left;
             }
             .instruction-title {
                 font-weight: bold;
                 margin-bottom: 3px;
             }
             .instruction-content {
                 color: #333;
             }
             .footer {
                 text-align: center;
                 margin-top: 15px;
                 font-size: 12px;
                 color: #333;
             }
             .address-qr-section {
                 display: flex;
                 justify-content: space-between;
                 margin-bottom: 15px;
                 border-bottom: none; /* Removed dashed line for cleaner look */
                 padding-bottom: 0;
             }
             .address-block {
                 flex: 1;
                 display: flex;
                 flex-direction: column;
                 gap: 10px;
             }
             .address-item {
                 border: 1px solid #ddd; /* Softer border */
                 padding: 8px; /* Increased padding */
                 border-radius: 5px; /* Slightly more rounded corners */
                 text-align: left;
                 background-color: #f9f9f9; /* Light background for items */
             }
             .address-header {
                 font-weight: bold;
                 font-size: 14px; /* Increased font size */
                 margin-bottom: 5px; /* Increased margin */
                 display: block;
                 background-color: #e9e9e9; /* Slightly darker background */
                 padding: 4px 8px;
                 border-radius: 3px;
                 color: #333;
             }
             .address-content {
                 font-size: 16px; /* Increased font size */
                 line-height: 1.5;
                 padding: 0 8px;
                 color: #555;
             }
         </style>
     </head>
     <body>
         <div class="receipt-container">
            <div class="header-section">
                <img src="data:' . $logoMime . ';base64,' . $logoData . '" alt="Company Logo" class="company-logo">
                <div class="company-info">
                    <div class="company-tagline"><b>Care With Awareness</b></div>
                    <b><div class="order-id" style="font-size: 12px; color: #666; margin-top: 5px;">Order ID: #' . $order['id'] . '</div><b>
                </div>
            </div>
                 <div class="address-qr-section">
                     <div class="address-block">
                         <div class="address-item">
                             <span class="address-header">TO:</span>
                             <div class="address-content">
                                 <strong>' . $order['guest_name'] . '</strong><br>
                                 <strong>' . $order['guest_full_address'] .'</strong><br>
                                 <strong>' . $order['guest_district'] . ', ' . $order['guest_state'] . '</strong><br>
                                 <strong>' . $order['guest_pincode'] . '</strong><br>
                                 <strong>Phone: ' . $order['guest_phone'] . '</strong>
                             </div>
                         </div>
                     </div>
                 </div>
            </div>
            <div class="address-item">
                <span class="address-header">FROM:</span>
                <div class="address-content">
                    <strong>WaryChary Care</strong><br><strong>
                    21-Choudhary Complex</strong>
                    <br><Strong> Hissar, 125006 </strong><br>
                   <strong> India </strong>
                </div>
            </div>
            </div>
            </div>
            <!-- Order Details Section -->
            <div class="order-details-section" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                <h3 style="font-size: 16px; margin-bottom: 10px; color: #333;">Order Details</h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                    <thead>
                        <tr style="background-color: #f2f2f2;">
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Product Name</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Quantity</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Price (INR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: left;">' . htmlspecialchars($order['product_name']) . '</td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: left;">' . htmlspecialchars($order['quantity']) . '</td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: left;">' . number_format($order['product_price'], 2) . '</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Delivery Instructions -->
            <div class="mb-3 p-2 bg-light rounded small">
                <div class="instruction-title">Delivery Instructions:</div>
                <div class="instruction-content">Please handle with care. Contact recipient before delivery.</div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                Thank you for choosing WaryChary Care!<br>
                For customer support: support@warychary.com
            </div>
        </div>
    </body>
    </html>
    ';

    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream('shipping_label_' . $order['id'] . '.pdf', array('Attachment' => 0));
    exit();
    } catch (Exception $e) {
        error_log("DomPDF Error in generateReceiptPDF: " . $e->getMessage());
        throw $e; // Re-throw to be caught by the main try-catch block
    }
}

?>