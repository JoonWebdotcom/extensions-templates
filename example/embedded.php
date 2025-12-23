<?php
namespace JoonWeb\EmbedApp;
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/src/JoonWebAPI.php';
require_once __DIR__ . '/src/DBSessionManager.php';
require_once __DIR__ .'/config/database.php';
$session = new SessionManager();
$api = new JoonWebAPI();

// Handle health check separately
if (isset($_GET['health_check'])) {
    header('Content-Type: text/plain');
    echo 'OK';
    exit;
}

function verifyJoonWebHmac($params) {
    $hmac = $params['hmac'] ?? '';
    unset($params['hmac']);
    
    ksort($params);
    $message = http_build_query($params);
    $calculated_hmac = hash_hmac('sha256', $message, JOONWEB_CLIENT_SECRET);

    return hash_equals($hmac, $calculated_hmac);
}

function getStoredTokenFromDatabase($site_domain) {
    global $session;
    $data = $session->getSiteFromDatabase($site_domain);

    
    if($data){
        return [
            'access_token' => $data['access_token'],
            'scope' => !empty($data['scope']) ? explode(",",$data['scope']) : [],
            'expires_in' => 9999999999999999,
            'associated_user' => [
                'id' => 123456,
                'email' => 'user@example.com'
            ]
        ];
    } else {   
        return false;   
    }
}


// Check if we have JoonWeb embedded parameters
if (isset($_GET['session']) && isset($_GET['id_token']) && isset($_GET['site'])) {
    
    // Verify HMAC first
    if (!verifyJoonWebHmac($_GET)) {
        die('Invalid HMAC signature');
    }
    
    // Decode and verify ID token
    $id_token = json_decode(base64_decode($_GET['id_token']), true);
    
    if ($id_token && $id_token['exp'] > time()) {
        // Valid session - get token from database using site parameter
        $token_data = getStoredTokenFromDatabase($_GET['site']);
        
        if ($token_data) {
            $session->startSession($_GET['site'], $token_data);

            
            
            // Store JoonWeb session info
            $_SESSION['joonweb_session'] = $_GET['session'];
            $_SESSION['joonweb_user'] = $id_token['sub'] ?? null;
        }else{
            exit("Invalid Token");
        }
    }else{
        exit("Expired");
    }
}


// TEMPORARY: Bypass authentication for testing - REMOVE LATER
if (!$session->isAuthenticated() && $session->isEmbeddedRequest()) {
   die("Invalid Sessionx");
}

// Check if user is authenticated
if (!$session->isAuthenticated()) {
    if ($session->isEmbeddedRequest()) {
        // REMOVED: echo "App is Loaded"; - This was causing the loop
        include 'views/embedded/loading.php';
    } else {
       include 'views/embedded/loading.php';
    }
    exit;
}

// Rest of your normal flow...
$api->setAccessToken($session->getAccessToken());
$api->setSiteDomain($session->getSiteDomain());

try {
    $site = $api->getSite();

} catch (Exception $e) {
    $session->destroySession();
    if ($session->isEmbeddedRequest()) {
        die('Session expired');
    } else {
        die('Session expired iframe');
    }
    exit;
}

// Handle routing
$page = $_GET['page'] ?? 'dashboard';
$valid_pages = ['dashboard', 'settings', 'automation'];

if (!in_array($page, $valid_pages)) {
    $page = 'dashboard';
}

// Set security headers for embedding
header("Content-Security-Policy: frame-ancestors https://*.joonweb.com");
header("X-Frame-Options: ALLOW-FROM https://accounts.joonweb.com");

// Started Development of App::
require_once __DIR__ .'/src/functions.php';
$fun = new Fun();

// Load the appropriate view
$view_file = "views/embedded/{$page}.php";
if (file_exists($view_file)) {
    include $view_file;
} else {
    include 'views/embedded/dashboard.php';
}