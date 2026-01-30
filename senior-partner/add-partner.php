<?php
require_once '../database/config.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

// Check if senior partner is logged in
if (!isset($_SESSION['senior_partner_id'])) {
    header('Location: senior-partner/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get senior partner info
$stmt = $db->prepare("SELECT * FROM senior_partners WHERE id = :id");
$stmt->execute([':id' => $_SESSION['senior_partner_id']]);
$senior_partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$senior_partner) {
    session_destroy();
    header('Location: senior-partner/login.php');
    exit;
}

// Function to generate an 8-digit alphanumeric ID
function generatePartnerId($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Function to generate a password based on name
function generatePasswordFromName($name) {
    $name = str_replace(' ', '', $name);
    $password = substr($name, 0, 4) . rand(100, 999) . '!';
    return $password;
}

// Function to send welcome email
function sendPartnerEmail($toEmail, $toName, $partnerId, $password, $smtpSettings, $seniorPartnerName) {
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
        $mail->Subject = 'Welcome to WaryCharyCare - Your Partner Account Details';
        $mail->Body    = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <img src="http://localhost:8000/logo/warycharycare.png" alt="WaryCharyCare Logo" style="max-width: 150px;">
                            </div>
                            <h2 style="color: #7c43b9; text-align: center;">Welcome, ' . htmlspecialchars($toName) . '!</h2>
                            <p>You have been invited to join WaryCharyCare as a Partner by <strong>' . htmlspecialchars($seniorPartnerName) . '</strong>.</p>
                            <p>Your partner account has been created successfully with automatic referral benefits.</p>
                            <p>Here are your login details:</p>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 10px;"><strong>Partner ID:</strong> <span style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($partnerId) . '</span></li>
                                <li style="margin-bottom: 10px;"><strong>Password:</strong> <span style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($password) . '</span></li>
                                <li style="margin-bottom: 10px;"><strong>Login URL:</strong> <a href="http://localhost:8000/partner/login.php" style="color: #7c43b9; text-decoration: none;">Click here to login</a></li>
                            </ul>
                            <p>Benefits of your partnership:</p>
                            <ul>
                                <li>Automatic referral tracking and commissions</li>
                                <li>Access to partner dashboard and tools</li>
                                <li>Ability to refer users and earn rewards</li>
                            </ul>
                            <p>Please note:</p>
                            <ul>
                                <li>Please keep your login details secure</li>
                                <li>We recommend changing your password after your first login</li>
                                <li>Your referral code is your Partner ID: <strong>' . htmlspecialchars($partnerId) . '</strong></li>
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_partner'])) {
    $partner_name = trim($_POST['partner_name']);
    $partner_email = trim($_POST['partner_email']);
    $partner_phone = trim($_POST['partner_phone']);
    $partner_gender = $_POST['partner_gender'] ?? null;
    $partner_state = trim($_POST['partner_state']);
    $partner_district = trim($_POST['partner_district']);
    $partner_pincode = trim($_POST['partner_pincode']);
    $partner_full_address = trim($_POST['partner_full_address']);
    $partner_image = null;

    // Basic validation
    if (empty($partner_name) || empty($partner_email) || empty($partner_phone)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($partner_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($partner_phone) < 10) {
        $error_message = "Please enter a valid phone number.";
    }

    // Handle image upload
    if (isset($_FILES['partner_image']) && $_FILES['partner_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "images/partners/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_file_type = strtolower(pathinfo($_FILES['partner_image']['name'], PATHINFO_EXTENSION));
        $new_file_name = uniqid('partner_') . "." . $image_file_type;
        $target_file = $target_dir . $new_file_name;
        $check = getimagesize($_FILES['partner_image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['partner_image']['tmp_name'], $target_file)) {
                $partner_image = $target_file;
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
            $stmt = $db->prepare("SELECT COUNT(*) FROM partners WHERE email = :email OR phone = :phone");
            $stmt->execute([':email' => $partner_email, ':phone' => $partner_phone]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "A partner with this email or phone number already exists.";
            } else {
                $partner_id = generatePartnerId();
                $password = generatePasswordFromName($partner_name);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Automatic referral - use senior partner's partner_id as referral code
                $referral_code = $senior_partner['partner_id'];
                $referred_by_senior_partner = $senior_partner['id'];
                
                $stmt = $db->prepare("INSERT INTO partners (partner_id, name, email, phone, gender, image, state, district, pincode, full_address, password, referral_code, referred_by_senior_partner, earning) VALUES (:partner_id, :name, :email, :phone, :gender, :image, :state, :district, :pincode, :full_address, :password, :referral_code, :referred_by_senior_partner, :earning)");
                $result = $stmt->execute([
                    ':partner_id' => $partner_id,
                    ':earning' => 15.0, // Default 15% earning for partners
                    ':name' => $partner_name,
                    ':email' => $partner_email,
                    ':phone' => $partner_phone,
                    ':gender' => $partner_gender,
                    ':image' => $partner_image,
                    ':state' => $partner_state,
                    ':district' => $partner_district,
                    ':pincode' => $partner_pincode,
                    ':full_address' => $partner_full_address,
                    ':password' => $hashed_password,
                    ':referral_code' => $referral_code,
                    ':referred_by_senior_partner' => $referred_by_senior_partner
                ]);

                if ($result) {
                    // Fetch SMTP settings
                    $stmt = $db->query("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
                    $smtp_settings = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($smtp_settings) {
                        if (sendPartnerEmail($partner_email, $partner_name, $partner_id, $password, $smtp_settings, $senior_partner['name'])) {
                            $success_message = "Partner added successfully! Welcome email sent to " . $partner_email;
                        } else {
                            $success_message = "Partner added successfully! However, there was an issue sending the email.";
                        }
                    } else {
                        $success_message = "Partner added successfully! However, email could not be sent due to missing SMTP settings.";
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
    <title>Add Partner - WaryCharyCare</title>
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
        .add-partner-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0,0,0,0.2);
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
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #6f42c1, #8e44ad);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            font-weight: 600;
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
        .senior-partner-info {
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
    </style>
</head>
<body>
    <a href="senior-partner/dashboard.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    <div class="container">
        <div class="add-partner-form">
            <div class="form-header">
                <h2><i class="bi bi-person-plus-fill"></i> Add New Partner</h2>
                <p class="mb-0">Add a partner with automatic referral benefits</p>
            </div>
            <div class="form-body">
                <div class="senior-partner-info">
                    <h6><i class="bi bi-person-badge"></i> Senior Partner Information</h6>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($senior_partner['name']); ?></p>
                    <p class="mb-1"><strong>Partner ID:</strong> <?php echo htmlspecialchars($senior_partner['partner_id']); ?></p>
                    <p class="mb-0"><small class="text-muted">New partners will be automatically referred by you</small></p>
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
                            <label for="partner_name" class="form-label">
                                <i class="bi bi-person"></i> Full Name *
                            </label>
                            <input type="text" class="form-control" id="partner_name" name="partner_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="partner_email" class="form-label">
                                <i class="bi bi-envelope"></i> Email Address *
                            </label>
                            <input type="email" class="form-control" id="partner_email" name="partner_email" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="partner_phone" class="form-label">
                                <i class="bi bi-telephone"></i> Phone Number *
                            </label>
                            <input type="tel" class="form-control" id="partner_phone" name="partner_phone" required>
                        </div>
                        <div class="col-md-6">
                            <label for="partner_gender" class="form-label">
                                <i class="bi bi-gender-ambiguous"></i> Gender
                            </label>
                            <select class="form-select" id="partner_gender" name="partner_gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label for="partner_state" class="form-label">
                                <i class="bi bi-geo-alt"></i> State
                            </label>
                            <input type="text" class="form-control" id="partner_state" name="partner_state">
                        </div>
                        <div class="col-md-4">
                            <label for="partner_district" class="form-label">
                                <i class="bi bi-geo"></i> District
                            </label>
                            <input type="text" class="form-control" id="partner_district" name="partner_district">
                        </div>
                        <div class="col-md-4">
                            <label for="partner_pincode" class="form-label">
                                <i class="bi bi-mailbox"></i> Pincode
                            </label>
                            <input type="text" class="form-control" id="partner_pincode" name="partner_pincode">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="partner_full_address" class="form-label">
                            <i class="bi bi-house"></i> Full Address
                        </label>
                        <textarea class="form-control" id="partner_full_address" name="partner_full_address" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="partner_image" class="form-label">
                            <i class="bi bi-image"></i> Profile Image
                        </label>
                        <input type="file" class="form-control" id="partner_image" name="partner_image" accept="image/*">
                        <small class="text-muted">Optional: Upload a profile image for the partner</small>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="add_partner" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus"></i> Add Partner
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