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
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Format the name for display
$fullName = $user ? htmlspecialchars($user['name'] ?? '') : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - WaryChary Care</title>
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
            height: 100%;
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
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 80%;
                max-width: 280px;
            }
        }

        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 60px);
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

        .user-dashboard-header {
            margin-bottom: 20px;
        }

        .user-dashboard-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .user-info-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .user-welcome {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 25px;
            color: #333;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
            word-break: break-word;
        }

        .recent-activities {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--card-shadow);
        }

        .recent-activities h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .notification-icon {
            font-size: 20px;
            color: #666;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        /* Tablet Styles */
        @media (max-width: 992px) {
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                overflow-x: hidden;
                height: 100%;
                position: relative;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1040;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                z-index: 1020;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                width: 80%;
                max-width: 280px;
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
                padding: 70px 15px 15px 15px;
                width: 100%;
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .user-info-card {
                padding: 20px;
                margin-bottom: 20px;
            }

            .recent-activities {
                padding: 20px;
            }

            .user-dashboard-header h1 {
                font-size: 20px;
                margin-top: 10px;
                text-align: center;
            }

            .user-welcome {
                font-size: 16px;
                margin-bottom: 20px;
                text-align: center;
            }
            
            .page-container {
                flex-direction: column;
            }
        }

        /* Small Mobile Styles */
        @media (max-width: 480px) {
            .main-content {
                padding: 70px 10px 10px 10px;
            }

            .user-info-card {
                padding: 15px;
            }

            .recent-activities {
                padding: 15px;
            }

            .info-value {
                font-size: 14px;
            }

            .user-dashboard-header h1 {
                font-size: 18px;
            }
            
            .mobile-menu-toggle {
                top: 10px;
                left: 10px;
                padding: 8px 10px;
                font-size: 16px;
            }
            
            .info-label {
                font-size: 12px;
            }
            
            .recent-activities h2 {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="user-dashboard-header">
                <h1>User Dashboard</h1>
            </div>
            
            <div class="user-info-card">
                <div class="user-welcome">
                    Welcome, <?php echo $fullName; ?>!
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Email:</div>
                        <div class="info-value"><?php echo $user ? htmlspecialchars($user['email'] ?? '') : ''; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Mobile:</div>
                        <div class="info-value"><?php echo $user ? htmlspecialchars($user['mobile'] ?? '08295106402') : '08295106402'; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Address:</div>
                        <div class="info-value"><?php echo $user ? htmlspecialchars($user['address'] ?? 'Near Old Bus Stand, Manesar, Haryana, 122050') : 'Near Old Bus Stand, Manesar, Haryana, 122050'; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Location:</div>
                        <div class="info-value"><?php 
                            $location = '';
                            if (!empty($user['city'])) {
                                $location .= htmlspecialchars($user['city']);
                            }
                            if (!empty($user['state'])) {
                                $location .= (!empty($location) ? ', ' : '') . htmlspecialchars($user['state']);
                            }
                            if (!empty($user['pincode'])) {
                                $location .= (!empty($location) ? ' - ' : '') . htmlspecialchars($user['pincode']);
                            }
                            echo !empty($location) ? $location : 'Jind, Haryana - 122050';
                        ?></div>
                    </div>
                </div>
            </div>

            <div class="recent-activities">
                <h2>Your Recent Activities</h2>
                <div class="alert alert-info">
                    No recent activities to display yet. Check back later!
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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