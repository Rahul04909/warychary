<?php
session_start();
if (!isset($_SESSION['partner_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../../database/config.php';
$db = new Database();
$conn = $db->getConnection();

$partner_id = $_SESSION['partner_id'];
$success_message = '';
$error_message = '';

// Create bank_details table if not exists
try {
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS partner_bank_details (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id INT NOT NULL,
            bank_name VARCHAR(255) NOT NULL,
            account_number VARCHAR(50) NOT NULL,
            ifsc_code VARCHAR(20) NOT NULL,
            account_holder_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
            UNIQUE KEY unique_partner_bank (partner_id)
        )
    ";
    $conn->exec($create_table_sql);
} catch (PDOException $e) {
    $error_message = 'Error creating bank details table: ' . $e->getMessage();
}

// Handle bank details update/insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_bank_details') {
    $bank_name = trim($_POST['bank_name']);
    $account_number = trim($_POST['account_number']);
    $ifsc_code = strtoupper(trim($_POST['ifsc_code']));
    $account_holder_name = trim($_POST['account_holder_name']);
    
    // Validate required fields
    if (empty($bank_name) || empty($account_number) || empty($ifsc_code) || empty($account_holder_name)) {
        $error_message = 'All fields are required.';
    } elseif (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc_code)) {
        $error_message = 'Invalid IFSC code format. Please enter a valid IFSC code.';
    } elseif (!preg_match('/^[0-9]{9,18}$/', $account_number)) {
        $error_message = 'Account number must be between 9-18 digits.';
    } else {
        try {
            // Check if bank details already exist for this partner
            $stmt = $conn->prepare("SELECT id FROM partner_bank_details WHERE partner_id = ?");
            $stmt->execute([$partner_id]);
            $existing_details = $stmt->fetch();
            
            if ($existing_details) {
                // Update existing bank details
                $stmt = $conn->prepare("
                    UPDATE partner_bank_details 
                    SET bank_name = ?, account_number = ?, ifsc_code = ?, account_holder_name = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE partner_id = ?
                ");
                $stmt->execute([$bank_name, $account_number, $ifsc_code, $account_holder_name, $partner_id]);
                $success_message = 'Bank details updated successfully!';
            } else {
                // Insert new bank details
                $stmt = $conn->prepare("
                    INSERT INTO partner_bank_details (partner_id, bank_name, account_number, ifsc_code, account_holder_name) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$partner_id, $bank_name, $account_number, $ifsc_code, $account_holder_name]);
                $success_message = 'Bank details saved successfully!';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get current bank details
$bank_details = null;
try {
    $stmt = $conn->prepare("SELECT * FROM partner_bank_details WHERE partner_id = ?");
    $stmt->execute([$partner_id]);
    $bank_details = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Error loading bank details.';
}

// Get partner information
try {
    $stmt = $conn->prepare("SELECT name, partner_id FROM partners WHERE id = ?");
    $stmt->execute([$partner_id]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $partner = ['name' => 'Partner', 'partner_id' => 'N/A'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Details - WaryChary Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/partner-style.css" rel="stylesheet">
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content">
        <button class="btn btn-primary d-md-none mb-3" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="content-header">
            <h1 class="text-gradient">Bank Details</h1>
            <p class="text-muted">Manage your bank account information for earnings withdrawal</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <!-- Bank Details Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-university me-2"></i>Bank Account Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bankDetailsForm">
                            <input type="hidden" name="action" value="save_bank_details">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="bank_name" class="form-label">Bank Name *</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                           value="<?php echo htmlspecialchars($bank_details['bank_name'] ?? ''); ?>" 
                                           placeholder="e.g., State Bank of India" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="account_holder_name" class="form-label">Account Holder Name *</label>
                                    <input type="text" class="form-control" id="account_holder_name" name="account_holder_name" 
                                           value="<?php echo htmlspecialchars($bank_details['account_holder_name'] ?? ''); ?>" 
                                           placeholder="Full name as per bank records" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="account_number" class="form-label">Account Number *</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" 
                                           value="<?php echo htmlspecialchars($bank_details['account_number'] ?? ''); ?>" 
                                           placeholder="Enter your bank account number" 
                                           pattern="[0-9]{9,18}" title="Account number must be 9-18 digits" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="ifsc_code" class="form-label">IFSC Code *</label>
                                    <input type="text" class="form-control text-uppercase" id="ifsc_code" name="ifsc_code" 
                                           value="<?php echo htmlspecialchars($bank_details['ifsc_code'] ?? ''); ?>" 
                                           placeholder="e.g., SBIN0001234" 
                                           pattern="[A-Z]{4}0[A-Z0-9]{6}" title="Enter valid IFSC code" 
                                           maxlength="11" required>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        IFSC code format: 4 letters + 0 + 6 alphanumeric characters
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $bank_details ? 'Update Bank Details' : 'Save Bank Details'; ?>
                                </button>
                                
                                <?php if ($bank_details): ?>
                                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="resetForm()">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Account Summary -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Account Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted">Partner Name</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($partner['name']); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Partner ID</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($partner['partner_id']); ?></div>
                        </div>
                        
                        <div class="mb-0">
                            <label class="form-label text-muted">Bank Details Status</label>
                            <div>
                                <?php if ($bank_details): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Completed
                                    </span>
                                    <div class="small text-muted mt-1">
                                        Last updated: <?php echo date('M d, Y', strtotime($bank_details['updated_at'])); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Pending
                                    </span>
                                    <div class="small text-muted mt-1">
                                        Please add your bank details to receive earnings
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Notice -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Security Notice
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-lock me-2"></i>Your Information is Safe
                            </h6>
                            <p class="mb-2">Your bank details are encrypted and stored securely. We use industry-standard security measures to protect your financial information.</p>
                            <hr>
                            <p class="mb-0 small">
                                <i class="fas fa-info-circle me-1"></i>
                                Bank details are required for earnings withdrawal and will be verified before processing payments.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/partner-script.js"></script>
    
    <script>
        // Auto-uppercase IFSC code
        document.getElementById('ifsc_code').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Account number validation
        document.getElementById('account_number').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // IFSC code validation
        document.getElementById('ifsc_code').addEventListener('input', function() {
            const ifscPattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;
            const value = this.value;
            
            if (value.length === 11) {
                if (ifscPattern.test(value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
        
        // Reset form function
        function resetForm() {
            document.getElementById('bankDetailsForm').reset();
            document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
        }
        
        // Form validation
        document.getElementById('bankDetailsForm').addEventListener('submit', function(e) {
            const ifscCode = document.getElementById('ifsc_code').value;
            const accountNumber = document.getElementById('account_number').value;
            
            if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifscCode)) {
                e.preventDefault();
                alert('Please enter a valid IFSC code (e.g., SBIN0001234)');
                return false;
            }
            
            if (!/^[0-9]{9,18}$/.test(accountNumber)) {
                e.preventDefault();
                alert('Account number must be between 9-18 digits');
                return false;
            }
        });
    </script>
</body>
</html>