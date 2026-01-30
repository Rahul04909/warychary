<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['senior_partner_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../database/config.php';

$db = new Database();
$conn = $db->getConnection();

$senior_partner_id = $_SESSION['senior_partner_id'];
$senior_partner_name = $_SESSION['senior_partner_name'];

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_name = trim($_POST['bank_name']);
    $account_number = trim($_POST['account_number']);
    $ifsc_code = strtoupper(trim($_POST['ifsc_code']));
    $account_holder_name = trim($_POST['account_holder_name']);
    
    // Validation
    if (empty($bank_name) || empty($account_number) || empty($ifsc_code) || empty($account_holder_name)) {
        $error_message = "All fields are required.";
    } elseif (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc_code)) {
        $error_message = "Please enter a valid IFSC code.";
    } elseif (!preg_match('/^[0-9]{9,18}$/', $account_number)) {
        $error_message = "Please enter a valid account number (9-18 digits).";
    } else {
        try {
            // Check if bank details already exist
            $check_stmt = $conn->prepare("SELECT id FROM bank_details WHERE partner_id = ?");
            $check_stmt->execute([$senior_partner_id]);
            $existing_record = $check_stmt->fetch();
            
            if ($existing_record) {
                // Update existing record
                $update_stmt = $conn->prepare("UPDATE bank_details SET bank_name = ?, account_number = ?, ifsc_code = ?, account_holder_name = ?, updated_at = CURRENT_TIMESTAMP WHERE partner_id = ?");
                $update_stmt->execute([$bank_name, $account_number, $ifsc_code, $account_holder_name, $senior_partner_id]);
                $success_message = "Bank details updated successfully!";
            } else {
                // Insert new record
                $insert_stmt = $conn->prepare("INSERT INTO bank_details (partner_id, bank_name, account_number, ifsc_code, account_holder_name) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$senior_partner_id, $bank_name, $account_number, $ifsc_code, $account_holder_name]);
                $success_message = "Bank details saved successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error saving bank details. Please try again.";
            error_log("Bank details error: " . $e->getMessage());
        }
    }
}

// Fetch existing bank details
$bank_details = null;
try {
    $stmt = $conn->prepare("SELECT * FROM bank_details WHERE partner_id = ?");
    $stmt->execute([$senior_partner_id]);
    $bank_details = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching bank details: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Details - Senior Partner Profile</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/senior-partner-style.css" rel="stylesheet">
    
    <style>
        .bank-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .bank-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
        }
        
        .verification-badge {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .pending-badge {
            background: #ffc107;
            color: #000;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>

    <!-- Mobile Menu Toggle Button -->
    <button id="mobile-menu-toggle" class="d-md-none position-fixed top-0 end-0 btn btn-link text-white p-3" style="z-index: 1100;">
        <i class="bi bi-list fs-4"></i>
    </button>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="content-header">
                        <h1 class="mb-2"><i class="bi bi-bank me-2"></i>Bank Details</h1>
                        <p class="text-muted">Manage your banking information for earnings withdrawal</p>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Current Bank Details Card -->
                <?php if ($bank_details): ?>
                <div class="col-lg-6 mb-4">
                    <div class="bank-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h4><i class="bi bi-credit-card me-2"></i>Current Bank Details</h4>
                            <?php if ($bank_details['is_verified']): ?>
                                <span class="verification-badge">
                                    <i class="bi bi-check-circle me-1"></i>Verified
                                </span>
                            <?php else: ?>
                                <span class="pending-badge">
                                    <i class="bi bi-clock me-1"></i>Pending Verification
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bank-info">
                            <div class="row">
                                <div class="col-sm-6 mb-2">
                                    <small class="opacity-75">Bank Name</small>
                                    <div class="fw-bold"><?php echo htmlspecialchars($bank_details['bank_name']); ?></div>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="opacity-75">Account Holder</small>
                                    <div class="fw-bold"><?php echo htmlspecialchars($bank_details['account_holder_name']); ?></div>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="opacity-75">Account Number</small>
                                    <div class="fw-bold">****<?php echo substr($bank_details['account_number'], -4); ?></div>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="opacity-75">IFSC Code</small>
                                    <div class="fw-bold"><?php echo htmlspecialchars($bank_details['ifsc_code']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <small class="opacity-75">
                                <i class="bi bi-calendar me-1"></i>
                                Last updated: <?php echo date('M d, Y', strtotime($bank_details['updated_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Bank Details Form -->
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-pencil-square me-2"></i>
                                <?php echo $bank_details ? 'Update Bank Details' : 'Add Bank Details'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="bankDetailsForm">
                                <div class="mb-3">
                                    <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                           value="<?php echo $bank_details ? htmlspecialchars($bank_details['bank_name']) : ''; ?>" 
                                           required placeholder="e.g., State Bank of India">
                                </div>

                                <div class="mb-3">
                                    <label for="account_holder_name" class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="account_holder_name" name="account_holder_name" 
                                           value="<?php echo $bank_details ? htmlspecialchars($bank_details['account_holder_name']) : ''; ?>" 
                                           required placeholder="Full name as per bank records">
                                </div>

                                <div class="mb-3">
                                    <label for="account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" 
                                           value="<?php echo $bank_details ? htmlspecialchars($bank_details['account_number']) : ''; ?>" 
                                           required placeholder="Enter your account number" pattern="[0-9]{9,18}">
                                    <div class="form-text">Enter 9-18 digit account number</div>
                                </div>

                                <div class="mb-4">
                                    <label for="ifsc_code" class="form-label">IFSC Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" 
                                           value="<?php echo $bank_details ? htmlspecialchars($bank_details['ifsc_code']) : ''; ?>" 
                                           required placeholder="e.g., SBIN0001234" pattern="[A-Z]{4}0[A-Z0-9]{6}" 
                                           style="text-transform: uppercase;">
                                    <div class="form-text">11-character IFSC code (e.g., SBIN0001234)</div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-save me-2"></i>
                                        <?php echo $bank_details ? 'Update Bank Details' : 'Save Bank Details'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="card mt-4 border-warning">
                        <div class="card-body">
                            <h6 class="card-title text-warning">
                                <i class="bi bi-shield-exclamation me-2"></i>Security Notice
                            </h6>
                            <ul class="mb-0 small">
                                <li>Your bank details are encrypted and stored securely</li>
                                <li>Only verified bank accounts can receive earnings</li>
                                <li>Verification may take 1-2 business days</li>
                                <li>Contact support if you need to change verified details</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/senior-partner.js"></script>
    
    <script>
        // Form validation
        document.getElementById('bankDetailsForm').addEventListener('submit', function(e) {
            const ifscCode = document.getElementById('ifsc_code').value;
            const accountNumber = document.getElementById('account_number').value;
            
            // IFSC validation
            const ifscPattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;
            if (!ifscPattern.test(ifscCode)) {
                e.preventDefault();
                alert('Please enter a valid IFSC code (e.g., SBIN0001234)');
                return;
            }
            
            // Account number validation
            const accountPattern = /^[0-9]{9,18}$/;
            if (!accountPattern.test(accountNumber)) {
                e.preventDefault();
                alert('Please enter a valid account number (9-18 digits)');
                return;
            }
        });
        
        // Auto-uppercase IFSC code
        document.getElementById('ifsc_code').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>