<?php
namespace JoonWeb\EmbedApp;
// Load environment variables
require_once __DIR__ . '/../src/EnvParser.php';
use EnvParser;

// Load the .env file - ADD THIS LINE
EnvParser::load(__DIR__ . '/../.env');

// App Configuration
define('APP_NAME', $_ENV['APP_NAME'] ?? 'My JoonWeb App');
define('APP_VERSION', $_ENV['APP_VERSION'] ?? '1.0.0');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('APP_BASE_URL', isset($_ENV['APP_BASE_URL']) && !empty($_ENV['APP_BASE_URL']) ? $_ENV['APP_BASE_URL'] : base_url());

// JoonWeb API Configuration
define('JOONWEB_CLIENT_ID', $_ENV['JOONWEB_CLIENT_ID'] ?? '');
define('JOONWEB_CLIENT_SECRET', $_ENV['JOONWEB_CLIENT_SECRET'] ?? '');
define('JOONWEB_REDIRECT_URI', $_ENV['JOONWEB_REDIRECT_URI'] ?? '');
define('JOONWEB_API_VERSION', $_ENV['JOONWEB_API_VERSION'] ?? '2024-01');
define('JOONWEB_API_SCOPES', $_ENV['JOONWEB_API_SCOPES'] ?? 'read_products');


// DATABASE Configuration
define('DB_MODULE', $_ENV['DB'] ?? 'sqllite');
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME',  $_ENV['DB_NAME'] ?? '');
define('DB_USER',  $_ENV['DB_USER'] ?? '');
define('DB_PASSWORD',  $_ENV['DB_PASSWORD'] ?? '');


// App Settings
define('PRODUCTS_PER_PAGE', $_ENV['PRODUCTS_PER_PAGE'] ?? 20);

// Validate required credentials
if (empty(JOONWEB_CLIENT_ID) || empty(JOONWEB_CLIENT_SECRET)) {
    die('Missing JoonWeb API credentials. Please check your .env file.');
}

// Error handling
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Enable error logging
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Create logs directory if it doesn't exist
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'], // or "yourdomain.com"
        'secure' => true,                  // always true for Shopify
        'httponly' => true,
        'samesite' => 'None'               // required for embedded apps
    ]);

    session_start();
}

function base_url() {

    // 1️⃣ Protocol
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://';
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? "https://"
            : "http://";
    }

    // 2️⃣ Host
    $host = explode(':', $_SERVER['HTTP_HOST'])[0];

    // 3️⃣ Detect tunnel if host is NOT localhost or an IP
    if ($host !== 'localhost' && !filter_var($host, FILTER_VALIDATE_IP)) {
        $isTunnel = true;    // Cloudflared / Ngrok domain
    } else {
        $isTunnel = false;   // localhost, 127.0.0.1, 192.168.x.x etc.
    }

    // 4️⃣ Port (skip for tunnels)
    if ($isTunnel) {
        $port = '';
    } else {
        $portVal = $_SERVER['SERVER_PORT'];
        $port = ($portVal == 80 || $portVal == 443) ? '' : ':' . $portVal;
    }

    // 5️⃣ Path
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    return $protocol . $host . $port . $path;
}



?>
