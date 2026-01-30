<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'database/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Razorpay\Api\Api;

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create orders table if it doesn't exist
$create_orders_table = "
CREATE TABLE IF NOT EXISTS orders (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100) NOT NULL,
    guest_phone VARCHAR(20) NOT NULL,
    guest_state VARCHAR(50) NOT NULL,
    guest_district VARCHAR(50) NULL,
    guest_pincode VARCHAR(10) NOT NULL,
    guest_full_address TEXT NOT NULL,
    product_id INT(11) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT(11) DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_id VARCHAR(255) NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    razorpay_order_id VARCHAR(255) NULL,
    razorpay_payment_id VARCHAR(255) NULL,
    razorpay_signature VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);";
$db->exec($create_orders_table);

$success_message = '';
$error_message = '';
$order_created = false;
$razorpay_order = null;

// Function to calculate and store partner earnings
function calculateAndStoreEarnings($db, $order_details) {
    try {
        // Get user's referral information (only referred_by_partner now)
        $stmt = $db->prepare("SELECT referred_by_partner FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $order_details['user_id']]);
        $user_referral = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_referral || !$user_referral['referred_by_partner']) {
            return; // User not found or not referred by anyone
        }
        
        $order_amount = $order_details['total_amount'];
        $partner_id = $user_referral['referred_by_partner'];
        
        // Get partner's earning percentage and senior partner info
        $stmt = $db->prepare("SELECT id, earning, referred_by_senior_partner FROM partners WHERE id = :partner_id");
        $stmt->execute([':partner_id' => $partner_id]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$partner) {
            return; // Partner not found
        }
        
        // Calculate and store partner earnings
        if ($partner['earning'] > 0) {
            $partner_earning_amount = ($order_amount * $partner['earning']) / 100;
            
            // Store partner earning history
            $stmt = $db->prepare("INSERT INTO partner_earnings (partner_id, order_id, user_id, earning_amount, earning_percentage, order_amount) VALUES (:partner_id, :order_id, :user_id, :earning_amount, :earning_percentage, :order_amount)");
            $stmt->execute([
                ':partner_id' => $partner['id'],
                ':order_id' => $order_details['id'],
                ':user_id' => $order_details['user_id'],
                ':earning_amount' => $partner_earning_amount,
                ':earning_percentage' => $partner['earning'],
                ':order_amount' => $order_amount
            ]);
            
            // Update partner's total earnings
            $stmt = $db->prepare("UPDATE partners SET total_earnings = total_earnings + :earning_amount WHERE id = :partner_id");
            $stmt->execute([
                ':earning_amount' => $partner_earning_amount,
                ':partner_id' => $partner['id']
            ]);
            

        }
        
        // Calculate senior partner earnings if this partner was referred by a senior partner
        if ($partner['referred_by_senior_partner']) {
            // Get senior partner's earning percentage
            $stmt = $db->prepare("SELECT id, earning FROM senior_partners WHERE id = :senior_partner_id");
            $stmt->execute([':senior_partner_id' => $partner['referred_by_senior_partner']]);
            $senior_partner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($senior_partner && $senior_partner['earning'] > 0) {
                $senior_partner_earning_amount = ($order_amount * $senior_partner['earning']) / 100;
                
                // Store senior partner earning history
                $stmt = $db->prepare("INSERT INTO senior_partner_earnings (senior_partner_id, partner_id, order_id, user_id, earning_amount, earning_percentage, order_amount) VALUES (:senior_partner_id, :partner_id, :order_id, :user_id, :earning_amount, :earning_percentage, :order_amount)");
                $stmt->execute([
                    ':senior_partner_id' => $senior_partner['id'],
                    ':partner_id' => $partner['id'],
                    ':order_id' => $order_details['id'],
                    ':user_id' => $order_details['user_id'],
                    ':earning_amount' => $senior_partner_earning_amount,
                    ':earning_percentage' => $senior_partner['earning'],
                    ':order_amount' => $order_amount
                ]);
                
                // Update senior partner's total earnings
                $stmt = $db->prepare("UPDATE senior_partners SET total_earnings = total_earnings + :earning_amount WHERE id = :senior_partner_id");
                $stmt->execute([
                    ':earning_amount' => $senior_partner_earning_amount,
                    ':senior_partner_id' => $senior_partner['id']
                ]);
                

            }
        }
        
    } catch (PDOException $e) {
        error_log("Error calculating earnings: " . $e->getMessage());
    }
}

// Function to send order confirmation email
function sendOrderConfirmationEmail($toEmail, $toName, $orderDetails, $smtpSettings) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = $smtpSettings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpSettings['smtp_username'];
        $mail->Password = $smtpSettings['smtp_password'];
        $mail->SMTPSecure = $smtpSettings['smtp_encryption'];
        $mail->Port = $smtpSettings['smtp_port'];

        // Recipients
        $mail->setFrom($smtpSettings['smtp_from_email'], $smtpSettings['smtp_from_name']);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation - WaryCharyCare';
        $mail->Body = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="logo/warycharycare.png" alt="WaryCharyCare Logo" style="max-width: 150px;">
                        </div>
                        <h2 style="color: #7c43b9; text-align: center;">Order Confirmation</h2>
                        <p>Dear ' . htmlspecialchars($toName) . ',</p>
                        <p>Thank you for your order! We have received your order and it is being processed.</p>
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="color: #7c43b9; margin-top: 0;">Order Details:</h3>
                            <p><strong>Order ID:</strong> #' . $orderDetails['id'] . '</p>
                            <p><strong>Product:</strong> ' . htmlspecialchars($orderDetails['product_name']) . '</p>
                            <p><strong>Quantity:</strong> ' . $orderDetails['quantity'] . '</p>
                            <p><strong>Total Amount:</strong> ₹' . number_format($orderDetails['total_amount'], 2) . '</p>
                            <p><strong>Order Status:</strong> ' . ucfirst($orderDetails['order_status']) . '</p>
                        </div>
                        <div style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="color: #7c43b9; margin-top: 0;">Shipping Address:</h3>
                            <p>' . htmlspecialchars($orderDetails['guest_full_address']) . '<br>
                            ' . htmlspecialchars($orderDetails['guest_state']) . ' - ' . htmlspecialchars($orderDetails['guest_pincode']) . '</p>
                        </div>
                        <p>We will send you another email with tracking information once your order has been shipped.</p>
                        <p>If you have any questions about your order, please contact our customer service team.</p>
                        <p style="text-align: center; margin-top: 30px; font-size: 0.9em; color: #777;">
                            Best regards,<br>
                            The WaryCharyCare Team
                        </p>
                        <div style="text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; font-size: 0.8em; color: #aaa;">
                            &copy; ' . date('Y') . ' WaryCharyCare. All rights reserved.
                        </div>
                    </div>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Order confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['place_order'])) {
        // Get form data
        $guest_name = trim($_POST['guest_name']);
        $guest_email = trim($_POST['guest_email']);
        $guest_phone = trim($_POST['guest_phone']);
        $guest_state = trim($_POST['guest_state']);
        $guest_district = trim($_POST['guest_district'] ?? '');
        $guest_pincode = trim($_POST['guest_pincode']);
        $guest_full_address = trim($_POST['guest_full_address']);
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']) ?: 1;

        // Basic validation
        if (empty($guest_name) || empty($guest_email) || empty($guest_phone) || empty($guest_state) || empty($guest_pincode) || empty($guest_full_address) || empty($product_id)) {
            $error_message = "Please fill in all required fields.";
        } elseif (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } elseif (strlen($guest_phone) < 10) {
            $error_message = "Please enter a valid phone number.";
        } elseif (strlen($guest_pincode) !== 6 || !is_numeric($guest_pincode)) {
            $error_message = "Please enter a valid 6-digit pincode.";
        } else {
            try {
                // Get product details
                $stmt = $db->prepare("SELECT * FROM products WHERE id = :product_id");
                $stmt->execute([':product_id' => $product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    $error_message = "Product not found.";
                } else {
                    // Calculate total amount
                    $product_price = $product['sales_price'];
                    $total_amount = $product_price * $quantity;

                    // Get user_id if logged in
                    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

                    // Create order in database
                    $stmt = $db->prepare("INSERT INTO orders (user_id, guest_name, guest_email, guest_phone, guest_state, guest_district, guest_pincode, guest_full_address, product_id, product_name, product_price, quantity, total_amount) VALUES (:user_id, :guest_name, :guest_email, :guest_phone, :guest_state, :guest_district, :guest_pincode, :guest_full_address, :product_id, :product_name, :product_price, :quantity, :total_amount)");
                    
                    $result = $stmt->execute([
                        ':user_id' => $user_id,
                        ':guest_name' => $guest_name,
                        ':guest_email' => $guest_email,
                        ':guest_phone' => $guest_phone,
                        ':guest_state' => $guest_state,
                        ':guest_district' => $guest_district,
                        ':guest_pincode' => $guest_pincode,
                        ':guest_full_address' => $guest_full_address,
                        ':product_id' => $product_id,
                        ':product_name' => $product['product_name'],
                        ':product_price' => $product_price,
                        ':quantity' => $quantity,
                        ':total_amount' => $total_amount
                    ]);

                    if ($result) {
                        $order_id = $db->lastInsertId();
                        
                        // Get Razorpay settings
                        $stmt = $db->query("SELECT * FROM razorpay_settings WHERE is_active = 1 LIMIT 1");
                        $razorpay_settings = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($razorpay_settings) {
                            try {
                                $keyId = $razorpay_settings['razorpay_key_id'];
                                $keySecret = $razorpay_settings['razorpay_key_secret'];
                                $api = new Api($keyId, $keySecret);

                                // Create Razorpay order
                                $razorpay_order = $api->order->create([
                                    'receipt' => 'order_' . $order_id,
                                    'amount' => $total_amount * 100, // amount in paise
                                    'currency' => 'INR',
                                    'payment_capture' => 1
                                ]);

                                // Update order with Razorpay order ID
                                $stmt = $db->prepare("UPDATE orders SET razorpay_order_id = :razorpay_order_id WHERE id = :order_id");
                                $stmt->execute([
                                    ':razorpay_order_id' => $razorpay_order['id'],
                                    ':order_id' => $order_id
                                ]);

                                $order_created = true;
                                $success_message = "Order created successfully! Please complete the payment.";

                            } catch (Exception $e) {
                                $error_message = "Error creating payment order: " . $e->getMessage();
                            }
                        } else {
                            $error_message = "Payment gateway not configured. Please contact administrator.";
                        }
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
                error_log($e->getMessage());
            }
        }
    } elseif (isset($_POST['payment_success'])) {
        // Handle payment success callback
        $razorpay_payment_id = $_POST['razorpay_payment_id'];
        $razorpay_order_id = $_POST['razorpay_order_id'];
        $razorpay_signature = $_POST['razorpay_signature'];
        $order_id = $_POST['order_id'];

        try {
            // Update order with payment details
            $stmt = $db->prepare("UPDATE orders SET razorpay_payment_id = :payment_id, razorpay_signature = :signature, payment_status = 'completed', order_status = 'confirmed' WHERE id = :order_id");
            $stmt->execute([
                ':payment_id' => $razorpay_payment_id,
                ':signature' => $razorpay_signature,
                ':order_id' => $order_id
            ]);

            // Get order details for email
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :order_id");
            $stmt->execute([':order_id' => $order_id]);
            $order_details = $stmt->fetch(PDO::FETCH_ASSOC);

            // Send confirmation email
            $stmt = $db->query("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
            $smtp_settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($smtp_settings && $order_details) {
                sendOrderConfirmationEmail($order_details['guest_email'], $order_details['guest_name'], $order_details, $smtp_settings);
            }

            // Calculate and store partner earnings if user was referred
            if ($order_details && $order_details['user_id']) {
                calculateAndStoreEarnings($db, $order_details);
            }

            $success_message = "Payment successful! Order confirmed. You will receive a confirmation email shortly.";
            
        } catch (PDOException $e) {
            $error_message = "Error updating payment status: " . $e->getMessage();
        }
    }
}

// Get specific product if product_id is provided
$selected_product = null;
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;

if ($product_id) {
    try {
        $stmt = $db->prepare("SELECT id, product_name, sales_price, product_image, product_description, offer_product_name FROM products WHERE id = :product_id");
        $stmt->execute([':product_id' => $product_id]);
        $selected_product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$selected_product) {
            $error_message = "Product not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching product: " . $e->getMessage();
    }
} else {
    $error_message = "No product selected. Please select a product to checkout.";
}

// Get user details if logged in
$user_details = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("SELECT name, email, phone, state, district, pincode, full_address FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_details = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user details: " . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - WaryCharyCare</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin: 40px auto;
        }
        .checkout-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .form-header {
            background: linear-gradient(45deg, #6f42c1, #8e44ad);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .form-body {
            padding: 2rem;
        }
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #6f42c1, #8e44ad);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #8e44ad, #6f42c1);
        }
        .product-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .product-card:hover {
            border-color: #6f42c1;
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.2);
        }
        .product-card.selected {
            border-color: #6f42c1;
            background-color: #f8f9ff;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .order-summary {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkout-form">
            <div class="form-header">
                <h2><i class="bi bi-cart-check me-2"></i>Checkout</h2>
                <p class="mb-0">Complete your order details</p>
            </div>
            <div class="form-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$order_created): ?>
                <form method="POST" id="checkoutForm">
                    <div class="row">
                        <!-- Customer Details -->
                        <div class="col-md-6">
                            <h4 class="mb-3"><i class="bi bi-person me-2"></i>Customer Details</h4>
                            
                            <div class="mb-3">
                                <label for="guest_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="guest_name" name="guest_name" value="<?php echo $user_details ? htmlspecialchars($user_details['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="guest_email" name="guest_email" value="<?php echo $user_details ? htmlspecialchars($user_details['email']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="guest_phone" name="guest_phone" value="<?php echo $user_details ? htmlspecialchars($user_details['phone']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_state" class="form-label">State *</label>
                                <input type="text" class="form-control" id="guest_state" name="guest_state" value="<?php echo $user_details ? htmlspecialchars($user_details['state']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_district" class="form-label">District</label>
                                <input type="text" class="form-control" id="guest_district" name="guest_district" value="<?php echo $user_details ? htmlspecialchars($user_details['district']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_pincode" class="form-label">Pincode *</label>
                                <input type="text" class="form-control" id="guest_pincode" name="guest_pincode" value="<?php echo $user_details ? htmlspecialchars($user_details['pincode']) : ''; ?>" maxlength="6" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_full_address" class="form-label">Full Address *</label>
                                <textarea class="form-control" id="guest_full_address" name="guest_full_address" rows="3" required><?php echo $user_details ? htmlspecialchars($user_details['full_address']) : ''; ?></textarea>
                            </div>
                        </div>

                        <!-- Selected Product Display -->
                        <div class="col-md-6">
                            <h4 class="mb-3"><i class="bi bi-box me-2"></i>Selected Product</h4>
                            
                            <?php if ($selected_product && !$error_message): ?>
                                <div class="product-card selected">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <?php if ($selected_product['product_image']): ?>
                                                <img src="images/products/<?php echo $selected_product['product_image']; ?>" alt="<?php echo htmlspecialchars($selected_product['product_name']); ?>" class="product-image w-100" style="height: 120px; object-fit: cover; border-radius: 10px;">
                                            <?php else: ?>
                                                <div class="product-image bg-light d-flex align-items-center justify-content-center" style="height: 120px; border-radius: 10px;">
                                                    <i class="bi bi-image text-muted fs-2"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-8">
                                            <h5 class="mb-2"><?php echo htmlspecialchars($selected_product['product_name']); ?></h5>
                                            <p class="text-muted small mb-2"><?php echo htmlspecialchars(substr(strip_tags($selected_product['product_description']), 0, 100)); ?>...</p>
                                            <?php if (!empty($selected_product['offer_product_name'])): ?>
                                                <div class="badge bg-success mb-2">
                                                    <i class="bi bi-gift me-1"></i>Free Gift: <?php echo htmlspecialchars($selected_product['offer_product_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="fs-4 fw-bold text-primary">₹<?php echo number_format($selected_product['sales_price'], 2); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 mt-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="10" onchange="updateTotal()">
                                </div>

                                <div class="order-summary">
                                    <h5>Order Summary</h5>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo htmlspecialchars($selected_product['product_name']); ?></span>
                                        <span>₹<?php echo number_format($selected_product['sales_price'], 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Quantity:</span>
                                        <span id="display-quantity">1</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total: <span id="total-amount">₹<?php echo number_format($selected_product['sales_price'], 2); ?></span></strong>
                                    </div>
                                </div>

                                <input type="hidden" name="product_id" value="<?php echo $selected_product['id']; ?>">
                                <button type="submit" name="place_order" class="btn btn-primary w-100 mt-3">
                                    <i class="bi bi-credit-card me-2"></i>Place Order & Pay
                                </button>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>No product selected. Please go back and select a product.
                                </div>
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Products
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

                <?php if ($order_created && $razorpay_order): ?>
                    <div class="text-center">
                        <h4>Order Created Successfully!</h4>
                        <p>Please complete your payment to confirm the order.</p>
                        <button id="rzp-button" class="btn btn-success btn-lg">
                            <i class="bi bi-credit-card me-2"></i>Pay ₹<?php echo number_format($razorpay_order['amount']/100, 2); ?>
                        </button>
                    </div>

                    <!-- Hidden form for payment success -->
                    <form id="payment-form" method="POST" style="display: none;">
                        <input type="hidden" name="payment_success" value="1">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="<?php echo $razorpay_order['id']; ?>">
                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($order_created && $razorpay_order && isset($razorpay_settings)): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        var options = {
            "key": "<?php echo $razorpay_settings['razorpay_key_id']; ?>",
            "amount": "<?php echo $razorpay_order['amount']; ?>",
            "currency": "<?php echo $razorpay_order['currency']; ?>",
            "name": "WaryCharyCare",
            "description": "Product Purchase",
            "order_id": "<?php echo $razorpay_order['id']; ?>",
            "handler": function (response){
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                document.getElementById('razorpay_signature').value = response.razorpay_signature;
                document.getElementById('payment-form').submit();
            },
            "prefill": {
                "name": "<?php echo htmlspecialchars($_POST['guest_name'] ?? ''); ?>",
                "email": "<?php echo htmlspecialchars($_POST['guest_email'] ?? ''); ?>",
                "contact": "<?php echo htmlspecialchars($_POST['guest_phone'] ?? ''); ?>"
            },
            "theme": {
                "color": "#6f42c1"
            }
        };
        
        var rzp = new Razorpay(options);
        document.getElementById('rzp-button').onclick = function(e){
            rzp.open();
            e.preventDefault();
        }
    </script>
    <?php endif; ?>

    <script>
        <?php if ($selected_product): ?>
        const selectedProductPrice = <?php echo $selected_product['sales_price']; ?>;
        
        function updateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const total = selectedProductPrice * quantity;
            document.getElementById('total-amount').textContent = `₹${total.toFixed(2)}`;
            document.getElementById('display-quantity').textContent = quantity;
        }
        <?php endif; ?>

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('guest_phone').value;
            const pincode = document.getElementById('guest_pincode').value;
            
            if (phone.length < 10) {
                e.preventDefault();
                alert('Please enter a valid phone number!');
                return;
            }

            if (pincode.length !== 6) {
                e.preventDefault();
                alert('Please enter a valid 6-digit pincode!');
                return;
            }
        });
    </script>
</body>
</html>