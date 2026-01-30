<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WaryChary Care</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .topbar {
            background-color: #7c43b9;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .topbar-content {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .topbar-content span {
            margin-right: 20px;
            display: flex;
            align-items: center;
        }
        .topbar-content a {
            color: white;
            text-decoration: none;
            margin-left: 5px;
        }
        .topbar-social a {
            color: white;
            margin-left: 15px;
            text-decoration: none;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: white;
            flex-wrap: wrap;
        }
        .header .logo img {
            height: 50px;
        }
        .header-nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .header-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .header-nav ul li {
            margin-right: 20px;
            position: relative;
        }
        .header-nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            padding: 5px 0;
            display: block;
        }
        .header-nav ul li a:hover {
            color: #7c43b9;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }
        .dropdown-content a:hover {background-color: #f1f1f1;}
        .dropdown:hover .dropdown-content {display: block;}

        .header-buttons {
            display: flex;
            gap: 10px;
        }
        .header-buttons button {
            background-color: #7c43b9;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .header-buttons button:hover {
            background-color: #6a3a9e;
        }
        .hamburger-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            z-index: 1100;
        }
        .hamburger-menu .bar {
            width: 25px;
            height: 3px;
            background-color: #333;
            margin: 4px 0;
            transition: 0.3s;
        }
        
        /* Mobile sidebar */
        .mobile-sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background-color: white;
            z-index: 1050;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
            overflow-y: auto;
            padding-top: 60px;
        }
        
        .mobile-sidebar.active {
            left: 0;
        }
        
        .mobile-sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .mobile-sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
        
        .mobile-sidebar-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #333;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .topbar-content {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 10px;
            }
            .topbar-content span {
                margin-bottom: 5px;
            }
            .header-nav ul {
                display: none;
            }
            .header-buttons {
                display: none;
            }
            .hamburger-menu {
                display: flex;
            }
            
            /* Mobile sidebar menu styles */
            .mobile-sidebar ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .mobile-sidebar ul li {
                border-bottom: 1px solid #eee;
            }
            
            .mobile-sidebar ul li a {
                display: block;
                padding: 15px 20px;
                color: #333;
                text-decoration: none;
                font-weight: bold;
            }
            
            .mobile-sidebar ul li a:hover {
                background-color: #f5f5f5;
                color: #7c43b9;
            }
            
            .mobile-sidebar .header-buttons {
                display: flex;
                flex-direction: column;
                padding: 20px;
                gap: 10px;
            }
            
            .mobile-sidebar .header-buttons button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-content">
            <span>Your One-Stop Solution Design Development Manufacturing</span>
            <span><i class="fas fa-phone"></i> +91-7988472662</span>
            <span><i class="fas fa-envelope"></i> <a href="mailto:Support@WaryChary.com">Support@WaryChary.com</a></span>
        </div>
        <div class="topbar-social">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </div>
    <header class="header">
        <div class="logo">
            <img src="logo/warycharycare.png" alt="Sparkle Logo">
        </div>
        <nav class="header-nav">
            <div class="hamburger-menu" onclick="toggleMenu()">
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
            </div>
            <ul id="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="#">Product</a></li>
                <li><a href="#">Videos</a></li>
                <li><a href="../about-warychary.php">About Us</a></li>
                <li><a href="../period-education.php">Period Education</a></li>
                <li><a href="#">Blogs</a></li>
            </ul>
        </nav>
        <div class="header-buttons">
            <a href="register.php"><button>Login/Register</button></a>
        </div>
    </header>
    
    <!-- Mobile Sidebar -->
    <div class="mobile-sidebar-overlay" id="sidebarOverlay"></div>
    <div class="mobile-sidebar" id="mobileSidebar">
        <div class="mobile-sidebar-close" id="sidebarClose">&times;</div>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="#">Product</a></li>
            <li><a href="#">Videos</a></li>
            <li><a href="../about-warychary.php">About Us</a></li>
            <li><a href="../period-education.php">Period Education</a></li>
            <li><a href="#">Blogs</a></li>
        </ul>
        <div class="header-buttons">
            <a href="register.php"><button>Login/Register</button></a>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const mobileSidebar = document.getElementById('mobileSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            mobileSidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = mobileSidebar.classList.contains('active') ? 'hidden' : '';
        }
        
        document.getElementById('sidebarClose').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
            document.body.style.overflow = '';
        });
        
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
            document.body.style.overflow = '';
        });
    </script>
</body>
</html>