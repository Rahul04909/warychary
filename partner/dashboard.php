<?php
session_start();
if (!isset($_SESSION['partner_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../database/config.php';
$db = new Database();
$conn = $db->getConnection();

// 获取合作伙伴信息和推荐的高级合作伙伴信息
$partner_id = $_SESSION['partner_id'];
$stmt = $conn->prepare("
    SELECT p.name, p.referral_code, p.partner_id as referred_by_senior_partner, sp.name as senior_partner_name 
    FROM partners p 
    LEFT JOIN senior_partners sp ON p.partner_id = sp.id 
    WHERE p.id = ?
");
$stmt->execute([$partner_id]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

// 获取统计数据
$stmt = $conn->prepare("SELECT COUNT(*) as total_referrals FROM users WHERE referred_by_partner = ?");
$stmt->execute([$partner_id]);
$referrals = $stmt->fetch(PDO::FETCH_ASSOC);

// 获取本月收入
$stmt = $conn->prepare("SELECT COALESCE(SUM(earning_amount), 0) as monthly_earnings FROM partner_earnings WHERE partner_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stmt->execute([$partner_id]);
$earnings = $stmt->fetch(PDO::FETCH_ASSOC);

// 获取最近的推荐用户
$stmt = $conn->prepare("SELECT name, created_at FROM users WHERE referred_by_partner = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$partner_id]);
$recent_referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Dashboard - WaryChary Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/partner-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <button class="btn btn-primary d-md-none mb-3" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="content-header">
            <h1 class="text-gradient">Welcome, <?php echo htmlspecialchars($partner['name']); ?>!</h1>
            <p class="text-muted">Here's your dashboard overview</p>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-circle">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?php echo $referrals['total_referrals']; ?></h3>
                    <p>Total Referrals</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-circle">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>₹<?php echo number_format($earnings['monthly_earnings'], 2); ?></h3>
                    <p>Monthly Earnings</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-circle">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3><?php echo $partner['senior_partner_name'] ? htmlspecialchars($partner['senior_partner_name']) : 'Direct Partner'; ?></h3>
                    <p>Referred By</p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Earnings Overview</h5>
                        <canvas id="earningsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Referrals</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_referrals as $referral): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($referral['name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($referral['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // 移动菜单切换
    document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.add('active');
        document.querySelector('.main-content').classList.add('sidebar-active');
    });

    // 收入图表
    const ctx = document.getElementById('earningsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Monthly Earnings',
                data: [0, 0, 0, 0, 0, <?php echo $earnings['monthly_earnings']; ?>],
                borderColor: '#6f42c1',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(111, 66, 193, 0.1)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value;
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>