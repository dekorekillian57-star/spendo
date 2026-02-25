<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle order status updates
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $orderId = (int)$_POST['order_id'];
        $newStatus = $_POST['status'] ?? '';
        
        // Validate status
        $validStatuses = ['pending', 'processing', 'completed', 'failed'];
        if (!in_array($newStatus, $validStatuses)) {
            $error = "Invalid status selected.";
        } else {
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $result = $stmt->execute([$newStatus, $orderId]);
            
            if ($result) {
                $success = "Order status updated successfully!";
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = "Failed to update order status. Please try again.";
            }
        }
    }
}

// Handle bulk status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_update') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $orderIds = $_POST['order_ids'] ?? [];
        $newStatus = $_POST['status'] ?? '';
        
        // Validate status
        $validStatuses = ['pending', 'processing', 'completed', 'failed'];
        if (!in_array($newStatus, $validStatuses) || empty($orderIds)) {
            $error = "Invalid status or no orders selected.";
        } else {
            // Update multiple orders
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $params = array_merge([$newStatus], $orderIds);
            
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id IN ($placeholders)");
            $result = $stmt->execute($params);
            
            if ($result) {
                $success = "Selected orders status updated successfully!";
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = "Failed to update orders status. Please try again.";
            }
        }
    }
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $orderId = (int)$_POST['order_id'];
        
        // Delete order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $result = $stmt->execute([$orderId]);
        
        if ($result) {
            $success = "Order deleted successfully!";
            // Regenerate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $error = "Failed to delete order. Please try again.";
        }
    }
}

// Handle bulk deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $orderIds = $_POST['order_ids'] ?? [];
        
        if (empty($orderIds)) {
            $error = "No orders selected for deletion.";
        } else {
            // Delete multiple orders
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id IN ($placeholders)");
            $result = $stmt->execute($orderIds);
            
            if ($result) {
                $success = "Selected orders deleted successfully!";
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = "Failed to delete orders. Please try again.";
            }
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Build query based on filters
$query = "SELECT o.*, p.name as package_name, u.username, u.phone as user_phone 
          FROM orders o
          LEFT JOIN packages p ON o.package_id = p.id
          LEFT JOIN users u ON o.user_id = u.id";
$params = [];

$whereClauses = [];
if ($statusFilter !== 'all') {
    $whereClauses[] = "o.status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchTerm)) {
    $searchTerm = "%$searchTerm%";
    $whereClauses[] = "(o.order_id LIKE ? OR p.name LIKE ? OR u.username LIKE ? OR u.phone LIKE ?)";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(' AND ', $whereClauses);
}

$query .= " ORDER BY o.created_at DESC";

// Get orders with pagination
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM orders o";
if (!empty($whereClauses)) {
    $countQuery .= " WHERE " . implode(' AND ', $whereClauses);
}
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalOrders / $perPage);

// Get paginated orders
$paginatedQuery = $query . " LIMIT ? OFFSET ?";
$paginatedParams = $params;
$paginatedParams[] = $perPage;
$paginatedParams[] = $offset;

$stmt = $pdo->prepare($paginatedQuery);
$stmt->execute($paginatedParams);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management | Ghana Telecom Admin</title>
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
        
        /* Sidebar styles (same as index.php) */
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
        
        /* Main content styles (same as index.php) */
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
        
        /* Orders specific styles */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-bar {
            flex: 1;
            min-width: 250px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .status-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .status-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .status-btn:hover {
            border-color: #1a73e8;
            color: #1a73e8;
        }
        
        .status-btn.active {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }
        
        .status-btn.pending { color: #ffa000; border-color: #ffa000; }
        .status-btn.pending.active { background: #ffa000; color: white; }
        
        .status-btn.processing { color: #1e88e5; border-color: #1e88e5; }
        .status-btn.processing.active { background: #1e88e5; color: white; }
        
        .status-btn.completed { color: #388e3c; border-color: #388e3c; }
        .status-btn.completed.active { background: #388e3c; color: white; }
        
        .status-btn.failed { color: #e53935; border-color: #e53935; }
        .status-btn.failed.active { background: #e53935; color: white; }
        
        .bulk-actions {
            display: none;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: #f5f7fa;
            border-radius: 6px;
            align-items: center;
        }
        
        .bulk-actions.active {
            display: flex;
        }
        
        .bulk-actions label {
            margin-right: 15px;
            font-weight: 500;
            color: #555;
        }
        
        .bulk-actions select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-right: 15px;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(to right, #1a73e8, #0d47a1);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 115, 232, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(to right, #e53935, #b71c1c);
        }
        
        .btn-success {
            background: linear-gradient(to right, #43a047, #1b5e20);
        }
        
        .btn-warning {
            background: linear-gradient(to right, #fb8c00, #ef6c00);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-danger {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            color: #b71c1c;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #1b5e20;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .orders-table th {
            background-color: #f9f9f9;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #555;
            font-size: 14px;
            border-bottom: 2px solid #eee;
        }
        
        .orders-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table tr:hover {
            background-color: #f5f9ff;
        }
        
        .order-id {
            font-weight: 600;
            color: #1a73e8;
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
        
        .status-failed {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .order-package {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .order-customer {
            display: flex;
            align-items: center;
        }
        
        .customer-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: #1a73e8;
            font-weight: 600;
        }
        
        .customer-details {
            line-height: 1.4;
        }
        
        .customer-name {
            font-weight: 500;
        }
        
        .customer-phone {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .order-date {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .order-amount {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .order-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .action-btn.view {
            background-color: #e3f2fd;
            color: #1e88e5;
        }
        
        .action-btn.edit {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .action-btn.delete {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 6px;
            text-decoration: none;
            color: #555;
            border: 1px solid #ddd;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .pagination a:hover {
            background-color: #e3f2fd;
            color: #1e88e5;
            border-color: #1e88e5;
        }
        
        .pagination .current {
            background-color: #1a73e8;
            color: white;
            border-color: #1a73e8;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-state-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .empty-state-text {
            max-width: 400px;
            margin: 0 auto 20px;
            line-height: 1.5;
        }
        
        .order-details-modal {
            max-width: 800px;
        }
        
        .order-detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-detail-label {
            flex: 0 0 180px;
            font-weight: 500;
            color: #555;
        }
        
        .order-detail-value {
            flex: 1;
        }
        
        .recipients-list {
            margin-top: 10px;
        }
        
        .recipient-item {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }
        
        .recipient-item:last-child {
            border-bottom: none;
        }
        
        .bulk-checkbox {
            margin: 0;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .select-all-checkbox {
            margin: 0;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .orders-table {
                display: block;
                overflow-x: auto;
            }
            
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .status-filters {
                justify-content: center;
            }
            
            .order-detail-row {
                flex-direction: column;
            }
            
            .order-detail-label {
                margin-bottom: 5px;
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
                <li><a href="index.php"><i>📊</i> <span>Dashboard</span></a></li>
                <li><a href="orders.php" class="active"><i>🛒</i> <span>Orders</span></a></li>
                <li><a href="packages.php"><i>📦</i> <span>Packages</span></a></li>
                <li><a href="users.php"><i>👥</i> <span>Users</span></a></li>
                <li><a href="logout.php"><i>🚪</i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h2>Orders Management</h2>
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
                <h1 class="page-title">Orders</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="filters-bar">
                    <div class="search-bar">
                        <form method="GET" action="orders.php">
                            <input type="text" name="search" class="search-input" 
                                   placeholder="Search orders by ID, customer, or package..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </form>
                    </div>
                    
                    <div class="status-filters">
                        <a href="orders.php?status=all" class="status-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="orders.php?status=pending" class="status-btn pending <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
                        <a href="orders.php?status=processing" class="status-btn processing <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">Processing</a>
                        <a href="orders.php?status=completed" class="status-btn completed <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
                        <a href="orders.php?status=failed" class="status-btn failed <?php echo $statusFilter === 'failed' ? 'active' : ''; ?>">Failed</a>
                    </div>
                </div>
                
                <form method="POST" action="orders.php" id="bulkActionsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="bulk_update">
                    
                    <div class="bulk-actions" id="bulkActions">
                        <input type="checkbox" class="select-all-checkbox" id="selectAll">
                        <label for="selectAll" id="selectedCount">0 selected</label>
                        
                        <select name="status" id="bulkStatus">
                            <option value="">Change status to...</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                        
                        <button type="submit" class="btn btn-success" id="applyBulkBtn" disabled>Apply</button>
                        
                        <button type="button" class="btn btn-danger" id="deleteSelectedBtn" disabled>Delete Selected</button>
                    </div>
                    
                    <input type="hidden" name="order_ids[]" id="selectedOrderIds" value="">
                    
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🛒</div>
                            <h3 class="empty-state-title">No orders found</h3>
                            <p class="empty-state-text">
                                <?php if ($statusFilter !== 'all'): ?>
                                    There are no <?php echo htmlspecialchars($statusFilter); ?> orders at the moment.
                                <?php else: ?>
                                    There are no orders in the system yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="select-all-checkbox" id="tableSelectAll"></th>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): 
                                    // Parse recipients JSON
                                    $recipients = [];
                                    if (!empty($order['recipients'])) {
                                        $recipients = json_decode($order['recipients'], true);
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            $recipients = [];
                                        }
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="bulk-checkbox" 
                                               name="order_ids[]" 
                                               value="<?php echo $order['id']; ?>"
                                               data-order-id="<?php echo $order['id']; ?>">
                                    </td>
                                    <td class="order-id"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td>
                                        <div class="order-customer">
                                            <?php if (!empty($order['user_id'])): ?>
                                                <div class="customer-avatar">
                                                    <?php 
                                                        $initials = '';
                                                        if (!empty($order['username'])) {
                                                            $names = explode(' ', $order['username']);
                                                            $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                                        } else {
                                                            $initials = 'G';
                                                        }
                                                        echo $initials;
                                                    ?>
                                                </div>
                                                <div class="customer-details">
                                                    <div class="customer-name">
                                                        <?php echo !empty($order['username']) ? htmlspecialchars($order['username']) : 'Guest'; ?>
                                                    </div>
                                                    <?php if (!empty($order['user_phone'])): ?>
                                                        <div class="customer-phone"><?php echo htmlspecialchars($order['user_phone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="customer-avatar">G</div>
                                                <div class="customer-details">
                                                    <div class="customer-name">Guest</div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="order-package"><?php echo htmlspecialchars($order['package_name']); ?></div>
                                        <?php if ($order['quantity'] > 1): ?>
                                            <div style="font-size: 12px; color: #7f8c8d;">x <?php echo $order['quantity']; ?> (Bulk)</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="order-amount">GHS <?php echo number_format($order['total_price'], 2); ?></td>
                                    <td class="order-date"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo htmlspecialchars($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="order-actions">
                                        <button class="action-btn view view-order" 
                                                data-order-id="<?php echo $order['id']; ?>"
                                                data-order='<?php echo htmlspecialchars(json_encode($order)); ?>'
                                                data-recipients='<?php echo htmlspecialchars(json_encode($recipients)); ?>'>
                                            View
                                        </button>
                                        
                                        <form method="POST" action="orders.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" class="action-btn delete" 
                                                    onclick="return confirm('Are you sure you want to delete this order?');">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="orders.php?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>">&laquo;</a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="orders.php?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" 
                                   class="<?php echo $i == $page ? 'current' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="orders.php?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>">&raquo;</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal" id="orderDetailsModal">
        <div class="modal-content order-details-modal">
            <div class="modal-header">
                <h3 class="modal-title">Order Details</h3>
                <button class="close-modal" id="closeOrderModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="order-detail-row">
                    <div class="order-detail-label">Order ID:</div>
                    <div class="order-detail-value" id="orderDetailId">ORD-123456</div>
                </div>
                
                <div class="order-detail-row">
                    <div class="order-detail-label">Customer:</div>
                    <div class="order-detail-value" id="orderDetailCustomer">John Doe</div>
                </div>
                
                <div class="order-detail-row">
                    <div class="order-detail-label">Package:</div>
                    <div class="order-detail-value" id="orderDetailPackage">1GB Data Bundle (MTN)</div>
                </div>
                
                <div class="order-detail-row">
                    <div class="order-detail-label">Quantity:</div>
                    <div class="order-detail-value" id="orderDetailQuantity">1</div>
                </div>
                
                <div class="order-detail-row">
                    <div class="order-detail-label">Recipients:</div>
                    <div class="order-detail-value">
                        <div class="recipients-list" id="orderDetailRecipients">
                            <!-- Recipients will be populated here -->
                        </div>
                    </div>
                </div>
                
                <div class="order-detail-row">
                    <div class="order-detail-label">Total Amount:</div>
                    <div class="order-detail-value" id="orderDetailAmount">GHS 5.00</div>
                </div>
                
                <div class="order-detail-row">
                    <div class="order-detail-label">Status:</div>
                    <div class="order-detail-value">
                        <span class="order-status" id="orderDetailStatus">Pending</span>
                    </div>
                </div>
                
                <div class="order-detail-row">
                    <div class="order-detail-label">Payment Reference:</div>
                    <div class="order-detail-value" id="orderDetailPaymentRef">PAY-789012</div>
                </div>
                
                <div class="order-detail-row">
                    <div class="order-detail-label">Date:</div>
                    <div class="order-detail-value" id="orderDetailDate">Feb 15, 2026 10:30 AM</div>
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST" action="orders.php" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="orderStatusId">
                    
                    <select name="status" id="orderStatusSelect" class="form-select" style="width: auto; display: inline-block; margin-right: 10px;">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                    
                    <button type="submit" class="btn btn-success">Update Status</button>
                </form>
                
                <form method="POST" action="orders.php" style="display: inline; margin-left: 10px;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="order_id" id="deleteOrderId">
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to delete this order?');">
                        Delete Order
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteConfirmationModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="close-modal" id="closeDeleteModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the selected orders? This action cannot be undone.</p>
                <div id="deleteOrderList" style="margin-top: 15px; max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 5px; padding: 10px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" id="cancelDeleteBtn">Cancel</button>
                <form method="POST" action="orders.php" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="bulk_delete">
                    <input type="hidden" name="order_ids[]" id="deleteOrderIds">
                    <button type="submit" class="btn btn-danger">Delete Orders</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal functionality
            const orderDetailsModal = document.getElementById('orderDetailsModal');
            const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
            const closeOrderModalBtn = document.getElementById('closeOrderModal');
            const closeDeleteModalBtn = document.getElementById('closeDeleteModal');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            
            // Close modals
            function closeOrderModal() {
                orderDetailsModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            function closeDeleteModal() {
                deleteConfirmationModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            // Event listeners for modal close buttons
            if (closeOrderModalBtn) {
                closeOrderModalBtn.addEventListener('click', closeOrderModal);
            }
            
            if (closeDeleteModalBtn) {
                closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
            }
            
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', closeDeleteModal);
            }
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === orderDetailsModal) {
                    closeOrderModal();
                }
                if (event.target === deleteConfirmationModal) {
                    closeDeleteModal();
                }
            });
            
            // View order details
            document.querySelectorAll('.view-order').forEach(button => {
                button.addEventListener('click', function() {
                    const order = JSON.parse(this.dataset.order);
                    const recipients = JSON.parse(this.dataset.recipients);
                    
                    // Format date
                    const orderDate = new Date(order.created_at);
                    const formattedDate = orderDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Update modal content
                    document.getElementById('orderDetailId').textContent = order.order_id;
                    document.getElementById('orderDetailPackage').textContent = order.package_name;
                    document.getElementById('orderDetailQuantity').textContent = order.quantity;
                    document.getElementById('orderDetailAmount').textContent = 'GHS ' + parseFloat(order.total_price).toFixed(2);
                    document.getElementById('orderDetailStatus').textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                    document.getElementById('orderDetailStatus').className = 'order-status status-' + order.status;
                    document.getElementById('orderDetailPaymentRef').textContent = order.payment_ref;
                    document.getElementById('orderDetailDate').textContent = formattedDate;
                    
                    // Set customer info
                    if (order.user_id) {
                        document.getElementById('orderDetailCustomer').textContent = order.username || 'Registered User';
                    } else {
                        document.getElementById('orderDetailCustomer').textContent = 'Guest';
                    }
                    
                    // Set order ID for status update form
                    document.getElementById('orderStatusId').value = order.id;
                    document.getElementById('deleteOrderId').value = order.id;
                    
                    // Populate recipients
                    const recipientsList = document.getElementById('orderDetailRecipients');
                    recipientsList.innerHTML = '';
                    
                    if (Array.isArray(recipients) && recipients.length > 0) {
                        recipients.forEach((recipient, index) => {
                            let recipientInfo = '';
                            
                            if (order.package_type === 'data' || order.package_type === 'airtime') {
                                recipientInfo = `Phone: ${recipient.phone || 'N/A'}`;
                            } else if (order.package_type === 'cable') {
                                recipientInfo = `Smart Card: ${recipient.smart_card || 'N/A'}`;
                            } else if (order.package_type === 'result_checker') {
                                recipientInfo = `WhatsApp: ${recipient.whatsapp || 'N/A'}`;
                            } else if (order.package_type === 'afa') {
                                recipientInfo = `MTN Number: ${recipient.mtn_number || 'N/A'}`;
                            }
                            
                            const recipientDiv = document.createElement('div');
                            recipientDiv.className = 'recipient-item';
                            recipientDiv.innerHTML = `
                                <strong>Recipient ${index + 1}:</strong> ${recipientInfo}
                                ${recipient.name ? `<div>Name: ${recipient.name}</div>` : ''}
                                ${recipient.email ? `<div>Email: ${recipient.email}</div>` : ''}
                                ${recipient.dob ? `<div>DOB: ${recipient.dob}</div>` : ''}
                                ${recipient.ghana_card ? `<div>Ghana Card: ${recipient.ghana_card}</div>` : ''}
                            `;
                            recipientsList.appendChild(recipientDiv);
                        });
                    } else {
                        recipientsList.innerHTML = '<div class="recipient-item">No recipient details available</div>';
                    }
                    
                    // Show modal
                    orderDetailsModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            });
            
            // Bulk selection functionality
            const bulkActions = document.getElementById('bulkActions');
            const selectAllCheckbox = document.getElementById('tableSelectAll');
            const bulkCheckboxes = document.querySelectorAll('.bulk-checkbox');
            const selectedCount = document.getElementById('selectedCount');
            const applyBulkBtn = document.getElementById('applyBulkBtn');
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            const selectedOrderIds = document.getElementById('selectedOrderIds');
            const deleteOrderIds = document.getElementById('deleteOrderIds');
            const deleteOrderList = document.getElementById('deleteOrderList');
            
            function updateBulkActions() {
                const checkedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
                
                if (checkedCount > 0) {
                    bulkActions.classList.add('active');
                    selectedCount.textContent = `${checkedCount} selected`;
                    
                    // Update selected order IDs
                    const ids = [];
                    document.querySelectorAll('.bulk-checkbox:checked').forEach(checkbox => {
                        ids.push(checkbox.value);
                    });
                    selectedOrderIds.value = ids.join(',');
                    
                    // Enable buttons
                    applyBulkBtn.disabled = false;
                    deleteSelectedBtn.disabled = false;
                } else {
                    bulkActions.classList.remove('active');
                    selectedCount.textContent = '0 selected';
                    selectedOrderIds.value = '';
                    applyBulkBtn.disabled = true;
                    deleteSelectedBtn.disabled = true;
                }
            }
            
            // Select all checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    bulkCheckboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });
                    updateBulkActions();
                });
            }
            
            // Individual checkboxes
            bulkCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Update "Select All" checkbox
                    const allChecked = bulkCheckboxes.length > 0 && 
                                      Array.from(bulkCheckboxes).every(cb => cb.checked);
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                    }
                    
                    updateBulkActions();
                });
            });
            
            // Bulk status select
            const bulkStatus = document.getElementById('bulkStatus');
            if (bulkStatus) {
                bulkStatus.addEventListener('change', function() {
                    if (this.value && document.querySelectorAll('.bulk-checkbox:checked').length > 0) {
                        document.getElementById('bulkActionsForm').submit();
                    }
                });
            }
            
            // Delete selected orders button
            if (deleteSelectedBtn) {
                deleteSelectedBtn.addEventListener('click', function() {
                    // Get selected order IDs
                    const selectedIds = [];
                    const selectedOrders = [];
                    
                    document.querySelectorAll('.bulk-checkbox:checked').forEach(checkbox => {
                        selectedIds.push(checkbox.value);
                        
                        // Get order details for display
                        const row = checkbox.closest('tr');
                        const orderId = row.querySelector('.order-id').textContent;
                        const package = row.querySelector('.order-package').textContent;
                        const amount = row.querySelector('.order-amount').textContent;
                        
                        selectedOrders.push({
                            id: checkbox.value,
                            orderId: orderId,
                            package: package,
                            amount: amount
                        });
                    });
                    
                    // Populate delete order list
                    deleteOrderList.innerHTML = '';
                    selectedOrders.forEach(order => {
                        const orderDiv = document.createElement('div');
                        orderDiv.className = 'recipient-item';
                        orderDiv.innerHTML = `
                            <strong>${order.orderId}</strong>: ${order.package} - ${order.amount}
                        `;
                        deleteOrderList.appendChild(orderDiv);
                    });
                    
                    // Set order IDs for deletion
                    deleteOrderIds.value = selectedIds.join(',');
                    
                    // Show confirmation modal
                    deleteConfirmationModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            }
        });
    </script>
</body>
</html>