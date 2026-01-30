<?php
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get user details
$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $state = trim($_POST['state']);
    $district = trim($_POST['district']);
    $pincode = trim($_POST['pincode']);
    $full_address = trim($_POST['full_address']);
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/users/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'user_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'images/users/' . $new_filename;
            }
        } else {
            $error_message = "Please upload a valid image file (JPG, JPEG, PNG, GIF, WEBP).";
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
            $email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $email_check->execute([$email, $userId]);
            
            if ($email_check->fetch()) {
                $error_message = "This email address is already registered with another account.";
            } else {
                // Build update query
                $update_fields = "name = ?, email = ?, phone = ?, gender = ?, state = ?, district = ?, pincode = ?, full_address = ?";
                $params = [$name, $email, $phone, $gender, $state, $district, $pincode, $full_address];
                
                if ($image_path) {
                    $update_fields .= ", image = ?";
                    $params[] = $image_path;
                }
                
                $params[] = $userId;
                
                $update_stmt = $conn->prepare("UPDATE users SET $update_fields WHERE id = ?");
                $update_stmt->execute($params);
                
                $success_message = "Profile updated successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile. Please try again.";
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Format the name for display
$fullName = $user ? htmlspecialchars($user['name'] ?? '') : '';

// Indian states array
$indian_states = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 
    'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 
    'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 
    'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 
    'Uttarakhand', 'West Bengal', 'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Chandigarh', 
    'Dadra and Nagar Haveli and Daman and Diu', 'Lakshadweep', 'Puducherry'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - WaryChary Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8B5CF6;
            --header-bg: #fff;
            --sidebar-bg: #fff;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --card-shadow: 0 2px 10px rgba(0,0,0,0.05);
            --border-radius: 10px;
            --sidebar-width: 250px;
        }
        
        body {
            background: var(--light-bg);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        
        .main-header {
            background: var(--header-bg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            padding: 0.5rem 0;
        }

        .main-header .nav-link {
            color: var(--text-color);
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .main-header .nav-link.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Mobile Menu Toggle Button */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1040;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(139, 92, 246, 0.3);
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle:hover {
            background: #7C3AED;
            transform: translateY(-1px);
        }

        .mobile-menu-toggle.active {
            background: #7C3AED;
        }
        
        /* Hide header on mobile devices */
        @media (max-width: 768px) {
            .main-header {
                display: none !important;
            }
        }
        
        .page-container {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .sidebar {
            background: var(--sidebar-bg);
            border-right: 1px solid #eee;
            padding: 1rem;
            width: var(--sidebar-width);
            height: 100%;
            overflow-y: auto;
            position: sticky;
            top: 0;
            transition: transform 0.3s ease;
            z-index: 1020;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 60px - 50px);
            transition: margin-left 0.3s ease;
        }
        
        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1010;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
        }
        
        footer {
            background: var(--header-bg);
            padding: 15px 0;
            text-align: center;
            border-top: 1px solid #eee;
            position: sticky;
            bottom: 0;
            width: 100%;
            z-index: 1000;
        }

        .profile-header {
            margin-bottom: 20px;
        }

        .profile-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
        }

        .profile-image-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin-bottom: 15px;
        }

        .profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 15px;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
        }

        .btn-update {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 500;
            border-radius: 8px;
        }

        .btn-update:hover {
            background-color: #7C3AED;
            border-color: #7C3AED;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
        }

        .alert-success {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .alert-danger {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        @media (max-width: 768px) {
            body {
                height: auto;
                position: relative;
                overflow-y: auto;
            }
            
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                width: 80%;
                max-width: 280px;
                transform: translateX(-100%);
                z-index: 1020;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
                width: 100%;
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* 改进表单在移动设备上的可访问性 */
            .profile-card {
                padding: 15px;
                overflow-x: hidden;
            }
            
            .form-control, .btn {
                font-size: 16px; /* 增大字体大小，提高可点击性 */
                height: auto;
                padding: 10px 15px;
                margin-bottom: 10px;
            }
            
            select.form-control {
                height: auto;
                padding: 10px 15px;
            }
            
            .btn {
                display: inline-block;
                width: auto;
                min-width: 120px;
                margin: 5px;
                padding: 12px 20px;
                touch-action: manipulation;
            }
            
            /* 确保按钮在移动设备上可点击 */
            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: center;
                width: 100%;
            }
            
            .d-flex.justify-content-between .btn {
                margin: 10px 0;
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Header removed -->
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="profile-header">
                <h1><i class="fas fa-user me-2"></i>My Profile</h1>
                <p class="text-muted">Manage your personal information and preferences</p>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Profile Image Section -->
                        <div class="col-md-4">
                            <div class="profile-image-container">
                                <?php if (!empty($user['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($user['image']); ?>" 
                                         alt="Profile Image" class="profile-image" id="imagePreview">
                                <?php else: ?>
                                    <div class="profile-placeholder" id="imagePreview">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Profile Image</label>
                                    <input type="file" class="form-control" id="image" name="image" 
                                           accept="image/*" onchange="previewImage(this)">
                                    <div class="form-text">Upload JPG, JPEG, PNG, GIF, or WEBP files only. Max size: 2MB</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Profile Form Section -->
                        <div class="col-md-8">
                            <div class="row">
                                <!-- Name -->
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>

                                <!-- Phone -->
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           pattern="[0-9]{10}" placeholder="10-digit mobile number">
                                </div>

                                <!-- Gender -->
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo (isset($user['gender']) && $user['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo (isset($user['gender']) && $user['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo (isset($user['gender']) && $user['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <!-- State -->
                                <div class="col-md-6 mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <select class="form-control" id="state" name="state">
                                        <option value="">Select State</option>
                                        <?php foreach ($indian_states as $state): ?>
                                            <option value="<?php echo $state; ?>" 
                                                    <?php echo (isset($user['state']) && $user['state'] === $state) ? 'selected' : ''; ?>>
                                                <?php echo $state; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- District -->
                                <div class="col-md-6 mb-3">
                                    <label for="district" class="form-label">District</label>
                                    <input type="text" class="form-control" id="district" name="district" 
                                           value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>" 
                                           placeholder="Enter your district">
                                </div>

                                <!-- Pincode -->
                                <div class="col-md-6 mb-3">
                                    <label for="pincode" class="form-label">Pincode</label>
                                    <input type="text" class="form-control" id="pincode" name="pincode" 
                                           value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>" 
                                           pattern="[0-9]{6}" placeholder="6-digit pincode">
                                </div>

                                <!-- Full Address -->
                                <div class="col-12 mb-3">
                                    <label for="full_address" class="form-label">Full Address</label>
                                    <textarea class="form-control" id="full_address" name="full_address" rows="3" 
                                              placeholder="Enter your complete address"><?php echo htmlspecialchars($user['full_address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary btn-update">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Profile Image" class="profile-image">';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    
    // Mobile menu toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        // Toggle sidebar on mobile
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            mobileMenuToggle.classList.toggle('active');
            
            // Change icon
            const icon = mobileMenuToggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
        
        // Close sidebar when clicking overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
            
            // Reset icon
            const icon = mobileMenuToggle.querySelector('i');
            icon.className = 'fas fa-bars';
        });
        
        // Close sidebar when clicking on sidebar links (mobile only)
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    
                    // Reset icon
                    const icon = mobileMenuToggle.querySelector('i');
                    icon.className = 'fas fa-bars';
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                
                // Reset icon
                const icon = mobileMenuToggle.querySelector('i');
                icon.className = 'fas fa-bars';
            }
        });
    });
    </script>
</body>
</html>