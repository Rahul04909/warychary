<?php
// Initialize senior partner name
$partner_name = "Senior Partner";
?>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <div class="d-flex justify-content-between align-items-center p-3">
            <a href="../../senior-partner/dashboard.php" class="text-white text-decoration-none">
                <span class="fs-4">WaryChary Partner</span>
            </a>
            <button id="close-sidebar" class="btn text-white d-md-none">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="../../senior-partner/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#earningsSubmenu">
                    <i class="bi bi-wallet2"></i>
                    <span>My Earnings</span>
                </a>
                <div class="collapse" id="earningsSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="../../senior-partner/earnings/earnings.php" class="nav-link">
                                <i class="bi bi-circle"></i>
                                <span>Overview</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#referralsSubmenu">
                    <i class="bi bi-people"></i>
                    <span>My Referrals</span>
                </a>
                <div class="collapse" id="referralsSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="../../senior-partner/referrals/referrals.php" class="nav-link">
                                <i class="bi bi-circle"></i>
                                <span>Active Referrals</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../../senior-partner/referrals/pending.php" class="nav-link">
                                <i class="bi bi-circle"></i>
                                <span>Pending Referrals</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#profileSubmenu">
                    <i class="bi bi-person"></i>
                    <span>My Profile</span>
                </a>
                <div class="collapse" id="profileSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="../../senior-partner/profile/edit.php" class="nav-link">
                                <i class="bi bi-circle"></i>
                                <span>Edit Profile</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../../senior-partner/profile/bank-details.php" class="nav-link">
                                <i class="bi bi-circle"></i>
                                <span>Bank Details</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../../senior-partner/profile/change-password.php" class="nav-link">
                                <i class="bi bi-circle"></i>
                                <span>Change Password</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item mt-auto">
                <a href="../../senior-partner/logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>