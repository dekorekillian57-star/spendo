<?php
/**
 * Database Connection and Helper Functions
 * 
 * Establishes and manages the database connection using PDO.
 * Provides utility functions for database operations.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to determine ABSPATH
    if (!isset($abspath_defined)) {
        $abspath_defined = true;
        define('ABSPATH', dirname(dirname(__FILE__)));
    } else {
        die('Direct access not permitted');
    }
}

require_once __DIR__ . '/config.php';

// Create a PDO instance for database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // In development, show a more detailed error
    if ($_SERVER['SERVER_NAME'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === false) {
        die("A database error occurred. Please try again later.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Execute a query with parameters
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return PDOStatement Executed statement
 */
function dbQuery($sql, $params = []) {
    global $pdo;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt;
}

/**
 * Fetch all rows from a query
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array Array of rows
 */
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Fetch a single row from a query
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array|null Single row or null if not found
 */
function dbFetchRow($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Fetch a single value from a query
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return mixed Value or null if not found
 */
function dbFetchValue($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Insert a record into a table
 * 
 * @param string $table Table name
 * @param array $data Data to insert
 * @return int|bool ID of inserted record or false on failure
 */
function dbInsert($table, $data) {
    global $pdo;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute(array_values($data));
    
    if ($success) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

/**
 * Update records in a table
 * 
 * @param string $table Table name
 * @param array $data Data to update
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return bool True on success, false on failure
 */
function dbUpdate($table, $data, $where, $params = []) {
    global $pdo;
    
    $set = [];
    foreach (array_keys($data) as $field) {
        $set[] = "$field = ?";
    }
    $set = implode(', ', $set);
    
    $sql = "UPDATE $table SET $set WHERE $where";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute(array_merge(array_values($data), $params));
    
    return $success;
}

/**
 * Delete records from a table
 * 
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return bool True on success, false on failure
 */
function dbDelete($table, $where, $params = []) {
    global $pdo;
    
    $sql = "DELETE FROM $table WHERE $where";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);
    
    return $success;
}

/**
 * Escape a string for safe SQL usage (though prepared statements are preferred)
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function dbEscape($string) {
    global $pdo;
    return $pdo->quote($string);
}

/**
 * Begin a transaction
 * 
 * @return bool True on success, false on failure
 */
function dbBeginTransaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

/**
 * Commit a transaction
 * 
 * @return bool True on success, false on failure
 */
function dbCommit() {
    global $pdo;
    return $pdo->commit();
}

/**
 * Rollback a transaction
 * 
 * @return bool True on success, false on failure
 */
function dbRollback() {
    global $pdo;
    return $pdo->rollBack();
}

/**
 * Check if a table exists
 * 
 * @param string $tableName Table name
 * @return bool True if table exists, false otherwise
 */
function dbTableExists($tableName) {
    global $pdo;
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tableName]);
    return $stmt->fetch() !== false;
}

/**
 * Get the last error
 * 
 * @return string Error message
 */
function dbLastError() {
    global $pdo;
    $error = $pdo->errorInfo();
    return $error[2];
}

/**
 * Close the database connection
 * 
 * @return void
 */
function dbClose() {
    global $pdo;
    $pdo = null;
}

/**
 * Initialize database tables if they don't exist
 * 
 * @return void
 */
function initDatabase() {
    global $pdo;
    
    // Check if users table exists
    if (!dbTableExists('users')) {
        $sql = "CREATE TABLE `users` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `email` VARCHAR(100) NOT NULL UNIQUE,
          `password_hash` VARCHAR(255) NOT NULL,
          `phone` VARCHAR(20) NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `last_login` TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
    }
    
    // Check if admin_users table exists
    if (!dbTableExists('admin_users')) {
        $sql = "CREATE TABLE `admin_users` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password_hash` VARCHAR(255) NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
        
        // Create default admin user if none exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $defaultUsername = 'admin';
            $defaultPassword = bin2hex(random_bytes(8)); // Generate a random password
            
            // Hash the password
            $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Insert admin user
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$defaultUsername, $passwordHash]);
            
            // Log the default credentials (only for first-time setup)
            error_log("Default admin credentials created:");
            error_log("Username: $defaultUsername");
            error_log("Password: $defaultPassword");
            error_log("PLEASE CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN!");
        }
    }
    
    // Check if packages table exists
    if (!dbTableExists('packages')) {
        $sql = "CREATE TABLE `packages` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `type` ENUM('data','airtime','cable','result_checker','afa') NOT NULL,
          `name` VARCHAR(100) NOT NULL,
          `price` DECIMAL(10,2) NOT NULL,
          `network` VARCHAR(20) DEFAULT NULL,
          `description` TEXT,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
        
        // Insert default packages
        $defaultPackages = [
            // MTN Data Bundles
            ['data', '1GB', 5.00, 'MTN', '1GB data bundle for MTN network'],
            ['data', '2GB', 10.00, 'MTN', '2GB data bundle for MTN network'],
            ['data', '3GB', 15.00, 'MTN', '3GB data bundle for MTN network'],
            ['data', '4GB', 20.00, 'MTN', '4GB data bundle for MTN network'],
            ['data', '5GB', 25.00, 'MTN', '5GB data bundle for MTN network'],
            ['data', '6GB', 30.00, 'MTN', '6GB data bundle for MTN network'],
            ['data', '8GB', 40.00, 'MTN', '8GB data bundle for MTN network'],
            ['data', '10GB', 45.00, 'MTN', '10GB data bundle for MTN network'],
            ['data', '15GB', 66.00, 'MTN', '15GB data bundle for MTN network'],
            ['data', '20GB', 85.00, 'MTN', '20GB data bundle for MTN network'],
            ['data', '25GB', 108.00, 'MTN', '25GB data bundle for MTN network'],
            ['data', '30GB', 126.00, 'MTN', '30GB data bundle for MTN network'],
            ['data', '40GB', 165.00, 'MTN', '40GB data bundle for MTN network'],
            ['data', '50GB', 197.00, 'MTN', '50GB data bundle for MTN network'],
            ['data', '100GB', 370.00, 'MTN', '100GB data bundle for MTN network'],
            
            // AirtelTigo Data Bundles
            ['data', '1GB', 5.00, 'AirtelTigo', '1GB data bundle for AirtelTigo network'],
            ['data', '2GB', 10.00, 'AirtelTigo', '2GB data bundle for AirtelTigo network'],
            ['data', '3GB', 15.00, 'AirtelTigo', '3GB data bundle for AirtelTigo network'],
            ['data', '4GB', 19.00, 'AirtelTigo', '4GB data bundle for AirtelTigo network'],
            ['data', '5GB', 24.00, 'AirtelTigo', '5GB data bundle for AirtelTigo network'],
            ['data', '6GB', 27.00, 'AirtelTigo', '6GB data bundle for AirtelTigo network'],
            ['data', '8GB', 36.00, 'AirtelTigo', '8GB data bundle for AirtelTigo network'],
            ['data', '10GB', 45.00, 'AirtelTigo', '10GB data bundle for AirtelTigo network'],
            ['data', '15GB', 63.00, 'AirtelTigo', '15GB data bundle for AirtelTigo network'],
            ['data', '20GB', 81.00, 'AirtelTigo', '20GB data bundle for AirtelTigo network'],
            ['data', '30GB', 90.00, 'AirtelTigo', '30GB data bundle for AirtelTigo network'],
            ['data', '40GB', 100.00, 'AirtelTigo', '40GB data bundle for AirtelTigo network'],
            ['data', '50GB', 130.00, 'AirtelTigo', '50GB data bundle for AirtelTigo network'],
            ['data', '100GB', 210.00, 'AirtelTigo', '100GB data bundle for AirtelTigo network'],
            
            // Telecel Data Bundles
            ['data', '5GB', 25.00, 'Telecel', '5GB data bundle for Telecel network'],
            ['data', '10GB', 45.00, 'Telecel', '10GB data bundle for Telecel network'],
            ['data', '15GB', 65.00, 'Telecel', '15GB data bundle for Telecel network'],
            ['data', '20GB', 87.00, 'Telecel', '20GB data bundle for Telecel network'],
            ['data', '30GB', 120.00, 'Telecel', '30GB data bundle for Telecel network'],
            ['data', '50GB', 200.00, 'Telecel', '50GB data bundle for Telecel network'],
            ['data', '100GB', 370.00, 'Telecel', '100GB data bundle for Telecel network'],
            
            // Airtime
            ['airtime', 'Airtime', 5.00, 'MTN', 'MTN airtime'],
            ['airtime', 'Airtime', 5.00, 'AirtelTigo', 'AirtelTigo airtime'],
            ['airtime', 'Airtime', 5.00, 'Telecel', 'Telecel airtime'],
            
            // Cable TV
            ['cable', 'Startimes Basic', 30.00, 'Startimes', 'Startimes Basic package'],
            ['cable', 'Startimes Smart', 50.00, 'Startimes', 'Startimes Smart package'],
            ['cable', 'Startimes Classic', 70.00, 'Startimes', 'Startimes Classic package'],
            ['cable', 'DSTV Compact', 120.00, 'DSTV', 'DSTV Compact package'],
            ['cable', 'DSTV Compact Plus', 180.00, 'DSTV', 'DSTV Compact Plus package'],
            ['cable', 'DSTV Confam', 250.00, 'DSTV', 'DSTV Confam package'],
            ['cable', 'DSTV Premium', 450.00, 'DSTV', 'DSTV Premium package'],
            
            // Result Checkers
            ['result_checker', 'BECE Result Checker', 5.00, NULL, 'Check BECE results'],
            ['result_checker', 'WASSCE Result Checker', 10.00, NULL, 'Check WASSCE results'],
            
            // AFA Registration
            ['afa', 'AFA Registration', 15.00, NULL, 'Ghana Football Association registration']
        ];
        
        foreach ($defaultPackages as $package) {
            $stmt = $pdo->prepare("INSERT INTO packages (type, name, price, network, description) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($package);
        }
    }
    
    // Check if orders table exists
    if (!dbTableExists('orders')) {
        $sql = "CREATE TABLE `orders` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT DEFAULT NULL,
          `order_id` VARCHAR(50) NOT NULL UNIQUE,
          `package_type` ENUM('data','airtime','cable','result_checker','afa') NOT NULL,
          `package_id` INT NOT NULL,
          `quantity` INT NOT NULL DEFAULT 1,
          `recipients` JSON NOT NULL,
          `total_price` DECIMAL(10,2) NOT NULL,
          `status` ENUM('pending','processing','completed','failed') DEFAULT 'pending',
          `payment_ref` VARCHAR(100) NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
    }
    
    // Check if user_cart table exists
    if (!dbTableExists('user_cart')) {
        $sql = "CREATE TABLE `user_cart` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT NOT NULL,
          `package_id` INT NOT NULL,
          `quantity` INT NOT NULL DEFAULT 1,
          `recipients` JSON NOT NULL,
          `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
    }
    
    // Check if login_attempts table exists
    if (!dbTableExists('login_attempts')) {
        $sql = "CREATE TABLE `login_attempts` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `ip_address` VARCHAR(45) NOT NULL,
          `success` TINYINT(1) NOT NULL DEFAULT 0,
          `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
    }
    
    // Check if admin_login_attempts table exists
    if (!dbTableExists('admin_login_attempts')) {
        $sql = "CREATE TABLE `admin_login_attempts` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `ip_address` VARCHAR(45) NOT NULL,
          `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
    }
    
    // Check if admin_login_logs table exists
    if (!dbTableExists('admin_login_logs')) {
        $sql = "CREATE TABLE `admin_login_logs` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `admin_id` INT DEFAULT NULL,
          `ip_address` VARCHAR(45) NOT NULL,
          `success` TINYINT(1) NOT NULL DEFAULT 0,
          `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `logout_time` TIMESTAMP NULL DEFAULT NULL,
          FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
    }
    
    // Check if password_resets table exists
    if (!dbTableExists('password_resets')) {
        $sql = "CREATE TABLE `password_resets` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `email` VARCHAR(100) NOT NULL,
          `token` VARCHAR(255) NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY `email_token` (`email`, `token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
    }
}

// Initialize database tables
initDatabase();