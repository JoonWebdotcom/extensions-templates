<?php
namespace JoonWeb\EmbedApp;

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/DBSessionManager.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Aisensy.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}


// Initialize classes
$session = new SessionManager();
$fun = new Fun();
$aisensy = new Aisensy();

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$required_fields = ['site_domain', 'api_key', 'action', 'crfToken'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Validate CRF token
if (!isset($_SESSION['crfToken']) || !hash_equals($_SESSION['crfToken'], $data['crfToken'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate action
if ($data['action'] !== 'save_api_key') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Sanitize inputs using PHP 8.3 filter_var_array
$filtered_data = filter_var_array($data, [
    'site_domain' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'api_key' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'action' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'crfToken' => FILTER_SANITIZE_FULL_SPECIAL_CHARS
], false);

$site_domain = $filtered_data['site_domain'] ?? '';
$api_key = $filtered_data['api_key'] ?? '';

// Additional validation
if (strlen($site_domain) > 255) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Site domain too long']);
    exit;
}


if (!preg_match('/^[a-zA-Z0-9._-]+$/', $site_domain)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid site domain format']);
    exit;
}

try {
    // Validate API key format using Aisensy class
    if (!$aisensy->validateApiKey($api_key)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid API key']);
        exit;
    }

    // Test API key by making a simple request to Aisensy
    $aisensy->setAPIkey($api_key);

    // Save API key to database
    $result = $fun->InsertApiKey($site_domain, $api_key);
    
    if ($result) {
        // Regenerate CRF token for next request (session fixation protection)
        $_SESSION['crfToken'] = bin2hex(random_bytes(32));
        
        // Clear sensitive data from memory
        unset($api_key, $input, $data);
        
        echo json_encode([
            'success' => true, 
            'message' => 'API key saved and validated successfully',
            'newCrfToken' => $_SESSION['crfToken']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save API key to database']);
    }
    
} catch (\Throwable $e) {
    // PHP 8.0+: Use Throwable to catch both Exception and Error
    error_log("Error saving API key: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    
    // Clear sensitive data in case of error
    unset($api_key, $input, $data);
}