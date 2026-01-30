<!-- sidebar.php -->
<style>
    body {
        margin: 0;
        font-family: "Poppins", sans-serif;
    }
    .sidebar {
        width: 260px;
        height: 100vh;
        background-color: #7c43b9;
        color: #fff;
        overflow-y: auto;
        position: fixed;
        top: 0;
        left: 0;
        transition: all 0.3s ease;
        scrollbar-width: thin;
        scrollbar-color: #bfa2f7 transparent;
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background-color: #bfa2f7;
        border-radius: 10px;
    }

    .sidebar h2 {
        text-align: center;
        padding: 20px 0;
        margin: 0;
        font-size: 22px;
        background: rgba(255,255,255,0.1);
    }

    .menu {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .menu li {
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .menu a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #fff;
        padding: 14px 20px;
        transition: 0.3s;
        font-size: 15px;
    }

    .menu a:hover {
        background: rgba(255,255,255,0.15);
    }

    .menu i {
        margin-right: 12px;
        font-size: 17px;
    }

    .submenu {
        display: none;
        background: rgba(0, 0, 0, 0.15);
    }

    .submenu a {
        padding-left: 45px;
        font-size: 14px;
    }

    .menu li.active > .submenu {
        display: block;
    }

    .toggle-icon {
        margin-left: auto;
        transition: 0.3s;
    }

    .menu li.active .toggle-icon {
        transform: rotate(90deg);
    }
</style>

<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="sidebar">
    <h2><i class="fa-solid fa-gem"></i> Admin Panel</h2>
    <ul class="menu">
        <li><a href="../../admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
        <li><a href="../../admin/manage-orders.php"><i class="fa-solid fa-box"></i> Orders</a></li>

        <!-- Products -->
        <li>
            <a href="#" class="toggle"><i class="fa-solid fa-cube"></i> Products <i class="fa-solid fa-chevron-right toggle-icon"></i></a>
            <ul class="submenu">
                <li><a href="../../admin/manage-products.php">Manage Products</a></li>
                <li><a href="../../admin/add-product.php">Add Product</a></li>
            </ul>
        </li>

        <!-- Partners -->
        <li>
            <a href="#" class="toggle"><i class="fa-solid fa-handshake"></i> Partners <i class="fa-solid fa-chevron-right toggle-icon"></i></a>
            <ul class="submenu">
                <li><a href="../../admin/">Manage Partners</a></li>
                <li><a href="../../admin/partner/manage-earnings.php">Partner Earnings</a></li>
                <li><a href="../../admin/partner-payout.php">Partner Payouts</a></li>
            </ul>
        </li>

        <!-- Senior Partners -->
        <li>
            <a href="#" class="toggle"><i class="fa-solid fa-user-tie"></i> Senior Partners <i class="fa-solid fa-chevron-right toggle-icon"></i></a>
            <ul class="submenu">
                <li><a href="../../admin/senior-partner/manage-senior-partners.php">Manage Senior Partners</a></li>
                <li><a href="../../admin/senior-partner/add-senior-partner.php">Add Senior Partner</a></li>
                <li><a href="../../admin/senior-partner/manage-earnings.php">Senior Partner Payouts</a></li>
            </ul>
        </li>

        <!-- Reports -->
        <li>
            <a href="#" class="toggle"><i class="fa-solid fa-chart-line"></i> Reports <i class="fa-solid fa-chevron-right toggle-icon"></i></a>
            <ul class="submenu">
                <li><a href="../../admin/reports/generate-senior-partner-earning-report.php">Senior Partner Report</a></li>
                <li><a href="../../admin/reports/generate-partner-earning-report.php">Partner Report</a></li>
            </ul>
        </li>

        <!-- Settings -->
        <li>
            <a href="#" class="toggle"><i class="fa-solid fa-gear"></i> Settings <i class="fa-solid fa-chevron-right toggle-icon"></i></a>
            <ul class="submenu">
                <li><a href="../../admin/settings/manage-smtp.php">SMTP Settings</a></li>
                <li><a href="../../admin/settings/manage-razorpay.php">Razorpay Settings</a></li>
            </ul>
        </li>

        <!-- Blogs -->
        <li>
            <a href="#" class="toggle"><i class="fa-solid fa-blog"></i> Blogs <i class="fa-solid fa-chevron-right toggle-icon"></i></a>
            <ul class="submenu">
                <li><a href="#">Add Blog</a></li>
                <li><a href="#">Manage Blog</a></li>
            </ul>
        </li>
    </ul>
</div>

<script>
    // Toggle collapse behaviour
    document.querySelectorAll(".toggle").forEach(item => {
        item.addEventListener("click", e => {
            e.preventDefault();
            let parent = item.parentElement;
            parent.classList.toggle("active");
        });
    });
</script>
