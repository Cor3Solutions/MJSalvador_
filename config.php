<?php
// ==========================================================
// 1. START SESSION AT THE VERY TOP (CRITICAL FIX)
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); 
define('DB_PASS', ''); 
define('DB_NAME', 'jades'); 

// Create database connection with persistent connection for better performance
function getDBConnection()
{
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true // Performance improvement
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Security function to sanitize output
function h($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// CSRF Token Generation
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate Limiting Function (Simple Implementation)
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 300)
{
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $current_time = time();
    $key = md5($identifier);
    
    // Clean old entries
    if (isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = array_filter(
            $_SESSION['rate_limit'][$key],
            function($timestamp) use ($current_time, $time_window) {
                return ($current_time - $timestamp) < $time_window;
            }
        );
    } else {
        $_SESSION['rate_limit'][$key] = [];
    }
    
    // Check if limit exceeded
    if (count($_SESSION['rate_limit'][$key]) >= $max_attempts) {
        return false;
    }
    
    // Add new attempt
    $_SESSION['rate_limit'][$key][] = $current_time;
    return true;
}
?>