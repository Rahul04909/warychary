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
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New password and confirm password do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $new_password)) {
        $error_message = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM senior_partners WHERE id = ?");
            $stmt->execute([$senior_partner_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user_data || !password_verify($current_password, $user_data['password'])) {
                $error_message = "Current password is incorrect.";
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE senior_partners SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->execute([$hashed_password, $senior_partner_id]);
                
                $success_message = "Password changed successfully!";
                
                // Clear form data on success
                $_POST = array();
            }
        } catch (PDOException $e) {
            $error_message = "Error changing password. Please try again.";
            error_log("Password change error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Senior Partner</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/senior-partner-style.css" rel="stylesheet">
    
    <style>
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: #dc3545; }
        .strength-medium { background-color: #ffc107; }
        .strength-strong { background-color: #28a745; }
        
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
        
        .security-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 2rem;
        }
        
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #495057;
        }
        
        .requirements-list {
            font-size: 0.875rem;
        }
        
        .requirement-met {
            color: #28a745;
        }
        
        .requirement-unmet {
            color: #dc3545;
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
                        <h1 class="mb-2"><i class="bi bi-shield-lock me-2"></i>Change Password</h1>
                        <p class="text-muted">Update your account password to keep your account secure</p>
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
                <!-- Security Information -->
                <div class="col-lg-4 mb-4">
                    <div class="security-card">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-check display-4 mb-3"></i>
                            <h4>Account Security</h4>
                            <p class="mb-0">Keep your account safe with a strong password</p>
                        </div>
                        
                        <div class="mb-4">
                            <h6><i class="bi bi-person me-2"></i>Account Info</h6>
                            <div class="bg-white bg-opacity-10 rounded p-3">
                                <div class="mb-2">
                                    <small class="opacity-75">Name</small>
                                    <div class="fw-bold"><?php echo htmlspecialchars($senior_partner_name); ?></div>
                                </div>
                                <div>
                                    <small class="opacity-75">Last Password Change</small>
                                    <div class="fw-bold">Today</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="security-tips">
                            <h6><i class="bi bi-lightbulb me-2"></i>Security Tips</h6>
                            <ul class="list-unstyled small">
                                <li class="mb-2"><i class="bi bi-check me-2"></i>Use a unique password</li>
                                <li class="mb-2"><i class="bi bi-check me-2"></i>Include special characters</li>
                                <li class="mb-2"><i class="bi bi-check me-2"></i>Make it at least 8 characters</li>
                                <li class="mb-2"><i class="bi bi-check me-2"></i>Don't share with anyone</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Change Password Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-key me-2"></i>Change Your Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="changePasswordForm">
                                <!-- Current Password -->
                                <div class="mb-4">
                                    <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control pe-5" id="current_password" name="current_password" 
                                               required placeholder="Enter your current password">
                                        <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                            <i class="bi bi-eye" id="current_password_icon"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control pe-5" id="new_password" name="new_password" 
                                               required placeholder="Enter your new password">
                                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                            <i class="bi bi-eye" id="new_password_icon"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength" id="passwordStrength"></div>
                                </div>

                                <!-- Password Requirements -->
                                <div class="mb-4">
                                    <div class="requirements-list">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div id="req-length" class="requirement-unmet">
                                                    <i class="bi bi-x-circle me-1"></i>At least 8 characters
                                                </div>
                                                <div id="req-uppercase" class="requirement-unmet">
                                                    <i class="bi bi-x-circle me-1"></i>One uppercase letter
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div id="req-lowercase" class="requirement-unmet">
                                                    <i class="bi bi-x-circle me-1"></i>One lowercase letter
                                                </div>
                                                <div id="req-number" class="requirement-unmet">
                                                    <i class="bi bi-x-circle me-1"></i>One number
                                                </div>
                                            </div>
                                        </div>
                                        <div id="req-special" class="requirement-unmet">
                                            <i class="bi bi-x-circle me-1"></i>One special character (@$!%*?&)
                                        </div>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control pe-5" id="confirm_password" name="confirm_password" 
                                               required placeholder="Confirm your new password">
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="bi bi-eye" id="confirm_password_icon"></i>
                                        </button>
                                    </div>
                                    <div id="passwordMatch" class="form-text"></div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                                        <i class="bi bi-shield-check me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
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
        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[@$!%*?&]/.test(password)
            };
            
            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById('req-' + req);
                if (requirements[req]) {
                    element.className = 'requirement-met';
                    element.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + element.textContent.replace(/^.*?\s/, '');
                    strength++;
                } else {
                    element.className = 'requirement-unmet';
                    element.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + element.textContent.replace(/^.*?\s/, '');
                }
            });
            
            // Update strength bar
            const strengthBar = document.getElementById('passwordStrength');
            if (strength < 3) {
                strengthBar.className = 'password-strength strength-weak';
                strengthBar.style.width = '33%';
            } else if (strength < 5) {
                strengthBar.className = 'password-strength strength-medium';
                strengthBar.style.width = '66%';
            } else {
                strengthBar.className = 'password-strength strength-strong';
                strengthBar.style.width = '100%';
            }
            
            return strength === 5;
        }
        
        // Password match checker
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchDiv.textContent = '';
                return false;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i>Passwords match';
                matchDiv.className = 'form-text text-success';
                return true;
            } else {
                matchDiv.innerHTML = '<i class="bi bi-x-circle text-danger me-1"></i>Passwords do not match';
                matchDiv.className = 'form-text text-danger';
                return false;
            }
        }
        
        // Form validation
        function validateForm() {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            const isStrong = checkPasswordStrength(newPassword);
            const isMatching = checkPasswordMatch();
            const hasCurrentPassword = currentPassword.length > 0;
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = !(isStrong && isMatching && hasCurrentPassword);
        }
        
        // Event listeners
        document.getElementById('new_password').addEventListener('input', validateForm);
        document.getElementById('confirm_password').addEventListener('input', validateForm);
        document.getElementById('current_password').addEventListener('input', validateForm);
        
        // Form submission
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (!checkPasswordStrength(newPassword)) {
                e.preventDefault();
                alert('Password does not meet all requirements!');
                return;
            }
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