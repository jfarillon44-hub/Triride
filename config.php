<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'triride_db');

// Connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if (!$conn) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Start session if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global sanitization helper
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Password verification helpers
function verify_password($input, $hash) {
    return password_verify($input, $hash);
}

// Authentication check helper
// Redirects to login page if user is not authenticated or lacks the required role.
function require_login($allowed_roles = []) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        // Find correct path back to root login
        $path_prefix = '';
        if (strpos($_SERVER['PHP_SELF'], '/passenger/') !== false || 
            strpos($_SERVER['PHP_SELF'], '/driver/') !== false || 
            strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
            strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
            $path_prefix = '../';
        }
        header("Location: " . $path_prefix . "login.php");
        exit();
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        $path_prefix = '';
        if (strpos($_SERVER['PHP_SELF'], '/passenger/') !== false || 
            strpos($_SERVER['PHP_SELF'], '/driver/') !== false || 
            strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
            strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
            $path_prefix = '../';
        }
        header("Location: " . $path_prefix . "login.php?error=unauthorized");
        exit();
    }
}

// Get system settings
function get_settings() {
    global $conn;
    $sql = "SELECT * FROM settings WHERE id = 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return [
        'app_name' => 'TriRide',
        'base_fare' => 25.00,
        'per_km_rate' => 5.00
    ];
}
?>
