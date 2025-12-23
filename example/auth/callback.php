<?php
namespace JoonWeb\EmbedApp;
require_once '../config/constants.php';
require_once '../src/JoonWebAPI.php';
require_once '../src/DBSessionManager.php';
require_once __DIR__ . '/../config/database.php';


$session = new SessionManager();
$api = new JoonWebAPI();

// Verify HMAC
function verifyHmac($params) {
    $hmac = $params['hmac'];
    unset($params['hmac']);
    
    ksort($params);
    $message = http_build_query($params);
    $calculated_hmac = hash_hmac('sha256', $message, JOONWEB_CLIENT_SECRET);
    
    return hash_equals($hmac, $calculated_hmac);
}

if (!verifyHmac($_GET)) {
    die('Invalid HMAC signature');
}

try {
    // Exchange code for access token
    $token_data = $api->exchangeCodeForToken($_GET['code'], $_GET['site']);
    
    
    // Start session AND store in database
    $session->startSession($_GET['site'], $token_data);
    
    // Get Site info and store it
    $api->setAccessToken($token_data['access_token']);
    $api->setSiteDomain($_GET['site']);
    $site_info = $api->getSite();
    
    // Save Site info to database
    $session->saveAppData($_GET['site'], 'site_info', $site_info['site']);
    
    // Track successful installation
    $session->trackAnalytics($_GET['site'], 'installation_completed', [
        'site_name' => $site_info['site']['name'],
        'email' => $site_info['site']['email']
    ]);
    $site_domain = $_GET['site'];
    // Redirect back to JoonWeb embed URL instead of your embedded.php
    $app_slug = $_GET['app_slug'] ?? JOONWEB_CLIENT_ID;
    $embed_url = buildJoonWebEmbedUrl($site_domain, $app_slug);
    header("Location: " . $embed_url);
    exit;
    
} catch (Exception $e) {
    // Track failed installation
    if (isset($_GET['site'])) {
        $session->trackAnalytics($_GET['site'], 'installation_failed', [
            'error' => $e->getMessage()
        ]);
    }
    
    die('Installation failed. Please try again.');
}

function buildJoonWebEmbedUrl($site_domain, $app_slug) {
    // This should be the URL where JoonWeb will embed your app
    $base_url = "https://accounts.joonweb.com/site/";
    $base_url = $base_url . '?sitehash=' . ($_GET['site_hash'] ?? '') . '&apps&'. urlencode($app_slug);
  
    return $base_url;
}
?>