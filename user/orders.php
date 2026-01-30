<?php
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get user details
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user orders with product information
$orderStmt = $conn->prepare("
    SELECT o.*, p.product_image 
    FROM orders o 
    LEFT JOIN products p ON o.product_id = p.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$orderStmt->execute([$userId]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Format the name for display
$fullName = $user ? htmlspecialchars($user['name'] ?? '') : '';

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'confirmed':
            return 'bg-info';
        case 'processing':
            return 'bg-primary';
        case 'shipped':
            return 'bg-secondary';
        case 'delivered':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Function to get payment status badge class
function getPaymentStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'completed':
            return 'bg-success';
        case 'failed':
            return 'bg-danger';
        case 'refunded':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - WaryChary Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon">
    <style>
        :root {
            --primary-color: #8B5CF6;
            --header-bg: #fff;
            --sidebar-bg: #fff;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --card-shadow: 0 2px 10px rgba(0,0,0,0.05);
            --border-radius: 10px;
            --sidebar-width: 250px;
        }
        
        body {
            background: var(--light-bg);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .mobile-menu-toggle:hover {
            background: #7C3AED;
            transform: translateY(-1px);
        }

        .mobile-menu-toggle.active {
            background: #7C3AED;
        }
        
        .page-container {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .sidebar {
            background: var(--sidebar-bg);
            border-right: 1px solid #eee;
            padding: 1rem;
            width: var(--sidebar-width);
            height: 100%;
            overflow-y: auto;
            position: sticky;
            top: 0;
            transition: transform 0.3s ease;
            z-index: 1020;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 60px - 50px);
            transition: margin-left 0.3s ease;
        }
        
        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1010;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
        }
        
        footer {
            background: var(--header-bg);
            padding: 15px 0;
            text-align: center;
            border-top: 1px solid #eee;
            position: sticky;
            bottom: 0;
            width: 100%;
            z-index: 1000;
        }

        .orders-header {
            margin-bottom: 20px;
        }

        .orders-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .order-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--primary-color);
        }

        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .order-id {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr 200px;
            gap: 20px;
            margin-bottom: 15px;
        }

        .product-image-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 10px;
        }

        .product-image {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #eee;
            background: #f8f9fa;
            padding: 10px;
        }

        .no-image {
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 8px;
            color: #666;
            font-size: 14px;
            text-align: center;
        }

        .detail-item {
            margin-bottom: 10px;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .detail-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .order-status-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
            flex-wrap: wrap;
        }

        .status-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .total-amount {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .no-orders {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            text-align: center;
            box-shadow: var(--card-shadow);
        }

        .no-orders i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .no-orders h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .no-orders p {
            color: #999;
            margin-bottom: 20px;
        }

        .tracking-number {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            font-weight: 600;
            color: var(--primary-color);
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            body {
                height: 100%;
                position: relative;
            }
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                width: 80%;
                max-width: 280px;
                transform: translateX(-100%);
                z-index: 1020;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                height: auto;
                min-height: 100vh;
                padding: 15px;
                -webkit-overflow-scrolling: touch;
            }
            .order-details {
                grid-template-columns: 1fr;
            }
            .product-image-container {
                order: -1;
                margin-bottom: 15px;
            }
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-status-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header removed -->
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="orders-header">
                <h1><i class="fas fa-shopping-bag me-2"></i>My Orders</h1>
                <p class="text-muted">Track and manage your orders</p>
            </div>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="../index.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<?php echo htmlspecialchars($order['id']); ?></div>
                                <div class="order-date">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div>
                                <div class="detail-item">
                                    <div class="detail-label">Product</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($order['product_name']); ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Quantity</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($order['quantity']); ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Unit Price</div>
                                    <div class="detail-value">₹<?php echo number_format($order['product_price'], 2); ?></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="detail-item">
                                    <div class="detail-label">Delivery Address</div>
                                    <div class="detail-value">
                                        <?php echo htmlspecialchars($order['guest_name']); ?><br>
                                        <?php echo htmlspecialchars($order['guest_full_address']); ?><br>
                                        <?php echo htmlspecialchars($order['guest_district'] . ', ' . $order['guest_state'] . ' - ' . $order['guest_pincode']); ?>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Contact</div>
                                    <div class="detail-value">
                                        <?php echo htmlspecialchars($order['guest_phone']); ?><br>
                                        <?php echo htmlspecialchars($order['guest_email']); ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($order['tracking_number']) || !empty($order['courier_name'])): ?>
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-shipping-fast me-1"></i>Shipping Information
                                    </div>
                                    <div class="detail-value">
                                        <?php if (!empty($order['courier_name'])): ?>
                                            <strong>Courier:</strong> <?php echo htmlspecialchars($order['courier_name']); ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($order['tracking_number'])): ?>
                                            <strong>Tracking:</strong> 
                                            <span class="tracking-number"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                            <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyTracking('<?php echo htmlspecialchars($order['tracking_number']); ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-image-container">
                                <?php if (!empty($order['product_image'])): ?>
                                    <img src="../images/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($order['product_name']); ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i><br>
                                        No Image
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['razorpay_order_id'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">Payment ID</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['razorpay_order_id']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="order-status-section">
                            <div class="status-badges">
                                <span class="badge <?php echo getStatusBadgeClass($order['order_status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                                </span>
                                <span class="badge <?php echo getPaymentStatusBadgeClass($order['payment_status']); ?>">
                                    Payment: <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                </span>
                            </div>
                            
                            <div class="total-amount">
                                Total: ₹<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarLinks = document.querySelectorAll('.sidebar a');

        // Toggle sidebar
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            this.classList.toggle('active');
        });

        // Close sidebar when clicking overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
        });

        // Close sidebar when clicking a link (mobile only)
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            }
        });
        
        function copyTracking(trackingNumber) {
            navigator.clipboard.writeText(trackingNumber).then(function() {
                // Show success message
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-success');
                
                setTimeout(function() {
                    button.innerHTML = originalHTML;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                alert('Failed to copy tracking number');
            });
        }
    </script>
</body>
</html>