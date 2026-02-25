<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle package creation
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            // Create new package
            $type = trim($_POST['type'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $network = trim($_POST['network'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            // Validate inputs
            if (empty($type) || empty($name) || $price === false || $price <= 0) {
                $error = "Please fill in all required fields correctly.";
            } else {
                // Insert into database
                $stmt = $pdo->prepare("INSERT INTO packages (type, name, price, network, description) 
                                      VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([$type, $name, $price, $network, $description]);
                
                if ($result) {
                    $success = "Package created successfully!";
                    // Regenerate CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = "Failed to create package. Please try again.";
                }
            }
        } elseif ($action === 'update' && isset($_POST['id'])) {
            // Update existing package
            $id = (int)$_POST['id'];
            $type = trim($_POST['type'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $network = trim($_POST['network'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            // Validate inputs
            if (empty($type) || empty($name) || $price === false || $price <= 0) {
                $error = "Please fill in all required fields correctly.";
            } else {
                // Update in database
                $stmt = $pdo->prepare("UPDATE packages SET type = ?, name = ?, price = ?, network = ?, description = ? 
                                      WHERE id = ?");
                $result = $stmt->execute([$type, $name, $price, $network, $description, $id]);
                
                if ($result) {
                    $success = "Package updated successfully!";
                    // Regenerate CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = "Failed to update package. Please try again.";
                }
            }
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            // Delete package
            $id = (int)$_POST['id'];
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $success = "Package deleted successfully!";
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = "Failed to delete package. Please try again.";
            }
        }
    }
}

// Get all packages
$stmt = $pdo->query("SELECT * FROM packages ORDER BY type, price");
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages Management | Ghana Telecom Admin</title>
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
        
        /* Packages specific styles */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(to right, #1a73e8, #0d47a1);
            color: white;
            border: none;
            padding: 12px 20px;
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
        
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .package-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }
        
        .package-header {
            padding: 20px;
            background: #f5f7fa;
            border-bottom: 1px solid #eee;
        }
        
        .package-type {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .package-type-data { background-color: #e3f2fd; color: #1e88e5; }
        .package-type-airtime { background-color: #e8f5e9; color: #388e3c; }
        .package-type-cable { background-color: #fff8e1; color: #ffa000; }
        .package-type-result_checker { background-color: #f3e5f5; color: #7b1fa2; }
        .package-type-afa { background-color: #ffecb3; color: #ff8f00; }
        
        .package-name {
            font-size: 20px;
            font-weight: 600;
            margin: 10px 0 5px;
            color: #2c3e50;
        }
        
        .package-network {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .package-body {
            padding: 20px;
        }
        
        .package-price {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .package-description {
            color: #555;
            line-height: 1.5;
            margin-bottom: 20px;
            min-height: 60px;
        }
        
        .package-actions {
            display: flex;
            gap: 10px;
        }
        
        .package-actions .btn {
            flex: 1;
            padding: 8px;
            font-size: 13px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .close-modal:hover {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            background-color: white;
            appearance: none;
            background-image: url("image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%237f8c8d' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .search-bar {
            margin-bottom: 25px;
        }
        
        .search-input {
            width: 100%;
            max-width: 350px;
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
        
        .network-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .network-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .network-btn:hover {
            border-color: #1a73e8;
            color: #1a73e8;
        }
        
        .network-btn.active {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
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
                <li><a href="orders.php"><i>🛒</i> <span>Orders</span></a></li>
                <li><a href="packages.php" class="active"><i>📦</i> <span>Packages</span></a></li>
                <li><a href="users.php"><i>👥</i> <span>Users</span></a></li>
                <li><a href="logout.php"><i>🚪</i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h2>Packages Management</h2>
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
                <div class="action-bar">
                    <h1 class="page-title">Service Packages</h1>
                    <button class="btn" id="openAddPackageModal">
                        <i>+</i> Add New Package
                    </button>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="search-bar">
                    <input type="text" class="search-input" id="packageSearch" placeholder="Search packages...">
                </div>
                
                <div class="network-filter">
                    <button class="network-btn active" data-network="all">All Networks</button>
                    <button class="network-btn" data-network="mtn">MTN</button>
                    <button class="network-btn" data-network="airteltigo">AirtelTigo</button>
                    <button class="network-btn" data-network="telecel">Telecel</button>
                    <button class="network-btn" data-network="startimes">Startimes</button>
                    <button class="network-btn" data-network="dstv">DSTV</button>
                </div>
                
                <?php if (empty($packages)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3 class="empty-state-title">No packages found</h3>
                        <p class="empty-state-text">Get started by adding your first service package for customers to purchase.</p>
                        <button class="btn" id="openAddPackageModalEmpty">
                            <i>+</i> Add First Package
                        </button>
                    </div>
                <?php else: ?>
                    <div class="packages-grid" id="packagesGrid">
                        <?php foreach ($packages as $package): ?>
                        <div class="package-card" 
                             data-type="<?php echo htmlspecialchars($package['type']); ?>" 
                             data-network="<?php echo htmlspecialchars(strtolower($package['network'] ?? '')); ?>">
                            <div class="package-header">
                                <span class="package-type package-type-<?php echo htmlspecialchars($package['type']); ?>">
                                    <?php 
                                        $typeNames = [
                                            'data' => 'Data Bundle',
                                            'airtime' => 'Airtime',
                                            'cable' => 'Cable TV',
                                            'result_checker' => 'Result Checker',
                                            'afa' => 'AFA Registration'
                                        ];
                                        echo $typeNames[$package['type']] ?? ucfirst($package['type']);
                                    ?>
                                </span>
                                <h3 class="package-name"><?php echo htmlspecialchars($package['name']); ?></h3>
                                <?php if (!empty($package['network'])): ?>
                                    <div class="package-network"><?php echo htmlspecialchars($package['network']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="package-body">
                                <div class="package-price">GHS <?php echo number_format($package['price'], 2); ?></div>
                                <div class="package-description">
                                    <?php echo !empty($package['description']) ? htmlspecialchars($package['description']) : 'No description available'; ?>
                                </div>
                                <div class="package-actions">
                                    <button class="btn btn-warning edit-package" 
                                            data-id="<?php echo $package['id']; ?>"
                                            data-type="<?php echo $package['type']; ?>"
                                            data-name="<?php echo htmlspecialchars($package['name']); ?>"
                                            data-price="<?php echo $package['price']; ?>"
                                            data-network="<?php echo htmlspecialchars($package['network'] ?? ''); ?>"
                                            data-description="<?php echo htmlspecialchars($package['description'] ?? ''); ?>">
                                        Edit
                                    </button>
                                    <form method="POST" action="packages.php" style="margin:0;flex:1;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this package?');">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Package Modal -->
    <div class="modal" id="addPackageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Package</h3>
                <button class="close-modal" id="closeAddModal">&times;</button>
            </div>
            <form method="POST" action="packages.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="type">Service Type</label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="">Select service type</option>
                            <option value="data">Data Bundle</option>
                            <option value="airtime">Airtime</option>
                            <option value="cable">Cable TV Subscription</option>
                            <option value="result_checker">Result Checker</option>
                            <option value="afa">AFA Registration</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Package Name</label>
                        <input type="text" id="name" name="name" class="form-control" required 
                               placeholder="e.g., 1GB, Basic, WASSCE">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (GHS)</label>
                        <input type="number" id="price" name="price" class="form-control" required 
                               step="0.01" min="0.01" placeholder="e.g., 5.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="network">Network/Provider (Optional)</label>
                        <input type="text" id="network" name="network" class="form-control" 
                               placeholder="e.g., MTN, AirtelTigo, DSTV">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="3" placeholder="Additional details about this package..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelAddModal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Package</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Package Modal -->
    <div class="modal" id="editPackageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Package</h3>
                <button class="close-modal" id="closeEditModal">&times;</button>
            </div>
            <form method="POST" action="packages.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editPackageId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editType">Service Type</label>
                        <select id="editType" name="type" class="form-select" required>
                            <option value="data">Data Bundle</option>
                            <option value="airtime">Airtime</option>
                            <option value="cable">Cable TV Subscription</option>
                            <option value="result_checker">Result Checker</option>
                            <option value="afa">AFA Registration</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editName">Package Name</label>
                        <input type="text" id="editName" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editPrice">Price (GHS)</label>
                        <input type="number" id="editPrice" name="price" class="form-control" required 
                               step="0.01" min="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="editNetwork">Network/Provider (Optional)</label>
                        <input type="text" id="editNetwork" name="network" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="editDescription">Description (Optional)</label>
                        <textarea id="editDescription" name="description" class="form-control" 
                                  rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelEditModal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal functionality
            const addPackageModal = document.getElementById('addPackageModal');
            const editPackageModal = document.getElementById('editPackageModal');
            const openAddModalBtn = document.getElementById('openAddPackageModal');
            const openAddModalEmptyBtn = document.getElementById('openAddPackageModalEmpty');
            const closeAddModalBtn = document.getElementById('closeAddModal');
            const closeEditModalBtn = document.getElementById('closeEditModal');
            const cancelAddModalBtn = document.getElementById('cancelAddModal');
            const cancelEditModalBtn = document.getElementById('cancelEditModal');
            
            // Open Add Modal
            function openAddModal() {
                addPackageModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
            
            // Close Add Modal
            function closeAddModal() {
                addPackageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            // Open Edit Modal with package data
            function openEditModal(package) {
                document.getElementById('editPackageId').value = package.id;
                document.getElementById('editType').value = package.type;
                document.getElementById('editName').value = package.name;
                document.getElementById('editPrice').value = package.price;
                document.getElementById('editNetwork').value = package.network || '';
                document.getElementById('editDescription').value = package.description || '';
                
                editPackageModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
            
            // Close Edit Modal
            function closeEditModal() {
                editPackageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            // Event listeners for modal triggers
            if (openAddModalBtn) {
                openAddModalBtn.addEventListener('click', openAddModal);
            }
            
            if (openAddModalEmptyBtn) {
                openAddModalEmptyBtn.addEventListener('click', openAddModal);
            }
            
            if (closeAddModalBtn) {
                closeAddModalBtn.addEventListener('click', closeAddModal);
            }
            
            if (closeEditModalBtn) {
                closeEditModalBtn.addEventListener('click', closeEditModal);
            }
            
            if (cancelAddModalBtn) {
                cancelAddModalBtn.addEventListener('click', closeAddModal);
            }
            
            if (cancelEditModalBtn) {
                cancelEditModalBtn.addEventListener('click', closeEditModal);
            }
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === addPackageModal) {
                    closeAddModal();
                }
                if (event.target === editPackageModal) {
                    closeEditModal();
                }
            });
            
            // Edit package button functionality
            document.querySelectorAll('.edit-package').forEach(button => {
                button.addEventListener('click', function() {
                    const packageData = {
                        id: this.dataset.id,
                        type: this.dataset.type,
                        name: this.dataset.name,
                        price: this.dataset.price,
                        network: this.dataset.network,
                        description: this.dataset.description
                    };
                    openEditModal(packageData);
                });
            });
            
            // Search functionality
            const searchInput = document.getElementById('packageSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const packages = document.querySelectorAll('.package-card');
                    
                    packages.forEach(package => {
                        const name = package.querySelector('.package-name').textContent.toLowerCase();
                        const description = package.querySelector('.package-description').textContent.toLowerCase();
                        
                        if (name.includes(searchTerm) || description.includes(searchTerm)) {
                            package.style.display = 'block';
                        } else {
                            package.style.display = 'none';
                        }
                    });
                });
            }
            
            // Network filter functionality
            const networkButtons = document.querySelectorAll('.network-btn');
            networkButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active button
                    networkButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const networkFilter = this.dataset.network;
                    const packages = document.querySelectorAll('.package-card');
                    
                    packages.forEach(package => {
                        const packageNetwork = package.dataset.network;
                        
                        if (networkFilter === 'all' || 
                            packageNetwork.includes(networkFilter) ||
                            (networkFilter === 'dstv' && packageNetwork.includes('dstv')) ||
                            (networkFilter === 'startimes' && packageNetwork.includes('startimes'))) {
                            package.style.display = 'block';
                        } else {
                            package.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>