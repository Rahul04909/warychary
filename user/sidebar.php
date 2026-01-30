<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center mb-4">
            <img src="../logo/logo.png" alt="WaryChary Logo" height="40" class="warychary-logo">
            <div class="ms-3">
            </div>
        </div>
    </div>
    
    <div class="sidebar-section mb-4">
        <div class="section-title text-muted mb-3">MAIN</div>
        <nav class="sidebar-nav">
            <ul class="list-unstyled">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>DASHBOARD</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-bag"></i>
                        <span>MY ORDERS</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user"></i>
                        <span>PROFILE</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="sidebar-section">
        <div class="section-title text-muted mb-3">SUPPORT</div>
        <nav class="sidebar-nav">
            <ul class="list-unstyled">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'get-support.php' ? 'active' : ''; ?>">
                    <a href="get-support.php" class="nav-link">
                        <i class="fas fa-headset"></i>
                        <span>GET SUPPORT</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>SETTINGS</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>LOG OUT</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<style>
.sidebar {
    width: 250px;
    background: #fff;
    border-right: 1px solid #eee;
    padding: 1.5rem;
    box-shadow: 0 0 10px rgba(0,0,0,0.03);
}

.sidebar-header {
    margin-bottom: 2rem;
}

.warychary-logo {
    height: 40px;
}

.section-title {
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.sidebar-nav .nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.sidebar-nav .nav-link i {
    width: 20px;
    margin-right: 10px;
    font-size: 16px;
    color: #666;
}

.sidebar-nav li.active .nav-link {
    color: var(--primary-color);
    font-weight: 600;
}

.sidebar-nav li.active .nav-link i {
    color: var(--primary-color);
}

.sidebar-nav .nav-link:hover {
    color: var(--primary-color);
}

.sidebar-nav .nav-link:hover i {
    color: var(--primary-color);
}

.sidebar-header {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.sidebar-nav .nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}

.sidebar-nav .nav-link i {
    width: 20px;
    margin-right: 10px;
    font-size: 1.1rem;
}

.sidebar-nav .nav-link span {
    font-size: 0.9rem;
    font-weight: 500;
}

.sidebar-nav .nav-link:hover,
.sidebar-nav .active .nav-link {
    color: #8B5CF6;
}

.mobile-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1031;
    background: transparent;
    border: none;
    color: #333;
    font-size: 1.5rem;
    cursor: pointer;
}

@media (max-width: 768px) {
    .mobile-toggle {
        display: block;
    }
}
</style>

<button class="mobile-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.querySelector('.mobile-toggle');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(event.target) && 
        !mobileToggle.contains(event.target) &&
        sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
    }
});
</script>