<?php
// This file provides quick action links for the admin dashboard.
// It is intended to be included in dashboard.php or similar admin pages.
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-plus-circle-fill quick-action-icon"></i>
                <h5 class="card-title">Add New Product</h5>
                <p class="card-text">Quickly add a new product to your inventory.</p>
                <a href="add-product.php" class="btn btn-primary">Go to Add Product</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-cart-check-fill quick-action-icon"></i>
                <h5 class="card-title">Manage Orders</h5>
                <p class="card-text">View and manage all customer orders.</p>
                <a href="manage-orders.php" class="btn btn-primary">Go to Orders</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-people-fill quick-action-icon"></i>
                <h5 class="card-title">Partner Earnings</h5>
                <p class="card-text">Review and manage partner earnings.</p>
                <a href="partner/manage-earnings.php" class="btn btn-primary">Go to Partner Earnings</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-person-badge-fill quick-action-icon"></i>
                <h5 class="card-title">Add Senior Partner</h5>
                <p class="card-text">Register a new senior partner.</p>
                <a href="senior-partner/add-senior-partner.php" class="btn btn-primary">Go to Add Senior Partner</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-graph-up-arrow quick-action-icon"></i>
                <h5 class="card-title">Generate Reports</h5>
                <p class="card-text">Generate various business reports.</p>
                <a href="reports/generate-partner-earning-report.php" class="btn btn-primary">Go to Reports</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-gear-fill quick-action-icon"></i>
                <h5 class="card-title">Settings</h5>
                <p class="card-text">Configure system settings.</p>
                <a href="settings/manage-razorpay.php" class="btn btn-primary">Go to Settings</a>
            </div>
        </div>
    </div>
</div>

<style>
    .quick-action-icon {
        font-size: 3rem;
        color: #007bff; /* Bootstrap primary color */
        margin-bottom: 15px;
    }
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .card-title {
        font-weight: bold;
        color: #333;
    }
    .card-text {
        color: #666;
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }
</style>