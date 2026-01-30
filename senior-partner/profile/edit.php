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

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $state = trim($_POST['state']);
    $district = trim($_POST['district']);
    $full_address = trim($_POST['full_address']);
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/senior-partners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'senior_partner_' . $senior_partner_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/senior-partners/' . $new_filename;
            }
        } else {
            $error_message = "Please upload a valid image file (JPG, JPEG, PNG, GIF).";
        }
    }
    
    // Validation
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error_message = "Please enter a valid 10-digit phone number.";
    } else {
        try {
            // Check if email already exists for another user
            $email_check = $conn->prepare("SELECT id FROM senior_partners WHERE email = ? AND id != ?");
            $email_check->execute([$email, $senior_partner_id]);
            
            if ($email_check->fetch()) {
                $error_message = "This email address is already registered with another account.";
            } else {
                // Build update query
                $update_fields = "name = ?, email = ?, phone = ?, gender = ?, state = ?, district = ?, full_address = ?";
                $params = [$name, $email, $phone, $gender, $state, $district, $full_address];
                
                if ($image_path) {
                    $update_fields .= ", image = ?";
                    $params[] = $image_path;
                }
                
                $params[] = $senior_partner_id;
                
                $update_stmt = $conn->prepare("UPDATE senior_partners SET $update_fields WHERE id = ?");
                $update_stmt->execute($params);
                
                // Update session data
                $_SESSION['senior_partner_name'] = $name;
                $_SESSION['senior_partner_email'] = $email;
                
                $success_message = "Profile updated successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile. Please try again.";
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// Fetch current profile data
$profile_data = null;
try {
    $stmt = $conn->prepare("SELECT * FROM senior_partners WHERE id = ?");
    $stmt->execute([$senior_partner_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching profile data: " . $e->getMessage());
    $error_message = "Error loading profile data.";
}

// Indian states array
$indian_states = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 
    'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 
    'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 
    'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 
    'Uttarakhand', 'West Bengal', 'Delhi', 'Jammu and Kashmir', 'Ladakh'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Senior Partner</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/senior-partner-style.css" rel="stylesheet">
    
    <style>
        .profile-image-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e9ecef;
        }
        
        .image-upload-container {
            position: relative;
            display: inline-block;
        }
        
        .image-upload-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid white;
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
        
        .profile-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 1.5rem;
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
                        <h1 class="mb-2"><i class="bi bi-person-gear me-2"></i>Edit Profile</h1>
                        <p class="text-muted">Update your personal information and profile settings</p>
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

            <?php if ($profile_data): ?>
            <div class="row">
                <!-- Profile Summary -->
                <div class="col-lg-4 mb-4">
                    <div class="profile-stats text-center">
                        <div class="mb-3">
                            <?php if ($profile_data['image']): ?>
                                <img src="../../<?php echo htmlspecialchars($profile_data['image']); ?>" 
                                     alt="Profile" class="profile-image-preview">
                            <?php else: ?>
                                <div class="profile-image-preview bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4><?php echo htmlspecialchars($profile_data['name']); ?></h4>
                        <p class="mb-2"><?php echo htmlspecialchars($profile_data['email']); ?></p>
                        <p class="mb-3">Partner ID: <?php echo htmlspecialchars($profile_data['partner_id']); ?></p>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end border-light">
                                    <h5>₹<?php echo number_format($profile_data['earning'], 2); ?></h5>
                                    <small>Total Earnings</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5><?php echo date('M Y', strtotime($profile_data['created_at'])); ?></h5>
                                <small>Member Since</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-pencil-square me-2"></i>Personal Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="profileForm">
                                <div class="row">
                                    <!-- Profile Image Upload -->
                                    <div class="col-12 mb-4">
                                        <label class="form-label">Profile Image</label>
                                        <div class="d-flex align-items-center">
                                            <div class="image-upload-container me-3">
                                                <?php if ($profile_data['image']): ?>
                                                    <img src="../../<?php echo htmlspecialchars($profile_data['image']); ?>" 
                                                         alt="Profile" class="profile-image-preview" id="imagePreview">
                                                <?php else: ?>
                                                    <div class="profile-image-preview bg-light d-flex align-items-center justify-content-center" id="imagePreview">
                                                        <i class="bi bi-person fs-1 text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <label for="image" class="image-upload-overlay">
                                                    <i class="bi bi-camera"></i>
                                                </label>
                                            </div>
                                            <div>
                                                <input type="file" class="form-control" id="image" name="image" 
                                                       accept="image/*" style="display: none;">
                                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                                        onclick="document.getElementById('image').click();">
                                                    <i class="bi bi-upload me-1"></i>Choose Image
                                                </button>
                                                <div class="form-text">JPG, JPEG, PNG or GIF (Max 2MB)</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Name -->
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($profile_data['name']); ?>" required>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($profile_data['email']); ?>" required>
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($profile_data['phone']); ?>" 
                                               pattern="[0-9]{10}" placeholder="10-digit mobile number">
                                    </div>

                                    <!-- Gender -->
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo ($profile_data['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($profile_data['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($profile_data['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>

                                    <!-- State -->
                                    <div class="col-md-6 mb-3">
                                        <label for="state" class="form-label">State</label>
                                        <select class="form-select" id="state" name="state">
                                            <option value="">Select State</option>
                                            <?php foreach ($indian_states as $state): ?>
                                                <option value="<?php echo $state; ?>" 
                                                        <?php echo ($profile_data['state'] === $state) ? 'selected' : ''; ?>>
                                                    <?php echo $state; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- District -->
                                    <div class="col-md-6 mb-3">
                                        <label for="district" class="form-label">District</label>
                                        <input type="text" class="form-control" id="district" name="district" 
                                               value="<?php echo htmlspecialchars($profile_data['district']); ?>" 
                                               placeholder="Enter your district">
                                    </div>

                                    <!-- Full Address -->
                                    <div class="col-12 mb-4">
                                        <label for="full_address" class="form-label">Full Address</label>
                                        <textarea class="form-control" id="full_address" name="full_address" 
                                                  rows="3" placeholder="Enter your complete address"><?php echo htmlspecialchars($profile_data['full_address']); ?></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Unable to load profile data. Please try again later.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/senior-partner.js"></script>
    
    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="profile-image-preview">`;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            
            if (phone && !/^[0-9]{10}$/.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit phone number');
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