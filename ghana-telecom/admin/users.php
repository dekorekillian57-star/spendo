<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle user deletion
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $userId = (int)$_POST['user_id'];
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$userId]);
        
        if ($result) {
            $success = "User deleted successfully!";
            // Regenerate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $error = "Failed to delete user. Please try again.";
        }
    }
}

// Handle bulk deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $userIds = $_POST['user_ids'] ?? [];
        
        if (empty($userIds)) {
            $error = "No users selected for deletion.";
        } else {
            // Delete multiple users
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
            $result = $stmt->execute($userIds);
            
            if ($result) {
                $success = "Selected users deleted successfully!";
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = "Failed to delete users. Please try again.";
            }
        }
    }
}

// Get filter parameters
$searchTerm = $_GET['search'] ?? '';

// Build query based on filters
$query = "SELECT * FROM users";
$params = [];

$whereClauses = [];

if (!empty($searchTerm)) {
    $searchTerm = "%$searchTerm%";
    $whereClauses[] = "(username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(' AND ', $whereClauses);
}

$query .= " ORDER BY created_at DESC";

// Get users with pagination
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM users";
if (!empty($whereClauses)) {
    $countQuery .= " WHERE " . implode(' AND ', $whereClauses);
}
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalUsers / $perPage);

// Get paginated users
$paginatedQuery = $query . " LIMIT ? OFFSET ?";
$paginatedParams = $params;
$paginatedParams[] = $perPage;
$paginatedParams[] = $offset;

$stmt = $pdo->prepare($paginatedQuery);
$stmt->execute($paginatedParams);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | Ghana Telecom Admin</title>
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
        
        /* Users specific styles */
        .search-bar {
            margin-bottom: 25px;
            max-width: 350px;
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
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .users-table th {
            background-color: #f9f9f9;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #555;
            font-size: 14px;
            border-bottom: 2px solid #eee;
        }
        
        .users-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .users-table tr:hover {
            background-color: #f5f9ff;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: #1a73e8;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .user-info {
            line-height: 1.4;
        }
        
        .user-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .user-email {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .user-phone {
            font-size: 14px;
            font-weight: 500;
        }
        
        .user-registered {
            color: #7f8c8d;
            font-size: 13px;
        }
        
        .user-actions {
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
            .users-table {
                display: block;
                overflow-x: auto;
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
                <li><a href="orders.php"><i>🛒</i> <span>Orders</span></a></li>
                <li><a href="packages.php"><i>📦</i> <span>Packages</span></a></li>
                <li><a href="users.php" class="active"><i>👥</i> <span>Users</span></a></li>
                <li><a href="logout.php"><i>🚪</i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h2>Users Management</h2>
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
                <h1 class="page-title">Registered Users</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="search-bar">
                    <form method="GET" action="users.php">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search users by name, email, or phone..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </form>
                </div>
                
                <form method="POST" action="users.php" id="bulkActionsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="bulk_delete">
                    
                    <div class="bulk-actions" id="bulkActions">
                        <input type="checkbox" class="select-all-checkbox" id="selectAll">
                        <label for="selectAll" id="selectedCount">0 selected</label>
                        
                        <button type="button" class="btn btn-danger" id="deleteSelectedBtn" disabled>Delete Selected</button>
                    </div>
                    
                    <input type="hidden" name="user_ids[]" id="selectedUserIds" value="">
                    
                    <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">👥</div>
                            <h3 class="empty-state-title">No users found</h3>
                            <p class="empty-state-text">
                                <?php if (!empty($searchTerm)): ?>
                                    No users match your search criteria.
                                <?php else: ?>
                                    There are no registered users yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="select-all-checkbox" id="tableSelectAll"></th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="bulk-checkbox" 
                                               name="user_ids[]" 
                                               value="<?php echo $user['id']; ?>"
                                               data-user-id="<?php echo $user['id']; ?>">
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="user-avatar">
                                                <?php 
                                                    $initials = '';
                                                    if (!empty($user['username'])) {
                                                        $names = explode(' ', $user['username']);
                                                        $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                                    }
                                                    echo $initials;
                                                ?>
                                            </div>
                                            <div class="user-info">
                                                <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="user-phone"><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td class="user-registered">
                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="user-actions">
                                        <form method="POST" action="users.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="action-btn delete" 
                                                    onclick="return confirm('Are you sure you want to delete this user? All their orders will be preserved with user_id set to NULL.');">
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
                                <a href="users.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>">&laquo;</a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="users.php?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>" 
                                   class="<?php echo $i == $page ? 'current' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="users.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>">&raquo;</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
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
                <p>Are you sure you want to delete the selected users? This action cannot be undone. Note: All orders associated with these users will be preserved, but the user_id will be set to NULL.</p>
                <div id="deleteUserList" style="margin-top: 15px; max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 5px; padding: 10px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" id="cancelDeleteBtn">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Users</button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal functionality
            const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
            const closeDeleteModalBtn = document.getElementById('closeDeleteModal');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            // Close delete modal
            function closeDeleteModal() {
                deleteConfirmationModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            // Event listeners for modal close buttons
            if (closeDeleteModalBtn) {
                closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
            }
            
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', closeDeleteModal);
            }
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === deleteConfirmationModal) {
                    closeDeleteModal();
                }
            });
            
            // Bulk selection functionality
            const bulkActions = document.getElementById('bulkActions');
            const selectAllCheckbox = document.getElementById('tableSelectAll');
            const bulkCheckboxes = document.querySelectorAll('.bulk-checkbox');
            const selectedCount = document.getElementById('selectedCount');
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            const selectedUserIds = document.getElementById('selectedUserIds');
            const deleteUserList = document.getElementById('deleteUserList');
            
            function updateBulkActions() {
                const checkedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
                
                if (checkedCount > 0) {
                    bulkActions.classList.add('active');
                    selectedCount.textContent = `${checkedCount} selected`;
                    
                    // Update selected user IDs
                    const ids = [];
                    document.querySelectorAll('.bulk-checkbox:checked').forEach(checkbox => {
                        ids.push(checkbox.value);
                    });
                    selectedUserIds.value = ids.join(',');
                    
                    // Enable buttons
                    deleteSelectedBtn.disabled = false;
                } else {
                    bulkActions.classList.remove('active');
                    selectedCount.textContent = '0 selected';
                    selectedUserIds.value = '';
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
            
            // Delete selected users button
            if (deleteSelectedBtn) {
                deleteSelectedBtn.addEventListener('click', function() {
                    // Get selected user IDs
                    const selectedIds = [];
                    const selectedUsers = [];
                    
                    document.querySelectorAll('.bulk-checkbox:checked').forEach(checkbox => {
                        selectedIds.push(checkbox.value);
                        
                        // Get user details for display
                        const row = checkbox.closest('tr');
                        const username = row.querySelector('.user-name').textContent;
                        const email = row.querySelector('.user-email').textContent;
                        
                        selectedUsers.push({
                            id: checkbox.value,
                            username: username,
                            email: email
                        });
                    });
                    
                    // Populate delete user list
                    deleteUserList.innerHTML = '';
                    selectedUsers.forEach(user => {
                        const userDiv = document.createElement('div');
                        userDiv.className = 'recipient-item';
                        userDiv.innerHTML = `
                            <strong>${user.username}</strong> (${user.email})
                        `;
                        deleteUserList.appendChild(userDiv);
                    });
                    
                    // Show confirmation modal
                    deleteConfirmationModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            }
            
            // Confirm delete button
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    // Submit the form
                    document.getElementById('bulkActionsForm').submit();
                });
            }
        });
    </script>
</body>
</html>