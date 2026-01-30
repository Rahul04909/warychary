<?php
require_once 'database/config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

$database = new Database();
$db = $database->getConnection();

// Function to validate referral code
function validateReferralCode($db, $referral_code) {
    try {
        $stmt = $db->prepare("SELECT id, partner_id, name FROM partners WHERE partner_id = :code OR email = :code OR phone = :code");
        $stmt->execute([':code' => $referral_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error validating referral code: " . $e->getMessage());
        return false;
    }
}

// Function to send welcome email
function sendWelcomeEmail($toEmail, $toName, $password, $smtpSettings) {
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
        $mail->Subject = 'Welcome to WaryCharyCare - Your Account Details';
        $mail->Body    = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <img src="logo/warycharycare.png" alt="WaryCharyCare Logo" style="max-width: 150px;">
                            </div>
                            <h2 style="color: #7c43b9; text-align: center;">Welcome, ' . htmlspecialchars($toName) . '!</h2>
                            <p>Thank you for registering with WaryCharyCare. Your account has been created successfully.</p>
                            <p>Here are your login details:</p>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 10px;"><strong>Email:</strong> <span style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($toEmail) . '</span></li>
                                <li style="margin-bottom: 10px;"><strong>Password:</strong> <span style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($password) . '</span></li>
                                <li style="margin-bottom: 10px;"><strong>Login URL:</strong> <a href="http://localhost:8080/login.php" style="color: #7c43b9; text-decoration: none;">Click here to login</a></li>
                            </ul>
                            <p>Please note:</p>
                            <ul>
                                <li>Please keep your login details secure</li>
                                <li>We recommend changing your password after your first login</li>
                            </ul>
                            <p>If you have any questions or need assistance, please contact our support team.</p>
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
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

$success_message = '';
$error_message = '';
$referral_info = null;

// Handle AJAX request for referral code validation
if (isset($_POST['action']) && $_POST['action'] === 'validate_referral') {
    header('Content-Type: application/json');
    $referral_code = trim($_POST['referral_code']);
    $result = validateReferralCode($db, $referral_code);
    echo json_encode(['valid' => (bool)$result, 'name' => $result ? $result['name'] : '']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'] ?? null;
    $state = trim($_POST['state']);
    $district = trim($_POST['district']);
    $pincode = trim($_POST['pincode']);
    $full_address = trim($_POST['full_address']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $referral_code = trim($_POST['referral_code'] ?? '');
    $user_image = null;

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($phone) < 10) {
        $error_message = "Please enter a valid phone number.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    }

    // Validate referral code if provided
    if (!empty($referral_code)) {
        $referral_info = validateReferralCode($db, $referral_code);
        if (!$referral_info) {
            $error_message = "Invalid referral code.";
        }
    }

    // Handle image upload
    if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "images/users/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_file_type = strtolower(pathinfo($_FILES['user_image']['name'], PATHINFO_EXTENSION));
        $new_file_name = uniqid('user_') . "." . $image_file_type;
        $target_file = $target_dir . $new_file_name;
        $check = getimagesize($_FILES['user_image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['user_image']['tmp_name'], $target_file)) {
                $user_image = $target_file;
            } else {
                $error_message = "Sorry, there was an error uploading your image.";
            }
        } else {
            $error_message = "File is not an image.";
        }
    }

    if (empty($error_message)) {
        try {
            // Check if email or phone already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR phone = :phone");
            $stmt->execute([':email' => $email, ':phone' => $phone]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "A user with this email or phone number already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Get partner ID if referral code is provided
                $referred_by_partner = null;
                if (!empty($referral_code) && $referral_info) {
                    $referred_by_partner = $referral_info['id']; // Use the 'id' field instead of 'partner_id'
                }

                $stmt = $db->prepare("INSERT INTO users (name, email, phone, gender, image, state, district, pincode, full_address, password, referral_code, referred_by_partner) VALUES (:name, :email, :phone, :gender, :image, :state, :district, :pincode, :full_address, :password, :referral_code, :referred_by_partner)");
                $result = $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':gender' => $gender,
                    ':image' => $user_image,
                    ':state' => $state,
                    ':district' => $district,
                    ':pincode' => $pincode,
                    ':full_address' => $full_address,
                    ':password' => $hashed_password,
                    ':referral_code' => $referral_code,
                    ':referred_by_partner' => $referred_by_partner
                ]);

                if ($result) {
                    // Fetch SMTP settings
                    $stmt = $db->query("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
                    $smtp_settings = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($smtp_settings) {
                        if (sendWelcomeEmail($email, $name, $password, $smtp_settings)) {
                            $success_message = "Registration successful! Please check your email for login details.";
                        } else {
                            $success_message = "Registration successful! However, there was an issue sending the email.";
                        }
                    } else {
                        $success_message = "Registration successful! However, email could not be sent due to missing SMTP settings.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - WaryCharyCare</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin: 40px auto;
        }
        .registration-form {
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
        .image-preview {
            max-width: 200px;
            margin-top: 10px;
            border-radius: 10px;
            display: none;
        }
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .referral-feedback {
            margin-top: 5px;
            font-size: 0.9em;
        }
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
            }
            .form-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="registration-form">
            <div class="form-header">
                <h2>Create Your Account</h2>
                <p>Join WaryCharyCare today</p>
            </div>
            <div class="form-body">
                <form method="POST" enctype="multipart/form-data" id="registrationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Mobile Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender *</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="user_image" class="form-label">Profile Image (Optional)</label>
                                <input type="file" class="form-control" id="user_image" name="user_image" accept="image/*">
                                <img id="imagePreview" src="#" alt="Image Preview" class="image-preview">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="state" class="form-label">State *</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                            <div class="mb-3">
                                <label for="district" class="form-label">District *</label>
                                <input type="text" class="form-control" id="district" name="district" required>
                            </div>
                            <div class="mb-3">
                                <label for="pincode" class="form-label">Pincode *</label>
                                <input type="text" class="form-control" id="pincode" name="pincode" required>
                            </div>
                            <div class="mb-3">
                                <label for="full_address" class="form-label">Full Address *</label>
                                <textarea class="form-control" id="full_address" name="full_address" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Create Password *</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <i class="bi bi-eye-slash password-toggle" id="toggleConfirmPassword"></i>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="referral_code" class="form-label">Partner Referral Code (Optional)</label>
                                <input type="text" class="form-control" id="referral_code" name="referral_code">
                                <div id="referralFeedback" class="referral-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" name="register" class="btn btn-primary btn-lg">Register</button>
                    </div>
                    <div class="form-group text-center mt-3">
                        <p>Already have an account? <a href="user/login.php" class="btn btn-outline-primary btn-lg">Login</a></p>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview
        document.getElementById('user_image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            if (file) {
                preview.style.display = 'block';
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Password toggle
        function togglePasswordVisibility(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                toggle.classList.toggle('bi-eye');
                toggle.classList.toggle('bi-eye-slash');
            });
        }

        togglePasswordVisibility('password', 'togglePassword');
        togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');

        // Referral code validation
        let referralTimeout;
        document.getElementById('referral_code').addEventListener('input', function(e) {
            clearTimeout(referralTimeout);
            const referralCode = e.target.value.trim();
            const feedback = document.getElementById('referralFeedback');

            if (referralCode === '') {
                feedback.innerHTML = '';
                return;
            }

            referralTimeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('action', 'validate_referral');
                formData.append('referral_code', referralCode);

                fetch('register.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        feedback.innerHTML = `<span class="text-success">Referral by: ${data.name}</span>`;
                    } else {
                        feedback.innerHTML = '<span class="text-danger">Invalid referral code</span>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    feedback.innerHTML = '<span class="text-danger">Error checking referral code</span>';
                });
            }, 500);
        });

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const phone = document.getElementById('phone').value;
            const pincode = document.getElementById('pincode').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return;
            }

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