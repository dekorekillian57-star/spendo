<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        // Rate limiting - prevent brute force
        $ip = $_SERVER['REMOTE_ADDR'];
        $rate_limit = 5; // max attempts
        $time_window = 300; // 5 minutes
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin_login_attempts 
                              WHERE ip_address = ? AND attempt_time > NOW() - INTERVAL ? SECOND");
        $stmt->execute([$ip, $time_window]);
        $attempts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($attempts['count'] >= $rate_limit) {
            $error = "Too many login attempts. Please try again later.";
        } else {
            // Check credentials
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Valid login
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_logged_in'] = true;
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Clear any previous login attempts for this IP
                $stmt = $pdo->prepare("DELETE FROM admin_login_attempts WHERE ip_address = ?");
                $stmt->execute([$ip]);
                
                // Log successful login
                $stmt = $pdo->prepare("INSERT INTO admin_login_logs (admin_id, ip_address, success) 
                                      VALUES (?, ?, 1)");
                $stmt->execute([$admin['id'], $ip]);
                
                // Redirect to dashboard
                header('Location: index.php');
                exit();
            } else {
                // Invalid login
                $error = "Invalid username or password.";
                
                // Log failed attempt
                $stmt = $pdo->prepare("INSERT INTO admin_login_attempts (ip_address, attempt_time) 
                                      VALUES (?, NOW())");
                $stmt->execute([$ip]);
                
                $stmt = $pdo->prepare("INSERT INTO admin_login_logs (admin_id, ip_address, success) 
                                      VALUES (NULL, ?, 0)");
                $stmt->execute([$ip]);
            }
        }
    }
    
    // Generate new CSRF token for next request
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Ghana Telecom Services</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            background: linear-gradient(to right, #1a73e8, #0d47a1);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .login-header p {
            margin-top: 8px;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(to right, #1a73e8, #0d47a1);
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(26, 115, 232, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(26, 115, 232, 0.3);
            background: linear-gradient(to right, #0d62d9, #0a369d);
        }
        
        .btn:active {
            transform: translateY(0);
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
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        .login-footer a {
            color: #1a73e8;
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo img {
            max-width: 120px;
        }
        
        @media (max-width: 480px) {
            .login-form {
                padding: 20px;
            }
            
            .login-header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Ghana Telecom Admin</h1>
            <p>Secure Admin Panel for Telecom Services Management</p>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)); ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           placeholder="Enter your admin username" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn">Login to Dashboard</button>
            </form>
        </div>
        
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> Ghana Telecom Services. All rights reserved.</p>
            <p><a href="../index.php">Back to Website</a></p>
        </div>
    </div>
</body>
</html>