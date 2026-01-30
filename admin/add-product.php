<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../database/config.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if products table exists, if not create it
$query = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    product_image VARCHAR(255) NOT NULL,
    product_description TEXT NOT NULL,
    purchase_price DECIMAL(10, 2) NOT NULL,
    sales_price DECIMAL(10, 2) NOT NULL,
    mrp DECIMAL(10, 2) NOT NULL,
    offer_product_name VARCHAR(255) NULL,
    offer_product_image VARCHAR(255) NULL,
    offer_product_purchase_price DECIMAL(10, 2) NULL,
    offer_product_sales_price DECIMAL(10, 2) NULL,
    offer_product_mrp DECIMAL(10, 2) NULL,
    delivery_cost DECIMAL(10, 2) NOT NULL,
    packing_cost DECIMAL(10, 2) NOT NULL,
    total_expense DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

try {
    $db->exec($query);
} catch(PDOException $e) {
    echo "Table Creation Error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Main product data
        $product_name = $_POST['product_name'];
        $purchase_price = $_POST['purchase_price'];
        $sales_price = $_POST['sales_price'];
        $mrp = $_POST['mrp'];
        $product_description = $_POST['product_description'];
        
        // Offer product data
        $offer_product_name = $_POST['offer_product_name'] ?? null;
        $offer_product_purchase_price = $_POST['offer_product_purchase_price'] ?? 0;
        $offer_product_sales_price = $_POST['offer_product_sales_price'] ?? 0;
        $offer_product_mrp = $_POST['offer_product_mrp'] ?? 0;
        
        // Costs
        $delivery_cost = $_POST['delivery_cost'];
        $packing_cost = $_POST['packing_cost'];
        
        // Calculate total expense
        $total_expense = $purchase_price + $offer_product_purchase_price + $delivery_cost + $packing_cost;
        
        // Handle main product image upload
        $product_image = '';
        if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'webp');
            $filename = $_FILES['product_image']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if(in_array(strtolower($ext), $allowed)) {
                $new_filename = 'product_' . uniqid() . '.' . $ext;
                $upload_path = '../images/products/';
                
                // Create directory if it doesn't exist
                if(!file_exists($upload_path)) {
                    mkdir($upload_path, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path . $new_filename)) {
                    $product_image = $new_filename;
                }
            }
        }
        
        // Handle offer product image upload
        $offer_product_image = '';
        if(isset($_FILES['offer_product_image']) && $_FILES['offer_product_image']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'webp');
            $filename = $_FILES['offer_product_image']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if(in_array(strtolower($ext), $allowed)) {
                $new_filename = 'offer_' . uniqid() . '.' . $ext;
                $upload_path = '../images/products/';
                
                // Create directory if it doesn't exist
                if(!file_exists($upload_path)) {
                    mkdir($upload_path, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['offer_product_image']['tmp_name'], $upload_path . $new_filename)) {
                    $offer_product_image = $new_filename;
                }
            }
        }
        
        // Insert product into database
        $query = "INSERT INTO products (
            product_name, product_image, product_description, purchase_price, sales_price, mrp,
            offer_product_name, offer_product_image, offer_product_purchase_price, offer_product_sales_price, offer_product_mrp,
            delivery_cost, packing_cost, total_expense
        ) VALUES (
            :product_name, :product_image, :product_description, :purchase_price, :sales_price, :mrp,
            :offer_product_name, :offer_product_image, :offer_product_purchase_price, :offer_product_sales_price, :offer_product_mrp,
            :delivery_cost, :packing_cost, :total_expense
        )";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':product_image', $product_image);
        $stmt->bindParam(':product_description', $product_description);
        $stmt->bindParam(':purchase_price', $purchase_price);
        $stmt->bindParam(':sales_price', $sales_price);
        $stmt->bindParam(':mrp', $mrp);
        $stmt->bindParam(':offer_product_name', $offer_product_name);
        $stmt->bindParam(':offer_product_image', $offer_product_image);
        $stmt->bindParam(':offer_product_purchase_price', $offer_product_purchase_price);
        $stmt->bindParam(':offer_product_sales_price', $offer_product_sales_price);
        $stmt->bindParam(':offer_product_mrp', $offer_product_mrp);
        $stmt->bindParam(':delivery_cost', $delivery_cost);
        $stmt->bindParam(':packing_cost', $packing_cost);
        $stmt->bindParam(':total_expense', $total_expense);
        
        if($stmt->execute()) {
            $success_message = "Product added successfully!";
        } else {
            $error_message = "Failed to add product.";
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
        .image-preview {
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            margin-top: 10px;
            display: none;
            background-size: cover;
            background-position: center;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-section h4 {
            margin-bottom: 15px;
            color: #495057;
        }
        .total-expense-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php
    // Include sidebar
    include 'sidebar.php';
    ?>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-toggle" class="btn btn-primary d-md-none">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="page-title">Add New Product</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Add Product</li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- Main Product Section -->
                        <div class="form-section">
                            <h4>Main Product Details</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="product_image" class="form-label">Product Image <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*" required onchange="previewImage(this, 'product_image_preview')">
                                    <div id="product_image_preview" class="image-preview"></div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="product_description" class="form-label">Product Description <span class="text-danger">*</span></label>
                                    <textarea id="product_description" name="product_description" class="form-control summernote" required></textarea>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="purchase_price" class="form-label">Purchase Price (₹) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" required onchange="calculateTotal()">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="sales_price" class="form-label">Sales Price (₹) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="sales_price" name="sales_price" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="mrp" class="form-label">MRP (₹) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="mrp" name="mrp" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Free Offer Product Section -->
                        <div class="form-section">
                            <h4>Free Offer Product Details (Optional)</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="offer_product_name" class="form-label">Offer Product Name</label>
                                    <input type="text" class="form-control" id="offer_product_name" name="offer_product_name">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="offer_product_image" class="form-label">Offer Product Image</label>
                                    <input type="file" class="form-control" id="offer_product_image" name="offer_product_image" accept="image/*" onchange="previewImage(this, 'offer_product_image_preview')">
                                    <div id="offer_product_image_preview" class="image-preview"></div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="offer_product_purchase_price" class="form-label">Purchase Price (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="offer_product_purchase_price" name="offer_product_purchase_price" value="0" onchange="calculateTotal()">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="offer_product_sales_price" class="form-label">Sales Price (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="offer_product_sales_price" name="offer_product_sales_price" value="0">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="offer_product_mrp" class="form-label">MRP (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="offer_product_mrp" name="offer_product_mrp" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Costs Section -->
                        <div class="form-section">
                            <h4>Additional Costs</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="delivery_cost" class="form-label">Delivery Cost (₹) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="delivery_cost" name="delivery_cost" required value="0" onchange="calculateTotal()">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="packing_cost" class="form-label">Packing Cost (₹) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="packing_cost" name="packing_cost" required value="0" onchange="calculateTotal()">
                                </div>
                            </div>
                            
                            <!-- Total Expense Box -->
                            <div class="total-expense-box">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Total Expense Calculation</h5>
                                        <p class="text-muted">Main Product Purchase Price + Offer Product Purchase Price + Delivery Cost + Packing Cost</p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <h4>Total: ₹<span id="total_expense_display">0.00</span></h4>
                                        <input type="hidden" id="total_expense" name="total_expense" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                            <button type="submit" class="btn btn-primary">Add Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Summernote JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    
    <script>
        // Initialize Summernote
        $(document).ready(function() {
            $('.summernote').summernote({
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            
            // Mobile menu toggle
            $('#mobile-menu-toggle, .sidebar-overlay').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.sidebar-overlay').toggleClass('active');
            });
            
            $('#close-sidebar').on('click', function() {
                $('#sidebar').removeClass('active');
                $('.sidebar-overlay').removeClass('active');
            });
        });
        
        // Image preview function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.style.backgroundImage = `url('${e.target.result}')`;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Calculate total expense
        function calculateTotal() {
            const purchasePrice = parseFloat(document.getElementById('purchase_price').value) || 0;
            const offerPurchasePrice = parseFloat(document.getElementById('offer_product_purchase_price').value) || 0;
            const deliveryCost = parseFloat(document.getElementById('delivery_cost').value) || 0;
            const packingCost = parseFloat(document.getElementById('packing_cost').value) || 0;
            
            const total = purchasePrice + offerPurchasePrice + deliveryCost + packingCost;
            
            document.getElementById('total_expense_display').textContent = total.toFixed(2);
            document.getElementById('total_expense').value = total.toFixed(2);
        }
    </script>
</body>
</html>