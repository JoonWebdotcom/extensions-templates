<?php
namespace JoonWeb\EmbedApp;
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/DBSessionManager.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Aisensy.php';

class JoonWebWebhookReceiver {
    private $fun;
    private $aisensy;
    private $webhookSecret; // You'll need to set this for signature verification
    
    public function __construct() {
        $this->fun = new Fun();
        $this->aisensy = new Aisensy();
        $this->webhookSecret = JOONWEB_CLIENT_SECRET; // Set this in your environment
    }
    
    /**
     * Main webhook handler for JoonWeb
     */
    public function handle() {
        // Set JSON response
        header('Content-Type: application/json');
        
        // Only accept POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Get request data
        $input = file_get_contents('php://input');
        $headers = getallheaders();
        $timestamp = date('Y-m-d H:i:s');
        
        try {
            // Log the webhook
            $this->logWebhook($timestamp, $headers, $input);
            
            // Validate JSON
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON payload');
            }
            
            // Verify webhook signature (JoonWeb style)
            if (!$this->verifyWebhookSignature($headers, $input)) {
                throw new \Exception('Invalid webhook signature');
            }
            
            // Extract JoonWeb headers
            $site_domain = $this->extractSiteDomain($headers, $data);
            $event_type = $this->extractEventType($headers, $data);
            
            if (!$site_domain) {
                throw new \Exception('Unable to determine site domain');
            }
            
            if (!$event_type) {
                throw new \Exception('Unable to determine event type');
            }
            
            // Immediately respond success (Shopify pattern)
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Webhook received',
                'processed_at' => $timestamp
            ]);
            
            // Process in background
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            
            // Process the webhook
            $this->processWebhookBackground($site_domain, $event_type, $data);
            
        } catch (\Exception $e) {
            $this->logError($timestamp, $e->getMessage());
            
            http_response_code(400);
            echo json_encode([
                'error' => $e->getMessage(),
                'timestamp' => $timestamp
            ]);
        }
    }
    
    /**
     * Verify JoonWeb webhook signature
     */
    private function verifyWebhookSignature($headers, $payload) {
        // If no secret configured, skip verification (for development)
        if (empty($this->webhookSecret)) {
            return true;
        }
        
        $signature = $headers['X-Webhook-Signature'] ?? '';
        
        if (empty($signature)) {
            throw new \Exception('Missing webhook signature');
        }
        
        // JoonWeb likely uses HMAC-SHA256
        $expected_signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Extract site domain from JoonWeb headers
     */
    private function extractSiteDomain($headers, $data) {
        // Priority 1: X-JoonWeb-Site header
        if (isset($headers['X-Joonweb-Site'])) {
            return $headers['X-Joonweb-Site'];
        }
        
        // Priority 2: From payload
        if (isset($data['site_url'])) {
            return parse_url($data['site_url'], PHP_URL_HOST);
        }
        
        // Priority 3: From order data
        if (isset($data['payload']['order']['source_name'])) {
            return $data['payload']['order']['source_name'];
        }
        
        return null;
    }
    
    /**
     * Extract event type from JoonWeb headers
     */
    private function extractEventType($headers, $data) {
        // Priority 1: X-JoonWeb-Event header
        if (isset($headers['X-Joonweb-Event'])) {
            return $headers['X-Joonweb-Event'];
        }
        
        // Priority 2: From payload
        if (isset($data['event'])) {
            return $data['event'];
        }
        
        return null;
    }
    
    /**
     * Process webhook in background (Shopify style)
     */
    private function processWebhookBackground($site_domain, $event_type, $webhook_data) {
        try {
            // Get active automations for this event
            $automations = $this->fun->getActiveAutomationsByEvent($site_domain, $event_type);
            
            if (empty($automations)) {
                $this->logProcessing($site_domain, $event_type, 'No active automations found');
                return;
            }
            
            $processed = 0;
            
            foreach ($automations as $automation) {
                if ($this->triggerAutomation($automation, $webhook_data)) {
                    $processed++;
                }
            }
            
            $this->logProcessing($site_domain, $event_type, "Processed $processed automations");
            
        } catch (\Exception $e) {
            $this->logError(date('Y-m-d H:i:s'), "Background processing failed: " . $e->getMessage());
        }
    }
    
    /**
     * Trigger individual automation
     */
    private function triggerAutomation($automation, $webhook_data) {
        $event_id = null;
        
        try {
            // Log triggered event
            $event_id = $this->fun->logTriggeredEvent(
                $automation['site_domain'],
                $automation['id'],
                $automation['joonweb_event'],
                $webhook_data,
                'processing'
            );
            
            if (!$event_id) {
                throw new \Exception('Failed to log triggered event');
            }
            
            $payload = $webhook_data['payload'] ?? $webhook_data;
            $ent = explode('/', $automation['joonweb_event']);
            $my_event = ($ent[0] ?? '');

            // Extract data based on event type
            switch($my_event){
                case "orders":
                    $extract = $this->handleOrderPayload($payload);
                    $mobile = $extract['phone'] ?? '';
                    $name = $extract['customer']['firstName'] ?? 'User';
                    break;
                case "checkouts":
                    $extract = $this->handleCheckoutPayload($payload);
                    $mobile = $extract['phone'] ?? '';
                    $name = $extract['customer']['firstName'] ?? 'User';
                    break;
                case "customers":
                    $extract = $this->handleCustomerPayload($payload);
                    $mobile = $extract['phone'] ?? '';
                    $name = $extract['firstName'] ?? 'User';
                    break;
                default:
                    throw new \Exception("Unsupported event type: $my_event");
            }
            
            if (empty($mobile)) {
                throw new \Exception('No customer phone number found');
            }
            
            // Get Aisensy API key
            $api_key = $this->fun->checkAPIBySite($automation['site_domain']);
            
            if (!$api_key) {
                throw new \Exception('Aisensy API key not configured for this site');
            }
            
            // Prepare WhatsApp message parameters
            $message_params = $this->prepareMessageParams($extract, $my_event, $automation['variables'] ?? []);

            // Log Message
            error_log("Prepared message params: " . json_encode($message_params));
            
            // Send via Aisensy
            $result = $this->aisensy->sendMessage($api_key, $name, $mobile, $message_params, $automation['campaign']);
            // log $result
            error_log("Aisensy sendMessage result: " . json_encode($result));
            
            if ($result['success']) {
                $this->fun->updateEventStatus($event_id, 'success');
                return true;
            } else {
                throw new \Exception('Aisensy API error: ' . ($result['message'] ?? 'Unknown error'));
            }
            
        } catch (\Exception $e) {
            if ($event_id) {
                $this->fun->updateEventStatus($event_id, 'failed', $e->getMessage());
            }
            error_log("Automation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle order payload data extraction
     */
    private function handleOrderPayload($payload) {
        $order = $payload['order'] ?? $payload;
        
        // Extract phone number from multiple possible locations
        $phone = $order['customer']['mobile'] ?? 
                $order['billing_address']['phone'] ?? 
                $order['shipping_address']['mobile'] ?? 
                $order['phone'] ?? '';
        
        // Clean and format phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        
        return [
            'phone' => $phone,
            'mobile' => $phone,
            'invoice_id' => $order['invoice_id'] ?? '',
            'subtotal_price' => $order['subtotal_price'] ?? '',
            'total_price' => $order['total_price'] ?? '',
            'total_tax' => $order['total_tax'] ?? '',
            'total_discount' => $order['total_discounts'] ?? '',
            'total_due' => $order['total_outstanding'] ?? $order['total_price'] ?? '',
            'payment_status' => $order['payment_status'] ?? '',
            'payment_method' => $order['payment_gateway_names'][0] ?? '',
            'items' => $order['items'] ?? [],
            'customer' => $order['customer'] ?? [],
            'shipping_address' => $order['shipping_address'] ?? [],
            'billing_address' => $order['billing_address'] ?? [],
            'currency' => $order['currency'] ?? '',
            'created_at' => $order['created_at'] ?? ''
        ];
    }
    
    private function handleCheckoutPayload($payload) {
        $checkout = $payload['checkout'] ?? $payload;
        
        // Extract phone number from multiple possible locations
        $phone = $checkout['customer']['mobile'] ?? 
                $checkout['billing_address']['mobile'] ?? 
                $checkout['shipping_address']['mobile'] ?? 
                $checkout['phone'] ?? '';
        
        // Clean and format phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        
        return [
            'id' => (string) $checkout['id'] ?? '-',
            'token' => $checkout['token'] ?? '',
            "email" => $checkout['email'] ?? '',
            'phone' => $phone,
            'mobile' => $phone,
            'invoice_id' => $checkout['invoice_id'] ?? '',
            'payment_method' => $checkout['gateway'] ?? '',
            'subtotal_price' => $checkout['subtotal_price'] ?? '',
            'total_price' => $checkout['total_price'] ?? '',
            'total_tax' => $checkout['total_tax'] ?? '',
            'abandoned_checkout_url' => $checkout['abandoned_checkout_url'] ?? '',
            'checkout_link' => $checkout['abandoned_checkout_url'] ?? '',
            'total_discount' => $checkout['total_discounts'] ?? '',
            'total_due' => $checkout['total_outstanding'] ?? $checkout['total_price'] ?? '',
            'payment_status' => $checkout['payment_status'] ?? '',
            'payment_method' => $checkout['payment_gateway_names'][0] ?? '',
            'items' => $checkout['items'] ?? [],
            'customer' => $checkout['customer'] ?? [],
            'shipping_address' => $checkout['shipping_address'] ?? [],
            'billing_address' => $checkout['billing_address'] ?? [],
            'currency' => $checkout['currency'] ?? '',
            'created_at' => $checkout['created_at'] ?? ''
        ];
    }
    /**
     * Handle customer payload data extraction
     */
    private function handleCustomerPayload($payload) {
        $customer = $payload['customer'] ?? $payload;
        
        // Extract phone number
        $phone = $customer['mobile'] ?? '';
        
        // Clean and format phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        
        return [
            'phone' => $phone,
            'id' => $customer['id'] ?? '',
            'name' => $customer['name'] ?? '',
            'firstName' => $customer['first_name'] ?? $customer['firstName'] ?? '',
            'lastName' => $customer['last_name'] ?? $customer['lastName'] ?? '',
            'email' => $customer['email'] ?? '',
            'created_at' => $customer['created_at'] ?? ''
        ];
    }
    
    /**
     * Prepare WhatsApp message parameters for Aisensy template
     */
    private function prepareMessageParams($payload, $event, $variables = []) {
        $template_params = [];
        
        if ($event === 'orders') {
            // Extract order data for template parameters
            $items_titles = array_column($payload['items'] ?? [], 'title');
            $first_item = $items_titles[0] ?? '';
            $first_item_qty = $payload['items'][0]['quantity'] ?? $payload['items'][0]['qty'] ?? '1';
            $data_map = [
                'invoice_id' => (string) $payload['invoice_id'] ?? '',
                'subtotal_price' => (string) $payload['subtotal_price'] ?? '',
                'total_price' => (string) $payload['total_price'] ?? '',
                'total_tax' => (string) $payload['total_tax'] ?? '',
                'total_discount' => (string) $payload['total_discount'] ?? '',
                'total_due' => (string) $payload['total_due'] ?? '',
                'payment_status' => $payload['payment_status'] ?? '',
                'payment_method' => $payload['payment_method'] ?? '',
                'order_items_title' => $first_item,
                'order_items_quantity' => (string) "$first_item_qty",
                'total_items_count' => count($payload['items'] ?? []),
                'name' => $payload['shipping_address']['name'] ?? $payload['customer']['name'] ?? '',
                'firstName' => $payload['shipping_address']['first_name'] ?? $payload['customer']['firstName'] ?? '',
                'lastName' => $payload['shipping_address']['last_name'] ?? $payload['customer']['lastName'] ?? '',
                'shipping_address' => isset($payload['shipping_address']) ? implode(', ', array_filter([
                    $payload['shipping_address']['address1'] ?? '',
                    $payload['shipping_address']['address2'] ?? '',
                    $payload['shipping_address']['city'] ?? '',
                    $payload['shipping_address']['state'] ?? '',
                    $payload['shipping_address']['zip'] ?? '',
                    $payload['shipping_address']['country'] ?? ''
                ])) : '',
                'mobile' => $payload['phone'] ?? '',
                'currency' => $payload['currency'] ?? '',
                'email' => $payload['customer']['email'] ?? '',
                'created_at' => $payload['created_at'] ?? ''
            ];
            
            // Map to template parameters (Aisensy expects array of values)
            foreach ($data_map as $key => $value) {
                $template_params[] = (string)$value; // Convert all to strings
            }
            
        }
        else if ($event === 'checkouts') {
            // Extract order data for template parameters
            $items_titles = array_column($payload['items'] ?? [], 'title');
            $first_item = $items_titles[0] ?? '';
            $first_item_qty = $payload['items'][0]['quantity'] ?? $payload['items'][0]['qty'] ?? '1';
            $data_map = [
                'invoice_id' => (string) $payload['invoice_id'] ?? '',
                'subtotal_price' => (string) $payload['subtotal_price'] ?? '',
                'total_price' => (string) $payload['total_price'] ?? '',
                'total_tax' => (string) $payload['total_tax'] ?? '',
                'total_discount' => (string) $payload['total_discount'] ?? '',
                'total_due' => (string) $payload['total_due'] ?? '',
                'payment_status' => $payload['payment_status'] ?? '',
                'payment_method' => $payload['payment_method'] ?? '',
                'order_items_title' => $first_item,
                'order_items_quantity' => (string) "$first_item_qty",
                'total_items_count' => count($payload['items'] ?? []),
                'checkout_link' => $payload['abandoned_checkout_url'] ?? '',
                'name' => $payload['shipping_address']['name'] ?? $payload['customer']['name'] ?? '',
                'firstName' => $payload['shipping_address']['first_name'] ?? $payload['customer']['firstName'] ?? '',
                'lastName' => $payload['shipping_address']['last_name'] ?? $payload['customer']['lastName'] ?? '',
                'shipping_address' => isset($payload['shipping_address']) ? implode(', ', array_filter([
                    $payload['shipping_address']['address1'] ?? '',
                    $payload['shipping_address']['address2'] ?? '',
                    $payload['shipping_address']['city'] ?? '',
                    $payload['shipping_address']['state'] ?? '',
                    $payload['shipping_address']['zip'] ?? '',
                    $payload['shipping_address']['country'] ?? ''
                ])) : '',
                'mobile' => $payload['phone'] ?? '',
                'currency' => $payload['currency'] ?? '',
                'email' => $payload['customer']['email'] ?? '',
                'created_at' => $payload['created_at'] ?? ''
            ];
            
            // Map to template parameters (Aisensy expects array of values)
            foreach ($data_map as $key => $value) {
                $template_params[] = (string)$value; // Convert all to strings
            }
            
        }
        
        else if ($event === 'customers') {
            // Extract customer data for template parameters
            $data_map = [
                'user_id' => $payload['id'] ?? '',
                'name' => $payload['name'] ?? '',
                'firstName' => $payload['firstName'] ?? '',
                'lastName' => $payload['lastName'] ?? '',
                'mobile' => $payload['phone'] ?? '',
                'email' => $payload['email'] ?? '',
                'created_at' => $payload['created_at'] ?? ''
            ];
            
            // Map to template parameters
            foreach ($data_map as $key => $value) {
                $template_params[] = (string)$value;
            }
        }
        
        // Apply custom variable mappings if provided
        if (!empty($variables)) {
            $custom_params = [];
            foreach ($variables as $var) {
                $placeholder = $var['name'] ?? '';
                $data_key = $var['value'] ?? '';
                
                if (isset($data_map[$data_key])) {
                    $custom_params[] = $data_map[$data_key];
                } else {
                    $custom_params[] = ''; // Default empty if not found
                }
            }
            $template_params = $custom_params;
        }
        
        return $template_params;
    }
    
    /**
     * Log webhook receipt
     */
    private function logWebhook($timestamp, $headers, $payload) {
        $logEntry = "=====================\n";
        $logEntry .= "Time: {$timestamp}\n";
        $logEntry .= "Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n";
        $logEntry .= "Body: {$payload}\n";
        $logEntry .= "=====================\n\n";
        
        file_put_contents(__DIR__ . '/webhook_log.txt', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log processing result
     */
    private function logProcessing($site, $event, $message) {
        $logEntry = "[".date('Y-m-d H:i:s')."] {$site} - {$event}: {$message}\n";
        file_put_contents(__DIR__ . '/processing_log.txt', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log errors
     */
    private function logError($timestamp, $message) {
        $logEntry = "[{$timestamp}] ERROR: {$message}\n";
        file_put_contents(__DIR__ . '/error_log.txt', $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Instantiate and handle the webhook
$receiver = new JoonWebWebhookReceiver();
$receiver->handle();