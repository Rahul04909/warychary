<?php
require_once '../../database/config.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    try {
        // First, get the image path to delete the image file
        $stmt = $db->prepare("SELECT image FROM senior_partners WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
        $senior_partner = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($senior_partner && $senior_partner['image']) {
            $image_path = '../../' . $senior_partner['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Then delete the senior partner from the database
        $stmt = $db->prepare("DELETE FROM senior_partners WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);

        $success_message = "Senior Partner deleted successfully!";
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Fetch all senior partners
try {
    $stmt = $db->prepare("SELECT * FROM senior_partners ORDER BY created_at DESC");
    $stmt->execute();
    $senior_partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $senior_partners = [];
}

include '../sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Senior Partners - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/admin-style.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            border-radius: 15px;
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            background: linear-gradient(45deg, #6f42c1, #8e44ad) !important;
            padding: 1.2rem;
        }
        .card-header h5 {
            color: white !important;
            font-weight: 600;
            margin: 0;
        }
        .card-body {
            padding: 2rem;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .table th,
        .table td {
            vertical-align: middle;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .senior-partner-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay"></div>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Mobile Menu Toggle -->
        <button id="mobile-menu-toggle" class="btn btn-primary d-md-none mb-3">
            <i class="bi bi-list"></i>
        </button>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Manage Senior Partners</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Gender</th>
                                        <th>State</th>
                                        <th>District</th>
                                        <th>Address</th>
                                        <th>Earning (%)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($senior_partners) > 0): ?>
                                        <?php foreach ($senior_partners as $partner): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($partner['partner_id']); ?></td>
                                                <td>
                                                    <?php if ($partner['image']): ?>
                                                        <img src="../../<?php echo htmlspecialchars($partner['image']); ?>" alt="Partner Image" class="senior-partner-image">
                                                    <?php else: ?>
                                                        <img src="../../images/default-user.png" alt="Default Image" class="senior-partner-image">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($partner['name']); ?></td>
                                                <td><?php echo htmlspecialchars($partner['email']); ?></td>
                                                <td><?php echo htmlspecialchars($partner['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($partner['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($partner['state']); ?></td>
                                                <td><?php echo htmlspecialchars($partner['district']); ?></td>
                                                <td><?php echo htmlspecialchars($partner['full_address']); ?></td>
                                                <td><?php echo htmlspecialchars($partner['earning']); ?></td>
                                                <td>
                                                    <a href="?delete_id=<?php echo $partner['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this senior partner?');">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center">No senior partners found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const closeSidebarButton = document.getElementById('close-sidebar');
        const mainContent = document.querySelector('.main-content');

        if (mobileMenuToggle && sidebar && sidebarOverlay && closeSidebarButton && mainContent) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.classList.add('sidebar-active');
                mainContent.classList.add('sidebar-active');
            });

            closeSidebarButton.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-active');
                mainContent.classList.remove('sidebar-active');
            });

            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-active');
                mainContent.classList.remove('sidebar-active');
            });
        }
    });
</script>
</body>
</html>