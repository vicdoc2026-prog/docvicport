<?php
/**
 * Database Configuration
 * PDO Connection File
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'doc_vic');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// PDO options for better security and error handling
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
    PDO::ATTR_PERSISTENT         => false,                   // Don't use persistent connections
];

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Optional: Set timezone
    $conn->exec("SET time_zone = '+08:00'"); // Philippine Time (GMT+8)
    
} catch (PDOException $e) {
    // Log error to file (recommended for production)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly error (remove in production)
    die("Database connection failed. Please contact the administrator.");
    
    // For development only - shows detailed error:
    // die("Connection failed: " . $e->getMessage());
}

/**
 * Helper function for safe query execution
 * Usage: executeQuery($conn, "SELECT * FROM users WHERE id = ?", [$id]);
 */
function executeQuery($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to fetch single row
 * Usage: $user = fetchOne($conn, "SELECT * FROM users WHERE id = ?", [$id]);
 */
function fetchOne($conn, $sql, $params = []) {
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Helper function to fetch all rows
 * Usage: $users = fetchAll($conn, "SELECT * FROM users");
 */
function fetchAll($conn, $sql, $params = []) {
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

/**
 * Helper function for INSERT/UPDATE/DELETE
 * Usage: $success = executeUpdate($conn, "DELETE FROM users WHERE id = ?", [$id]);
 */
function executeUpdate($conn, $sql, $params = []) {
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt ? $stmt->rowCount() : false;
}

/**
 * Get last inserted ID
 */
function getLastInsertId($conn) {
    return $conn->lastInsertId();
}

// Optional: Test connection (comment out in production)
// echo "Database connected successfully!";
?>