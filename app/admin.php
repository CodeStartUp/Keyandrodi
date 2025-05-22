<?php
// Set a custom session path if default isn't writable
$sessionPath = '/tmp/sessions'; // Linux/Unix
// $sessionPath = 'C:\Windows\Temp\sessions'; // Windows

// Create directory if it doesn't exist
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

// Configure session settings
ini_set('session.save_path', $sessionPath);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 1440); // 24 minutes

// Prevent session ID in URLs
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

// Start session with error handling
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch (Exception $e) {
    error_log("Session start failed: " . $e->getMessage());
    die("Unable to start session. Please try again later.");
}

require 'db_config.php';

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect to login if not authenticated
if (!isAdminLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
