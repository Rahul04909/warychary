<?php
require_once '../database/config.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

// Check if partner is logged in
if (!isset($_SESSION['partner_id'])) {
    header('Location: partner/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get partner info
$stmt = $db->prepare("SELECT * FROM partners WHERE id = :id");
$stmt->execute([':id' => $_SESSION['partner_id']]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partner) {
    session_destroy();
    header('Location: partner/login.php');
    exit;
}

// Function to generate a password based on name
function generatePasswordFromName($name) {
    $name = str_replace(' ', '', $name);
    $password = substr($name, 0, 4) . rand(100, 999) . '!';
    return $password;
}

// Function to send welcome email
function sendWelcomeEmail($toEmail, $toName, $password, $smtpSettings, $partnerName) {
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
                                <img src="http://localhost:8000/logo/warycharycare.png" alt="WaryCharyCare Logo" style="max-width: 150px;">
                            </div>
                            <h2 style="color: #7c43b9; text-align: center;">Welcome, ' . htmlspecialchars($toName) . '!</h2>
                            <p>You have been invited to join WaryCharyCare by our partner <strong>' . htmlspecialchars($partnerName) . '</strong>.</p>
                            <p>Your account has been created successfully with automatic referral benefits.</p>
                            <p>Here are your login details:</p>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 10px;"><strong>Email:</strong> <span style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($toEmail) . '</span></li>
                                <li style="margin-bottom: 10px;"><strong>Password:</strong> <span style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($password) . '</span></li>
                                <li style="margin-bottom: 10px;"><strong>Login URL:</strong> <a href="http://localhost:8000/login.php" style="color: #7c43b9; text-decoration: none;">Click here to login</a></li>
                            </ul>
                            <p>Benefits of joining through our partner:</p>
                            <ul>
                                <li>Automatic referral tracking and rewards</li>
                                <li>Access to exclusive services and offers</li>
                                <li>Priority customer support</li>
                                <li>Special discounts on healthcare services</li>
                            </ul>
                            <p>Please note:</p>
                            <ul>
                                <li>Please keep your login details secure</li>
                                <li>We recommend changing your password after your first login</li>
                                <li>Your account is linked to partner: <strong>' . htmlspecialchars($partnerName) . '</strong></li>
                            </ul>
                            <p>If you have any questions or need assistance, please contact our support team or your referring partner.</p>
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'] ?? null;
    $state = trim($_POST['state']);
    $district = trim($_POST['district']);
    $pincode = trim($_POST['pincode']);
    $full_address = trim($_POST['full_address']);
    $user_image = null;

    // Basic validation
    if (empty($name) || empty($email) || empty($phone)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($phone) < 10) {
        $error_message = "Please enter a valid phone number.";
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
                // Generate password automatically
                $password = generatePasswordFromName($name);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Automatic referral - use partner's partner_id as referral code
                $referral_code = $partner['partner_id'];
                $referred_by_partner = $partner['id'];

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
                        if (sendWelcomeEmail($email, $name, $password, $smtp_settings, $partner['name'])) {
                            $success_message = "User added successfully! Welcome email sent to " . $email;
                        } else {
                            $success_message = "User added successfully! However, there was an issue sending the email.";
                        }
                    } else {
                        $success_message = "User added successfully! However, email could not be sent due to missing SMTP settings.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - WaryCharyCare</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
        }
        .add-user-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .form-header {
            background: linear-gradient(45deg, #28a745, #20c997);
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
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .partner-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }
        .auto-password-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <a href="partner/dashboard.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    <div class="container">
        <div class="add-user-form">
            <div class="form-header">
                <h2><i class="bi bi-person-plus-fill"></i> Add New User</h2>
                <p class="mb-0">Add a user with automatic referral benefits</p>
            </div>
            <div class="form-body">
                <div class="partner-info">
                    <h6><i class="bi bi-person-badge"></i> Partner Information</h6>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($partner['name']); ?></p>
                    <p class="mb-1"><strong>Partner ID:</strong> <?php echo htmlspecialchars($partner['partner_id']); ?></p>
                    <p class="mb-0"><small class="text-muted">New users will be automatically referred by you</small></p>
                </div>

                <div class="auto-password-info">
                    <h6><i class="bi bi-info-circle"></i> Automatic Account Setup</h6>
                    <p class="mb-0">
                        <small>
                            A secure password will be automatically generated based on the user's name and sent via email. 
                            The user can change it after their first login.
                        </small>
                    </p>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name" class="form-label">
                                <i class="bi bi-person"></i> Full Name *
                            </label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope"></i> Email Address *
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">
                                <i class="bi bi-telephone"></i> Phone Number *
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="col-md-6">
                            <label for="gender" class="form-label">
                                <i class="bi bi-gender-ambiguous"></i> Gender
                            </label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label for="state" class="form-label">
                                <i class="bi bi-geo-alt"></i> State
                            </label>
                            <input type="text" class="form-control" id="state" name="state">
                        </div>
                        <div class="col-md-4">
                            <label for="district" class="form-label">
                                <i class="bi bi-geo"></i> District
                            </label>
                            <input type="text" class="form-control" id="district" name="district">
                        </div>
                        <div class="col-md-4">
                            <label for="pincode" class="form-label">
                                <i class="bi bi-mailbox"></i> Pincode
                            </label>
                            <input type="text" class="form-control" id="pincode" name="pincode">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="full_address" class="form-label">
                            <i class="bi bi-house"></i> Full Address
                        </label>
                        <textarea class="form-control" id="full_address" name="full_address" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="user_image" class="form-label">
                            <i class="bi bi-image"></i> Profile Image
                        </label>
                        <input type="file" class="form-control" id="user_image" name="user_image" accept="image/*">
                        <small class="text-muted">Optional: Upload a profile image for the user</small>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="add_user" class="btn btn-success btn-lg">
                            <i class="bi bi-person-plus"></i> Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>