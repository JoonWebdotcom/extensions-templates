<?php
namespace JoonWeb\EmbedApp;

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/DBSessionManager.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Aisensy.php';
require_once __DIR__ . '/../src/JoonWebAPI.php';

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

// Start session and initialize classes
$session = new SessionManager();
$fun = new Fun();
$aisensy = new Aisensy();
$api = new JoonWebAPI();
$api->setAccessToken($session->getAccessToken());
$api->setSiteDomain($session->getSiteDomain());

// Get POST data (form data, not JSON)
$data = $_POST;

// Validate required fields for all actions
$required_fields = ['action'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Validate action and route to appropriate handler
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'save_automation':
            handleSaveAutomation($data, $fun, $session);
            break;
            
        // Add more actions here as needed
        case 'delete_automation':
            handleDeleteAutomation($data, $fun, $session);
            break;
            
        case 'toggle_automation_status':
            handleToggleAutomationStatus($data, $fun, $session);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
} catch (\Throwable $e) {
    // PHP 8.0+: Use Throwable to catch both Exception and Error
    error_log("Error in handler: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Handle saving automation data
 */
function handleSaveAutomation($data, $fun, $session) {
    global $api;
    // Validate required fields for automation
    $required_fields = ['name', 'campaign', 'joonweb_event', 'status'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    // Sanitize inputs
    $filtered_data = filter_var_array($data, [
        'automation_id' => FILTER_SANITIZE_NUMBER_INT,
        'name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'campaign' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'joonweb_event' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'status' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'variables' => FILTER_DEFAULT
    ], false);

    $automation_id = isset($filtered_data['automation_id']) && $filtered_data['automation_id'] !== '' ? intval($filtered_data['automation_id']) : null;
    $name = trim($filtered_data['name']);
    $campaign = trim($filtered_data['campaign']);
    $joonweb_event = trim($filtered_data['joonweb_event']);
    $status = trim($filtered_data['status']);
    
    // Get site domain from session
    $site_domain = $_SESSION['site_domain'] ?? '';
    if (empty($site_domain)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Site domain not found in session']);
        exit;
    }

    // Validate status
    $allowed_statuses = ['active', 'draft', 'paused'];
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }

    // Validate JoonWeb event
    $allowed_events = [
        'checkout/updated', 'customers/create', 'orders/create', 
        'orders/confirmed', 'orders/paid', 'orders/shipped', 
        'orders/delivered', 'orders/cancelled'
    ];

    if (!in_array($joonweb_event, $allowed_events)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JoonWeb event']);
        exit;
    }

    // Process variables
    $variables = [];
    if (isset($data['variables']) && is_array($data['variables'])) {
        foreach ($data['variables'] as $variable) {
            if (isset($variable['name']) && isset($variable['value'])) {
                $var_name = trim(filter_var($variable['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
                $var_value = trim(filter_var($variable['value'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
                
                if (!empty($var_name) && !empty($var_value)) {
                    $variables[] = [
                        'name' => $var_name,
                        'value' => $var_value
                    ];
                }
            }
        }
    }

     if ($automation_id) {
        $old_automation = $fun->getAutomation($automation_id, $site_domain);
        $old_event = $old_automation['joonweb_event'] ?? '';
     }

    // Prepare automation data
    $automation_data = [
        'name' => $name,
        'campaign' => $campaign,
        'joonweb_event' => $joonweb_event,
        'status' => $status,
        'variables' => $variables
    ];

    // Save automation
    if ($automation_id) {
        // Update existing automation
        $result = $fun->updateAutomation($automation_id, $site_domain, $automation_data);
        $message = 'Automation updated successfully';
    } else {
        // Create new automation
        $result = $fun->createAutomation($site_domain, $automation_data);
        $message = 'Automation created successfully';
    }

    if ($result) {
        if($automation_id){
            if($status === 'active'){
                $less = $api->updateWebhookSubscription($old_event, $joonweb_event, APP_BASE_URL . "/webhooks/_receiver.php?automation_id={$automation_id}",$status);
                if(!$less){
                    $address = APP_BASE_URL . "/webhooks/_receiver.php?automation_id={$automation_id}";
                    $api->subscribeToWebhooks($joonweb_event, $address);
                }
            }else{
                $address = APP_BASE_URL . "/webhooks/_receiver.php?automation_id={$automation_id}";
                $api->unsubscribeFromWebhooks($joonweb_event, $address);
            }
        }else{
            if($status === 'active'){
                $address = APP_BASE_URL . "/webhooks/_receiver.php?automation_id={$result}";
                $api->subscribeToWebhooks($joonweb_event, $address);
            }
        }


        echo json_encode([
            'success' => true, 
            'message' => $message,
            'automation_id' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save automation']);
    }
}


/**
 * Handle deleting automation
 */
function handleDeleteAutomation($data, $fun, $session) {
    global $api;
    // Validate required fields
    if (!isset($data['automation_id']) || empty(trim($data['automation_id']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: automation_id"]);
        exit;
    }

    $automation_id = intval($data['automation_id']);
    $site_domain = $_SESSION['site_domain'] ?? '';

    if (empty($site_domain)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Site domain not found in session']);
        exit;
    }

    // Get JoonWeb event for the automation before deletion
    $automation = $fun->getAutomation($automation_id, $site_domain);
    $joonweb_event = $automation['joonweb_event'] ?? '';

    $result = $fun->deleteAutomation($automation_id, $site_domain);
    
    if ($result) {
        $address = APP_BASE_URL . "webhooks/_receiver.php?automation_id={$automation_id}";
        $api->unsubscribeFromWebhooks($joonweb_event, $address);
        echo json_encode([
            'success' => true, 
            'message' => 'Automation deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete automation']);
    }
}

/**
 * Handle toggling automation status
 */
function handleToggleAutomationStatus($data, $fun, $session) {
    global $api;
    // Validate required fields
    if (!isset($data['automation_id']) || empty(trim($data['automation_id']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: automation_id"]);
        exit;
    }

    if (!isset($data['status']) || empty(trim($data['status']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: status"]);
        exit;
    }

    $automation_id = intval($data['automation_id']);
    $status = trim($data['status']);
    $site_domain = $_SESSION['site_domain'] ?? '';

    if (empty($site_domain)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Site domain not found in session']);
        exit;
    }

    // Validate status
    $allowed_statuses = ['active', 'paused'];
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }

    $result = $fun->updateAutomationStatus($automation_id, $site_domain, $status);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Automation status updated successfully',
            'new_status' => $status
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update automation status']);
    }
}