<?php
ob_start(); // Prevent headers already sent errors

// Include security components
require_once __DIR__ . '/security_config.php';
require_once __DIR__ . '/security.php';

// SQLite Database configuration
define('DB_PATH', __DIR__ . '/../cmstest.db');  // SQLite database file path
define('DB_NAME', 'cmstest');  // Database name for reference

// PRODUCTION Security Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);  // NEVER show errors in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0700, true);
}

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS in production
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
ini_set('session.use_strict_mode', 1);

// Start session with security headers
session_start();

// Set security headers on every request
SecurityHelper::setSecurityHeaders();

// Secure error handler
function secureErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    
    // Log security-relevant errors
    if (strpos($errstr, 'failed') !== false || strpos($errstr, 'denied') !== false) {
        SecurityHelper::logSecurityEvent('PHP_ERROR', "Error: $errstr");
    }
    
    if (ini_get('display_errors')) {
        return false; // Show errors only in development
    }
    
    // In production, show generic message for fatal errors
    if ($errno === E_ERROR || $errno === E_USER_ERROR) {
        die('An error occurred. Please try again later.');
    }
    return true;
}
set_error_handler('secureErrorHandler');

// Connection pool to reuse database connections
static $dbConnection = null;

// Database connection function for SQLite
function getDBConnection() {
    global $dbConnection;
    
    // Return existing connection if available
    if ($dbConnection !== null) {
        return $dbConnection;
    }
    
    try {
        // Create database file if it doesn't exist
        $dbPath = DB_PATH;
        $dbDir = dirname($dbPath);
        
        // Create directory if it doesn't exist
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Connect to SQLite database
        $dbConnection = new PDO("sqlite:" . $dbPath);
        
        // Set error mode to exception
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Enable prepared statement emulation for better performance
        $dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        // Enable foreign key constraints (important for SQLite)
        $dbConnection->exec("PRAGMA foreign_keys = ON");
        
        // Performance optimizations for SQLite
        $dbConnection->exec("PRAGMA synchronous = NORMAL");    // Faster writes, still safe
        $dbConnection->exec("PRAGMA cache_size = 10000");      // 10MB cache (default 2MB)
        $dbConnection->exec("PRAGMA temp_store = MEMORY");     // Store temp tables in RAM
        $dbConnection->exec("PRAGMA journal_mode = WAL");      // Write-Ahead Logging for speed
        $dbConnection->exec("PRAGMA wal_autocheckpoint = 1000"); // Optimize WAL checkpoints
        $dbConnection->exec("PRAGMA busy_timeout = 30000");    // Prevent locks
        $dbConnection->exec("PRAGMA mmap_size = 268435456");   // 256MB memory mapping
        
        return $dbConnection;
    } catch(PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Function to initialize database with schema if needed
function initializeDatabase() {
    $conn = getDBConnection();
    
    // Check if tables exist
    $result = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    
    if ($result->fetchColumn() === false) {
        // Database needs to be initialized
        $sqlFile = __DIR__ . '/../setup_database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $conn->exec($sql);
        }
    }
    
    return $conn;
}

// Query optimization functions
function getPortfolioItemsOptimized($category, $limit = null) {
    global $dbConnection;
    $conn = getDBConnection();
    
    $sql = "
        SELECT p.*, 
               GROUP_CONCAT(m.media_url, '|||') AS media_urls,
               GROUP_CONCAT(m.media_type, '|||') AS media_types,
               COUNT(m.id) as media_count
        FROM portfolio_items p
        LEFT JOIN portfolio_media m ON p.id = m.portfolio_item_id
        WHERE p.category = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$category]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPortfolioStats() {
    global $dbConnection;
    $conn = getDBConnection();
    
    // Use a single query for all stats
    $stmt = $conn->query("
        SELECT 
            category,
            COUNT(DISTINCT p.id) as item_count,
            COUNT(m.id) as media_count,
            MAX(p.created_at) as latest_date
        FROM portfolio_items p
        LEFT JOIN portfolio_media m ON p.id = m.portfolio_item_id
        GROUP BY category
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get page animation settings
function getPageAnimationSettings($pageName) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM page_settings WHERE page_name = ?");
        $stmt->execute([$pageName]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return defaults if no settings found
        if (!$settings) {
            return [
                'refresh_animation' => 'fade',
                'gallery_animation' => 'rotate'
            ];
        }
        
        return [
            'refresh_animation' => $settings['refresh_animation'] ?? 'fade',
            'gallery_animation' => $settings['gallery_animation'] ?? 'rotate'
        ];
    } catch (Exception $e) {
        // Return defaults on error
        return [
            'refresh_animation' => 'fade',
            'gallery_animation' => 'rotate'
        ];
    }
}

// Initialize database on first load and ensure site_settings table exists
initializeDatabase();

// Ensure global site_settings table exists (key/value store)
try {
    $connTZ = getDBConnection();
    $connTZ->exec("CREATE TABLE IF NOT EXISTS site_settings (key TEXT PRIMARY KEY, value TEXT)");
    // Fetch timezone setting
    $stmtTZ = $connTZ->prepare("SELECT value FROM site_settings WHERE key = 'timezone'");
    $stmtTZ->execute();
    $tz = $stmtTZ->fetchColumn();
    if (!$tz) {
        $tz = 'UTC'; // default
    }
    date_default_timezone_set($tz);
} catch (Exception $e) {
    date_default_timezone_set('UTC');
} 