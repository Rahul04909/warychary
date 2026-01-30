<?php
require_once '../../database/config.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Initialize database connection

// Set default admin name
$admin_name = "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Settings - Admin Dashboard</title>
    
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

<?php
$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_smtp'])) {
        try {
            $query = "INSERT INTO smtp_settings 
                    (smtp_host, smtp_port, smtp_username, smtp_password, smtp_from_email, smtp_from_name, smtp_encryption) 
                    VALUES (:host, :port, :username, :password, :from_email, :from_name, :encryption)
                    ON DUPLICATE KEY UPDATE 
                    smtp_host = VALUES(smtp_host),
                    smtp_port = VALUES(smtp_port),
                    smtp_username = VALUES(smtp_username),
                    smtp_password = VALUES(smtp_password),
                    smtp_from_email = VALUES(smtp_from_email),
                    smtp_from_name = VALUES(smtp_from_name),
                    smtp_encryption = VALUES(smtp_encryption)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                ':host' => $_POST['smtp_host'],
                ':port' => $_POST['smtp_port'],
                ':username' => $_POST['smtp_username'],
                ':password' => $_POST['smtp_password'],
                ':from_email' => $_POST['smtp_from_email'],
                ':from_name' => $_POST['smtp_from_name'],
                ':encryption' => $_POST['smtp_encryption']
            ]);

            $success_message = "SMTP settings saved successfully!";
        } catch(PDOException $e) {
            $error_message = "Error saving SMTP settings: " . $e->getMessage();
        }
    } elseif (isset($_POST['test_smtp'])) {
        try {
            // Get SMTP settings from database
            $stmt = $db->query("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
            $smtp_settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($smtp_settings) {
                $mail = new PHPMailer(true);

                // Server settings
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                $mail->isSMTP();
                $mail->Host = $smtp_settings['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_settings['smtp_username'];
                $mail->Password = $smtp_settings['smtp_password'];
                $mail->SMTPSecure = $smtp_settings['smtp_encryption'];
                $mail->Port = $smtp_settings['smtp_port'];

                // Recipients
                $mail->setFrom($smtp_settings['smtp_from_email'], $smtp_settings['smtp_from_name']);
                $mail->addAddress($_POST['test_email']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'SMTP Test Email';
                $mail->Body = 'This is a test email to verify SMTP configuration. If you receive this, your SMTP settings are working correctly!';

                $mail->send();
                $success_message = "Test email sent successfully!";
            } else {
                $error_message = "No active SMTP settings found!";
            }
        } catch (Exception $e) {
            $error_message = "Error sending test email: " . $mail->ErrorInfo;
        }
    }
}

// Get current SMTP settings
try {
    $stmt = $db->query("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
    $smtp_settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching SMTP settings: " . $e->getMessage();
}

// Include sidebar
include '../sidebar.php';
?>

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
                        <h5 class="card-title mb-0">SMTP Configuration</h5>
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
                                        <label for="smtp_host" class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                               value="<?php echo $smtp_settings['smtp_host'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">Please provide SMTP host.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                               value="<?php echo $smtp_settings['smtp_port'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">Please provide SMTP port.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_username" class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                               value="<?php echo $smtp_settings['smtp_username'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">Please provide SMTP username.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_password" class="form-label">SMTP Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                                   value="<?php echo $smtp_settings['smtp_password'] ?? ''; ?>" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Please provide SMTP password.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_from_email" class="form-label">From Email</label>
                                        <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" 
                                               value="<?php echo $smtp_settings['smtp_from_email'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">Please provide a valid email address.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_from_name" class="form-label">From Name</label>
                                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" 
                                               value="<?php echo $smtp_settings['smtp_from_name'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">Please provide sender name.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_encryption" class="form-label">Encryption</label>
                                        <select class="form-select" id="smtp_encryption" name="smtp_encryption" required>
                                            <option value="tls" <?php echo ($smtp_settings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo ($smtp_settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        </select>
                                        <div class="invalid-feedback">Please select encryption type.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" name="save_smtp" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Settings
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <!-- Test Email Section -->
                        <h5 class="mb-4">Test SMTP Configuration</h5>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="test_email" class="form-label">Test Email Address</label>
                                        <input type="email" class="form-control" id="test_email" name="test_email" required>
                                        <div class="invalid-feedback">Please provide a valid email address for testing.</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="test_smtp" class="btn btn-info">
                                        <i class="bi bi-envelope me-2"></i>Send Test Email
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
    const passwordInput = document.getElementById('smtp_password');
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
});
</script>
</body>
</html>