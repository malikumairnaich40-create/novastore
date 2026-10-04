<?php
/**
 * Application Configuration & Initialization
 */

// Start session if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Environment Constants
define('APP_NAME', 'NovaStore');
define('APP_TAGLINE', 'Premium Multi-Category Store');
define('CURRENCY_SYMBOL', '$');
define('TAX_RATE', 0.08); // 8% sales tax
define('FREE_SHIPPING_THRESHOLD', 100.00);
define('FLAT_SHIPPING_FEE', 9.99);

// Directory Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// Dynamically compute base URL for both XAMPP subfolder and PHP built-in server
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// If running inside a subfolder (like /ecommerce_store or /ecommerce_store/admin), compute root URL
$parts = explode('/', trim($scriptDir, '/'));
$rootSubfolder = '';
if (!empty($parts[0]) && $parts[0] === 'ecommerce_store') {
    $rootSubfolder = '/ecommerce_store';
}

define('BASE_URL', $protocol . $host . $rootSubfolder);

// Include Database
require_once __DIR__ . '/db.php';

// Include Global Functions
require_once BASE_PATH . '/includes/functions.php';
