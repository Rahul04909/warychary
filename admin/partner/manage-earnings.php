<?php
include '../../vendor/autoload.php';
include '../../database/config.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle earnings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_earnings'])) {
    foreach ($_POST['earnings_percentage'] as $partner_id => $percentage) {
        $percentage = floatval($percentage);
        if ($percentage < 0 || $percentage > 100) {
            $error_message = "Earnings percentage must be between 0 and 100.";
            break;
        }

        try {
            $stmt = $db->prepare("UPDATE partners SET earning = :earning WHERE id = :id");
            $stmt->execute([
                ':earning' => $percentage,
                ':id' => $partner_id
            ]);
            $success_message = "Earnings percentages updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            break;
        }
    }
}

// Fetch all partners
$partners = [];
try {
    $stmt = $db->query("SELECT id, name, email, earning FROM partners ORDER BY name ASC");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching partners: " . $e->getMessage();
}

include '../sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Partner Earnings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/admin/assets/css/admin-style.css" rel="stylesheet">
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
        .form-label {
            color: #6f42c1;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #6f42c1, #8e44ad);
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .table-responsive {
            margin-top: 1.5rem;
        }
        .table thead th {
            background-color: #f8f9fa;
            color: #6f42c1;
            font-weight: 600;
        }
        .table tbody tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>

<div class="sidebar-overlay"></div>

<div class="main-content">
    <div class="container-fluid">
        <button id="mobile-menu-toggle" class="btn btn-primary d-md-none mb-3">
            <i class="bi bi-list"></i>
        </button>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Manage Partner Earnings</h5>
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

                        <form method="POST">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Current Earnings (%)</th>
                                            <th>New Earnings (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($partners) > 0): ?>
                                            <?php foreach ($partners as $partner): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($partner['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($partner['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($partner['earning']); ?>%</td>
                                                    <td>
                                                        <input type="number" class="form-control" name="earnings_percentage[<?php echo $partner['id']; ?>]" value="<?php echo htmlspecialchars($partner['earning']); ?>" min="0" max="100" step="0.01" required>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4">No partners found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" name="update_earnings" class="btn btn-primary mt-3">Update Earnings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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