<?php
if (!isset($_SESSION['partner_id'])) {
    header('Location: login.php');
    exit();
}
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <div class="d-flex justify-content-between align-items-center px-3 mb-4">
            <a href="dashboard.php" class="text-white text-decoration-none">
                <h5 class="mb-0">Partner Portal</h5>
            </a>
            <button class="btn btn-link text-white d-md-none" id="mobile-sidebar-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="../../partner/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../../partner/earnings.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'earnings') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-wallet"></i>
                    Earnings
                </a>
            </li>

             <li class="nav-item">
                <a href="../../partner/add-user.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'add-user') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    Add User
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../../partner/referrals/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'referrals') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    Referrals
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../../partner/profile/profile.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'profile') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="../../partner/profile/bank-details.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'bank-details') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    Bank Details
                </a>
            </li>
        </ul>
        
        <div class="mt-auto px-3 py-4">
            <a href="../../partner/logout.php" class="btn btn-light btn-block w-100">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById('mobile-sidebar-close').addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('active');
    document.querySelector('.main-content').classList.remove('sidebar-active');
});
</script>