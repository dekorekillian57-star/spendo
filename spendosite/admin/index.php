<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM packages");
$totalPackages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'processing'");
$processingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
$completedOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent orders
$stmt = $pdo->query("SELECT o.*, p.name as package_name, u.username 
                    FROM orders o
                    LEFT JOIN packages p ON o.package_id = p.id
                    LEFT JOIN users u ON o.user_id = u.id
                    ORDER BY o.created_at DESC LIMIT 5");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent admin activity
$stmt = $pdo->query("SELECT * FROM admin_login_logs 
                    ORDER BY login_time DESC LIMIT 5");
$recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Ghana Telecom Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, #0d47a1, #1a237e);
            color: white;
            height: 100vh;
            position: fixed;
            transition: all 0.3s;
            z-index: 100;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .sidebar-logo {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .sidebar-logo img {
            width: 40px;
            margin-right: 10px;
        }
        
        .sidebar-nav {
            padding: 0;
            list-style: none;
            margin: 0;
        }
        
        .sidebar-nav li {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 16px;
        }
        
        .sidebar-nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 500;
        }
        
        .sidebar-nav i {
            margin-right: 12px;
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        /* Main content styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        .header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 99;
        }
        
        .header-left h2 {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .header-right {
            display: flex;
            align-items: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .user-profile img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .content {
            padding: 30px;
        }
        
        .page-title {
            margin-bottom: 25px;
            color: #2c3e50;
        }
        
        /* Dashboard stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            border-left: 4px solid #1a73e8;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }
        
        .stat-card.users {
            border-left-color: #388e3c;
        }
        
        .stat-card.packages {
            border-left-color: #f57c00;
        }
        
        .stat-card.completed {
            border-left-color: #388e3c;
        }
        
        .stat-card-title {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card-change {
            font-size: 13px;
            color: #388e3c;
        }
        
        .stat-card-change.negative {
            color: #e53935;
        }
        
        /* Recent orders and activity */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .view-all {
            color: #1a73e8;
            text-decoration: none;
            font-size: 14px;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        .orders-table, .activity-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th, .orders-table td,
        .activity-table th, .activity-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th, .activity-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        
        .orders-table tr:hover, .activity-table tr:hover {
            background-color: #f5f9ff;
        }
        
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .status-processing {
            background-color: #e3f2fd;
            color: #1e88e5;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .activity-time {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .activity-description {
            font-size: 14px;
        }
        
        .activity-success {
            color: #388e3c;
        }
        
        .activity-failure {
            color: #e53935;
        }
        
        /* Responsive design */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h3, .sidebar-nav span {
                display: none;
            }
            
            .sidebar-nav i {
                margin-right: 0;
                font-size: 24px;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px;
            }
            
            .content {
                padding: 20px;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar-header {
                padding: 15px;
            }
            
            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="../images/logo.png" alt="Logo">
                    <h3>Ghana Telecom</h3>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="index.php" class="active"><i>📊</i> <span>Dashboard</span></a></li>
                <li><a href="orders.php"><i>🛒</i> <span>Orders</span></a></li>
                <li><a href="packages.php"><i>📦</i> <span>Packages</span></a></li>
                <li><a href="users.php"><i>👥</i> <span>Users</span></a></li>
                <li><a href="logout.php"><i>🚪</i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h2>Dashboard</h2>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['admin_username']); ?>&background=1a73e8&color=fff" alt="Profile">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                            <div class="user-role">Administrator</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <h1 class="page-title">Dashboard Overview</h1>
                
                <div class="stats-container">
                    <div class="stat-card users">
                        <div class="stat-card-title">Total Users</div>
                        <div class="stat-card-value"><?php echo number_format($totalUsers); ?></div>
                        <div class="stat-card-change">+5 this week</div>
                    </div>
                    
                    <div class="stat-card packages">
                        <div class="stat-card-title">Total Packages</div>
                        <div class="stat-card-value"><?php echo number_format($totalPackages); ?></div>
                        <div class="stat-card-change">+2 new packages</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-title">Pending Orders</div>
                        <div class="stat-card-value"><?php echo number_format($pendingOrders); ?></div>
                        <div class="stat-card-change <?php echo $pendingOrders > 0 ? 'negative' : ''; ?>">
                            <?php echo $pendingOrders > 0 ? '-' . $pendingOrders . ' to process' : 'All clear'; ?>
                        </div>
                    </div>
                    
                    <div class="stat-card completed">
                        <div class="stat-card-title">Completed Orders</div>
                        <div class="stat-card-value"><?php echo number_format($completedOrders); ?></div>
                        <div class="stat-card-change">+15 this week</div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Orders</h3>
                            <a href="orders.php" class="view-all">View All</a>
                        </div>
                        
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?></td>
                                    <td><?php echo htmlspecialchars($order['package_name']); ?></td>
                                    <td>GHS <?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="order-status 
                                            <?php 
                                                switch($order['status']) {
                                                    case 'pending': echo 'status-pending'; break;
                                                    case 'processing': echo 'status-processing'; break;
                                                    case 'completed': echo 'status-completed'; break;
                                                    default: echo 'status-pending';
                                                }
                                            ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 25px; color: #7f8c8d;">
                                        No recent orders found
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                            <a href="#" class="view-all">View Logs</a>
                        </div>
                        
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivity as $activity): ?>
                                <tr>
                                    <td>
                                        <div class="activity-time">
                                            <?php echo date('M d, h:i A', strtotime($activity['login_time'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="activity-description">
                                            <?php if ($activity['success']): ?>
                                                <span class="activity-success">Login successful</span> from <?php echo htmlspecialchars($activity['ip_address']); ?>
                                            <?php else: ?>
                                                <span class="activity-failure">Failed login attempt</span> from <?php echo htmlspecialchars($activity['ip_address']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($recentActivity)): ?>
                                <tr>
                                    <td colspan="2" style="text-align: center; padding: 25px; color: #7f8c8d;">
                                        No recent activity found
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>