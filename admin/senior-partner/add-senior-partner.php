<?php
require_once '../../database/config.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$admin_name = "Admin";

$database = new Database();
$db = $database->getConnection();

// Function to generate an 8-digit alphanumeric ID
function generateSeniorPartnerId($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}



// Function to send email
function sendSeniorPartnerEmail($toEmail, $toName, $partnerId, $password, $smtpSettings) {
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
        $mail->Subject = 'Welcome to WaryCharyCare - Your Senior Partner Account Details';
        $mail->Body    = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <img src="http://localhost:8000/logo/warycharycare.png" alt="WaryCharyCare Logo" style="max-width: 150px;">
                            </div>
                            <h2 style="color: #7c43b9; text-align: center;">Welcome, ' . htmlspecialchars($toName) . '!</h2>
                            <p>We are thrilled to have you join us as a Senior Partner at WaryCharyCare. Your account has been successfully created.</p>
                            <p>Here are your login details:</p>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 10px;"><strong>Partner ID:</strong> <span style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($partnerId) . '</span></li>
                                <li style="margin-bottom: 10px;"><strong>Password:</strong> <span style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($password) . '</span></li>
                                <li style="margin-bottom: 10px;"><strong>Login URL:</strong> <a href="http://localhost:8000/admin/login.php" style="color: #7c43b9; text-decoration: none;">Click here to login</a></li>
                            </ul>
                            <p>Please keep your login details secure. We recommend changing your password after your first login.</p>
                            <p>If you have any questions or need assistance, please do not hesitate to contact our support team.</p>
                            <p style="text-align: center; margin-top: 30px; font-size: 0.9em; color: #777;">
                                Thank you,<br>
                                The WaryCharyCare Team
                            </p>
                            <div style="text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; font-size: 0.8em; color: #aaa;">
                                &copy; ' . date('Y') . ' WaryCharyCare. All rights reserved.
                            </div>
                        </div>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_partner'])) {
        $partner_name = trim($_POST['partner_name']);
        $partner_email = trim($_POST['partner_email']);
        $partner_phone = trim($_POST['partner_phone']);
        $partner_gender = $_POST['partner_gender'] ?? null;
        $partner_state = trim($_POST['partner_state']) ?? null;
        $partner_district = trim($_POST['partner_district']) ?? null;
        $partner_full_address = trim($_POST['partner_full_address']) ?? null;
        $partner_password = trim($_POST['partner_password']);
        $partner_image = null;

        // Handle image upload
        if (isset($_FILES['partner_image']) && $_FILES['partner_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../../images/senior-partners/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image_file_type = strtolower(pathinfo($_FILES['partner_image']['name'], PATHINFO_EXTENSION));
            $new_file_name = uniqid('partner_') . "." . $image_file_type;
            $target_file = $target_dir . $new_file_name;
            $check = getimagesize($_FILES['partner_image']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['partner_image']['tmp_name'], $target_file)) {
                    $partner_image = "images/senior-partners/" . $new_file_name;
                } else {
                    $error_message = "Sorry, there was an error uploading your image.";
                }
            } else {
                $error_message = "File is not an image.";
            }
        }

        if (empty($partner_name) || empty($partner_email) || empty($partner_phone)) {
            $error_message = "Name, Email, and Phone are required fields.";
        } else if (!filter_var($partner_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } else {
            try {
                // Check if email already exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM senior_partners WHERE email = :email");
                $stmt->execute([':email' => $partner_email]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "A senior partner with this email already exists.";
                } else {
                    $partner_id = generateSeniorPartnerId();
                    $password = $partner_password;
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $db->prepare("INSERT INTO senior_partners (partner_id, name, email, phone, image, gender, state, district, full_address, password, earning) VALUES (:partner_id, :name, :email, :phone, :image, :gender, :state, :district, :full_address, :password, :earning)");
                    $stmt->execute([
                        ':partner_id' => $partner_id,
                        ':name' => $partner_name,
                        ':email' => $partner_email,
                        ':phone' => $partner_phone,
                        ':earning' => 2.0, // Default 2% earning for new senior partners
                        ':image' => $partner_image,
                        ':gender' => $partner_gender,
                        ':state' => $partner_state,
                        ':district' => $partner_district,
                        ':full_address' => $partner_full_address,
                        ':password' => $hashed_password
                    ]);

                    // Fetch SMTP settings
                    $stmt = $db->query("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
                    $smtp_settings = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($smtp_settings) {
                        if (sendSeniorPartnerEmail($partner_email, $partner_name, $partner_id, $password, $smtp_settings)) {
                            $success_message = "Senior Partner added successfully and email sent!";
                        } else {
                            $error_message = "Senior Partner added, but failed to send email.";
                        }
                    } else {
                        $error_message = "Senior Partner added, but SMTP settings not found. Email not sent.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

include '../sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Senior Partner - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/admin-style.css" rel="stylesheet">
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
        .image-preview {
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            margin-top: 10px;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>

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
                    <div class="card-header">
                        <h5>Add New Senior Partner</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="partner_name" class="form-label">Senior Partner Name</label>
                                <input type="text" class="form-control" id="partner_name" name="partner_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="partner_email" class="form-label">Senior Partner Email</label>
                                <input type="email" class="form-control" id="partner_email" name="partner_email" required>
                            </div>
                            <div class="mb-3">
                                <label for="partner_phone" class="form-label">Senior Partner Phone</label>
                                <input type="text" class="form-control" id="partner_phone" name="partner_phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="partner_image" class="form-label">Senior Partner Image (Optional)</label>
                                <input type="file" class="form-control" id="partner_image" name="partner_image" accept="image/*">
                                <div class="image-preview" id="imagePreview">
                                    <img src="" alt="Image Preview" class="d-none">
                                    <span class="text-muted">No Image</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="partner_gender" class="form-label">Gender</label>
                                <select class="form-select" id="partner_gender" name="partner_gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="partner_state" class="form-label">State</label>
                                <input type="text" class="form-control" id="partner_state" name="partner_state">
                            </div>
                            <div class="mb-3">
                                <label for="partner_district" class="form-label">District</label>
                                <input type="text" class="form-control" id="partner_district" name="partner_district">
                            </div>
                            <div class="mb-3">
                                <label for="partner_full_address" class="form-label">Full Address</label>
                                <textarea class="form-control" id="partner_full_address" name="partner_full_address" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="partner_password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="partner_password" name="partner_password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" name="add_partner" class="btn btn-primary">Add Senior Partner</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const closeSidebarButton = document.getElementById('close-sidebar');
        const mainContent = document.querySelector('.main-content');

        if (mobileMenuToggle && sidebar && sidebarOverlay && closeSidebarButton && mainContent) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.classList.add('sidebar-active');
                mainContent.classList.add('sidebar-active');
            });

            closeSidebarButton.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-active');
                mainContent.classList.remove('sidebar-active');
            });

            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-active');
                mainContent.classList.remove('sidebar-active');
            });
        }

        // Image preview logic
        const partnerImageInput = document.getElementById('partner_image');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewImg = imagePreview.querySelector('img');
        const imagePreviewText = imagePreview.querySelector('span');

        if (partnerImageInput) {
            partnerImageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreviewImg.src = e.target.result;
                        imagePreviewImg.classList.remove('d-none');
                        imagePreviewText.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreviewImg.src = '';
                    imagePreviewImg.classList.add('d-none');
                    imagePreviewText.classList.remove('d-none');
                }
            });
        }

        // Password toggle logic
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('partner_password');
        const togglePasswordIcon = document.getElementById('togglePasswordIcon');

        if (togglePassword && passwordInput && togglePasswordIcon) {
            togglePassword.addEventListener('click', function() {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle the eye icon
                togglePasswordIcon.classList.toggle('bi-eye');
                togglePasswordIcon.classList.toggle('bi-eye-slash');
            });
        }
    });
</script>
</body>
</html>