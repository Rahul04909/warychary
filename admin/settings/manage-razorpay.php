<?php
require_once '../../database/config.php';
require_once '../../vendor/autoload.php';

use Razorpay\Api\Api;

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Set default admin name
$admin_name = "Admin";

// Create razorpay_settings table if it doesn't exist
$create_table_query = "
CREATE TABLE IF NOT EXISTS razorpay_settings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    razorpay_key_id VARCHAR(255) NOT NULL,
    razorpay_key_secret VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";
$db->exec($create_table_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_razorpay'])) {
        try {
            $query = "INSERT INTO razorpay_settings 
                    (razorpay_key_id, razorpay_key_secret) 
                    VALUES (:key_id, :key_secret)
                    ON DUPLICATE KEY UPDATE 
                    razorpay_key_id = VALUES(razorpay_key_id),
                    razorpay_key_secret = VALUES(razorpay_key_secret)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                ':key_id' => $_POST['razorpay_key_id'],
                ':key_secret' => $_POST['razorpay_key_secret']
            ]);

            $success_message = "Razorpay settings saved successfully!";
        } catch(PDOException $e) {
            $error_message = "Error saving Razorpay settings: " . $e->getMessage();
        }
    } elseif (isset($_POST['test_razorpay'])) {
        try {
            $stmt = $db->query("SELECT * FROM razorpay_settings WHERE is_active = 1 LIMIT 1");
            $razorpay_settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($razorpay_settings) {
                $keyId = $razorpay_settings['razorpay_key_id'];
                $keySecret = $razorpay_settings['razorpay_key_secret'];
                $api = new Api($keyId, $keySecret);

                $order = $api->order->create(array(
                    'receipt' => 'test_payment_receipt',
                    'amount' => $_POST['test_amount'] * 100, // amount in paise
                    'currency' => 'INR',
                    'payment_capture' => 1 // auto capture
                ));

                $order_id = $order['id'];
                $amount = $order['amount'];
                $currency = $order['currency'];

                $success_message = "Test payment initiated successfully! Order ID: " . $order_id . ". Please complete the payment using the Razorpay checkout.";

            } else {
                $error_message = "No active Razorpay settings found!";
            }
        } catch (Exception $e) {
            $error_message = "Error initiating test payment: " . $e->getMessage();
        }
    }
}

// Get current Razorpay settings
try {
    $stmt = $db->query("SELECT * FROM razorpay_settings WHERE is_active = 1 LIMIT 1");
    $razorpay_settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching Razorpay settings: " . $e->getMessage();
}

// Include sidebar
include '../sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay Settings - Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/warycharycare/admin/assets/css/admin-style.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            border-radius: 15px;
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            background: linear-gradient(45deg, #6f42c1, #8e44ad) !important;
            padding: 1.2rem;
        }
        .card-header h5 {
            color: white !important;
            font-weight: 600;
            margin: 0;
        }
        .card-body {
            padding: 2rem;
        }
        .form-label {
            color: #6f42c1;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #6f42c1, #8e44ad);
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
        }
        .btn-info {
            background: linear-gradient(45deg, #17a2b8, #2980b9);
            border: none;
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.4);
        }
        .input-group .btn {
            border-radius: 0 10px 10px 0;
            padding: 0.7rem 1rem;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        hr {
            background: linear-gradient(to right, #6f42c1, #8e44ad);
            height: 2px;
            opacity: 0.1;
        }
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Menu Toggle -->
<button id="menu-toggle" class="btn btn-primary d-md-none position-fixed" style="top: 10px; left: 10px; z-index: 1030;">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay"></div>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Mobile Menu Toggle -->
        <button id="mobile-menu-toggle" class="btn btn-primary d-md-none mb-3">
            <i class="bi bi-list"></i>
        </button>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Razorpay Configuration</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="razorpay_key_id" class="form-label">Razorpay Key ID</label>
                                        <input type="text" class="form-control" id="razorpay_key_id" name="razorpay_key_id" 
                                               value="<?php echo $razorpay_settings['razorpay_key_id'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">Please provide Razorpay Key ID.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="razorpay_key_secret" class="form-label">Razorpay Key Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="razorpay_key_secret" name="razorpay_key_secret" 
                                                   value="<?php echo $razorpay_settings['razorpay_key_secret'] ?? ''; ?>" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Please provide Razorpay Key Secret.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" name="save_razorpay" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Settings
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <!-- Test Razorpay Section -->
                        <h5 class="mb-4">Test Razorpay Configuration</h5>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="test_amount" class="form-label">Test Amount (INR)</label>
                                        <input type="number" class="form-control" id="test_amount" name="test_amount" value="1" min="1" required>
                                        <div class="invalid-feedback">Please provide a valid amount for testing (minimum 1 INR).</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="test_razorpay" class="btn btn-info">
                                        <i class="bi bi-currency-rupee me-2"></i>Initiate Test Payment
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if (isset($order_id)): ?>
                            <div class="mt-4">
                                <button id="rzp-button1" class="btn btn-success">Complete Test Payment</button>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Password toggle
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('razorpay_key_secret');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (mobileMenuToggle && sidebar && overlay) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
    }
});
</script>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const closeButton = document.getElementById('close-sidebar');
    
    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
    }

    // Initialize dropdowns
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('data-bs-target'));
            if (target) {
                target.classList.toggle('show');
            }
        });
    });

// Razorpay Checkout Integration
<?php if (isset($order_id) && isset($razorpay_settings)): ?>
    var options = {
        "key": "<?php echo $razorpay_settings['razorpay_key_id']; ?>", // Enter the Key ID generated from the Dashboard
        "amount": "<?php echo $amount; ?>", // Amount is in currency subunits. Default currency is INR. Hence, 50000 means 50000 paise or ₹500.
        "currency": "<?php echo $currency; ?>",
        "name": "WaryCharyCare",
        "description": "Test Payment",
        "order_id": "<?php echo $order_id; ?>", //This is a sample Order ID. Pass the `id` obtained in the response of Step 1
        "handler": function (response){
            // You can handle the success response here, e.g., send to server for verification
            alert("Payment Successful! Payment ID: " + response.razorpay_payment_id);
            window.location.reload();
        },
        "prefill": {
            "name": "Admin User", // Replace with admin's name
            "email": "admin@example.com", // Replace with admin's email
            "contact": "9999999999" // Replace with admin's contact
        },
        "notes": {
            "address": "Admin Corporate Office"
        },
        "theme": {
            "color": "#6f42c1"
        }
    };
    var rzp1 = new Razorpay(options);
    document.getElementById('rzp-button1').onclick = function(e){
        rzp1.open();
        e.preventDefault();
    }
<?php endif; ?>

});
</script>
</body>
</html>