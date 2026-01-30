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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $state = trim($_POST['state']);
    $district = trim($_POST['district']);
    $pincode = trim($_POST['pincode']);
    $full_address = trim($_POST['full_address']);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($phone)) {
        $error_message = 'Name, email, and phone are required fields.';
    } else {
        try {
            // Check if email is already taken by another partner
            $stmt = $conn->prepare("SELECT id FROM partners WHERE email = ? AND id != ?");
            $stmt->execute([$email, $partner_id]);
            if ($stmt->fetch()) {
                $error_message = 'Email is already registered with another account.';
            } else {
                // Handle image upload
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../../uploads/partners/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = 'partner_' . $partner_id . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            $image_path = 'uploads/partners/' . $new_filename;
                        }
                    } else {
                        $error_message = 'Invalid image format. Please upload JPG, JPEG, PNG, or GIF files only.';
                    }
                }
                
                if (empty($error_message)) {
                    // Update profile
                    $sql = "UPDATE partners SET name = ?, email = ?, phone = ?, gender = ?, state = ?, district = ?, pincode = ?, full_address = ?";
                    $params = [$name, $email, $phone, $gender, $state, $district, $pincode, $full_address];
                    
                    if ($image_path) {
                        $sql .= ", image = ?";
                        $params[] = $image_path;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $partner_id;
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    
                    $success_message = 'Profile updated successfully!';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New password and confirm password do not match.';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'New password must be at least 6 characters long.';
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM partners WHERE id = ?");
            $stmt->execute([$partner_id]);
            $partner_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($current_password, $partner_data['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE partners SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $partner_id]);
                
                $success_message = 'Password changed successfully!';
            } else {
                $error_message = 'Current password is incorrect.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get current partner data
try {
    $stmt = $conn->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->execute([$partner_id]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partner) {
        header('Location: ../login.php');
        exit();
    }
} catch (PDOException $e) {
    $error_message = 'Error loading profile data.';
    $partner = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management - WaryChary Care</title>
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
            <h1 class="text-gradient">Profile Management</h1>
            <p class="text-muted">Manage your profile information and account settings</p>
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
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($partner['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($partner['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($partner['phone'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($partner['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($partner['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($partner['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" name="state" 
                                           value="<?php echo htmlspecialchars($partner['state'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="district" class="form-label">District</label>
                                    <input type="text" class="form-control" id="district" name="district" 
                                           value="<?php echo htmlspecialchars($partner['district'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="pincode" class="form-label">Pincode</label>
                                    <input type="text" class="form-control" id="pincode" name="pincode" 
                                           value="<?php echo htmlspecialchars($partner['pincode'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-12">
                                    <label for="full_address" class="form-label">Full Address</label>
                                    <textarea class="form-control" id="full_address" name="full_address" rows="3"><?php echo htmlspecialchars($partner['full_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <label for="image" class="form-label">Profile Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <div class="form-text">Upload JPG, JPEG, PNG, or GIF files only. Max size: 2MB</div>
                                    <?php if (!empty($partner['image'])): ?>
                                        <div class="mt-2">
                                            <img src="../../<?php echo htmlspecialchars($partner['image']); ?>" 
                                                 alt="Current Profile Image" class="img-thumbnail" style="max-width: 150px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Account Information & Password Change -->
            <div class="col-lg-4">
                <!-- Account Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Account Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted">Partner ID</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($partner['partner_id'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Referral Code</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($partner['referral_code'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Account Status</label>
                            <div>
                                <?php 
                                $status = $partner['status'] ?? 'pending';
                                $status_class = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning');
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-0">
                            <label class="form-label text-muted">Member Since</label>
                            <div class="fw-bold">
                                <?php echo isset($partner['created_at']) ? date('M d, Y', strtotime($partner['created_at'])) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Password Change -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required minlength="6">
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/partner-script.js"></script>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // File size validation
        document.getElementById('image').addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.size > 2 * 1024 * 1024) { // 2MB
                alert('File size must be less than 2MB');
                this.value = '';
            }
        });
    </script>
</body>
</html>