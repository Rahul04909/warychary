<?php
session_start();
require_once '../database/config.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();


// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $order_status = $_POST['order_status'];
    $tracking_number = $_POST['tracking_number'] ?? '';
    $courier_name = $_POST['courier_name'] ?? '';
    
    try {
        // Update order status and tracking information
        $update_query = "UPDATE orders SET order_status = ?, tracking_number = ?, courier_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$order_status, $tracking_number, $courier_name, $order_id]);
        
        $success_message = "Order status updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

// Fetch orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = [];
$params = [];

// Always filter for completed payment status
$where_conditions[] = "payment_status = 'completed'";

if (!empty($search)) {
    $where_conditions[] = "(guest_name LIKE ? OR guest_email LIKE ? OR product_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "order_status = ?";
    $params[] = $status_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

try {
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM orders $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_orders = $count_stmt->fetchColumn();
    $total_pages = ceil($total_orders / $per_page);
    
    // Fetch orders
    $query = "SELECT * FROM orders $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-style.css" rel="stylesheet">
    <style>
        .order-card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            border-radius: 15px;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
        }
        
        .order-header {
            background: linear-gradient(45deg, #6f42c1, #8e44ad);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .order-id {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .order-date {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-processing {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-dispatched {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .order-details {
            padding: 1.5rem;
        }
        
        .customer-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .product-info {
            background-color: #fff;
            border: 1px solid #e9ecef;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .update-form {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-update {
            background: linear-gradient(45deg, #6f42c1, #8e44ad);
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-update:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
            color: white;
        }
        
        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }
        
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        
        .page-link {
            color: #6f42c1;
            border: 1px solid #6f42c1;
            margin: 0 2px;
            border-radius: 8px;
        }
        
        .page-link:hover {
            background-color: #6f42c1;
            color: white;
        }
        
        .page-item.active .page-link {
            background-color: #6f42c1;
            border-color: #6f42c1;
        }
        
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .order-details {
                padding: 1rem;
            }
            
            .customer-info, .product-info {
                padding: 0.8rem;
            }
            
            .update-form {
                padding: 1rem;
            }
            
            .search-filters {
                padding: 1rem;
            }
            
            .btn-update {
                width: 100%;
                margin-top: 0.5rem;
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
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Manage Orders</h1>
                <div class="text-muted">
                    Total Orders: <?php echo $total_orders; ?>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Orders</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by customer name, email, or product..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="dispatched" <?php echo $status_filter == 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                            <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-update d-block w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="manage-orders.php" class="btn btn-outline-secondary d-block w-100">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Orders List -->
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-cart-x" style="font-size: 4rem; color: #6c757d;"></i>
                    <h4 class="mt-3 text-muted">No orders found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
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
                                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['guest_name']); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['guest_email']); ?></p>
                                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['guest_phone']); ?></p>
                                        <p class="mb-0"><strong>Address:</strong> <?php echo htmlspecialchars($order['guest_full_address']); ?></p>
                                        <p class="mb-0"><strong>State:</strong> <?php echo htmlspecialchars($order['guest_state']); ?>, 
                                        <strong>District:</strong> <?php echo htmlspecialchars($order['guest_district']); ?>, 
                                        <strong>PIN:</strong> <?php echo htmlspecialchars($order['guest_pincode']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="product-info">
                                        <h6 class="mb-2"><i class="bi bi-box-seam"></i> Product Information</h6>
                                        <p class="mb-1"><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
                                        <p class="mb-1"><strong>Price:</strong> ₹<?php echo number_format($order['product_price'], 2); ?></p>
                                        <p class="mb-1"><strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                                        <p class="mb-1"><strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                        <p class="mb-0"><strong>Payment Status:</strong> 
                                            <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Update Form -->
                            <div class="update-form">
                                <h6 class="mb-3"><i class="bi bi-pencil-square"></i> Update Order Status</h6>
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    
                                    <div class="col-md-3">
                                        <label for="order_status_<?php echo $order['id']; ?>" class="form-label">Order Status</label>
                                        <select class="form-select" name="order_status" id="order_status_<?php echo $order['id']; ?>" required>
                                            <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $order['order_status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="dispatched" <?php echo $order['order_status'] == 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                            <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="tracking_number_<?php echo $order['id']; ?>" class="form-label">Tracking Number</label>
                                        <input type="text" class="form-control" name="tracking_number" 
                                               id="tracking_number_<?php echo $order['id']; ?>"
                                               value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>"
                                               placeholder="Enter tracking number">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="courier_name_<?php echo $order['id']; ?>" class="form-label">Courier Name</label>
                                        <input type="text" class="form-control" name="courier_name" 
                                               id="courier_name_<?php echo $order['id']; ?>"
                                               value="<?php echo htmlspecialchars($order['courier_name'] ?? ''); ?>"
                                               placeholder="Enter courier name">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" name="update_order" class="btn btn-update d-block w-100">
                                            <i class="bi bi-check-circle"></i> Update Order
                                        </button>
                                    </div>
                                </form>
                                
                                <!-- Print Receipt Button -->
                                <div class="mt-3">
                                    <a href="print-receipt.php?order_id=<?php echo $order['id']; ?>&preview=true" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i> Preview Receipt
                                    </a>
                                    <a href="print-receipt.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary ms-2">
                                        <i class="bi bi-printer"></i> Print Receipt
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Orders pagination">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/admin-script.js"></script>
</body>
</html>