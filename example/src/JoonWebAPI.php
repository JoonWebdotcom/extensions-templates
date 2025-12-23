<?php
namespace JoonWeb\EmbedApp;
class JoonWebAPI {
    private $access_token;
    private $site_domain;
    
    public function setAccessToken($token) {
        $this->access_token = $token;
    }
    
    public function setSiteDomain($domain) {
        $this->site_domain = $domain;
    }
    
    public function exchangeCodeForToken($code, $site_domain) {
        $url = "https://{$site_domain}/api/admin/26.0/oauth/access_token";
        
        $payload = [
            'client_id' => JOONWEB_CLIENT_ID,
            'client_secret' => JOONWEB_CLIENT_SECRET,
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ' . APP_NAME . '/v' . APP_VERSION
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
     
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
        
    }
    
    public function api($endpoint, $method = 'GET', $data = []) {
        if (!$this->access_token || !$this->site_domain) {
            throw new Exception("API client not properly configured");
        }
        
        $url = "https://{$this->site_domain}/api/admin/" . JOONWEB_API_VERSION . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'X-JoonWeb-Access-Token: ' . $this->access_token,
            'X-JoonWeb-API-Version: ' . APP_VERSION,
            'User-Agent: ' . APP_NAME . '/v' . APP_VERSION
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);

        if ($http_code >= 400) {
            echo $url;
            throw new Exception("API error {$http_code}: " . ($result['error'] ?? 'Unknown error'));
        }
        
        return $result;
    }
    
    // Convenience methods
    public function getSite() {
        return $this->api('/site.json');
    }
    
    public function getProducts($page = 1, $limit = null) {
        $limit = $limit ?: PRODUCTS_PER_PAGE;
        return $this->api("/products.json");
    }
    
    public function getOrders($status = 'any', $limit = 50) {
        return $this->api("/orders.json?status={$status}&limit={$limit}");
    }
    
    public function getCustomers($limit = 50) {
        return $this->api("/customers.json?limit={$limit}");
    }
    
    public function createProduct($product_data) {
        return $this->api('/products.json', 'POST', ['product' => $product_data]);
    }

    public function subscribeToWebhooks($jw_event, $address) {
        try {
            $webhook_url = $address;
            $payload = [
                'webhook' => [
                    'event' => $jw_event,
                    'address' => $webhook_url,
                    'format' => 'json'
                ]
            ];
            error_log("Subscribing to webhook for event {$jw_event} at address {$webhook_url}");
            $result = $this->api('/webhooks.json', 'POST', $payload);
            error_log("Webhook subscription payload: " . print_r($payload, true));
            return $result;
        } catch (Exception $e) {
            error_log("Failed to subscribe to webhook: " . $e->getMessage());
            return false;
        }
    }
    public function updateWebhookSubscription($old_jw_event, $new_jw_event, $address, $status) {
        // Fetch existing webhooks to find the ID
        $webhooks = $this->api('/webhooks.json');
        foreach ($webhooks['webhooks'] as $webhook) {
            if ($webhook['address'] === $address && $webhook['event'] === $old_jw_event) {
                $webhookid = $webhook['id'];
                break;
            }

        }
        if(isset($webhookid)){
            $payload = [
                'webhook' => [
                    'event' => $new_jw_event,
                    'address' => $address,
                    'format' => 'json',
                    'status' => $status == 'active' ? 1 : 0
                ]
            ];
            error_log("Updating webhook ID {$webhookid} to event {$new_jw_event} with status {$status}");
            error_log(print_r($payload, true));
            $result = $this->api("/webhooks/{$webhookid}.json", 'PUT', $payload);
            error_log("Webhook update result: " . print_r($result, true));
            return $result;
        }else{
            error_log("Webhook ID not found for address {$address} and event {$old_jw_event}");
            return false; // Webhook ID not found
        }
        return false; // Webhook not found
    }

    public function unsubscribeFromWebhooks($jw_event, $address) {
        // Fetch existing webhooks to find the ID
        $webhooks = $this->api('/webhooks.json');
        foreach ($webhooks['webhooks'] as $webhook) {
            if ($webhook['address'] === $address && $webhook['event'] === $jw_event) {
                return $this->api("/webhooks/{$webhook['id']}.json", 'DELETE');
            }
        }
        return false; // Webhook not found
    }
    
    public function updateProduct($product_id, $product_data) {
        return $this->api("/products/{$product_id}.json", 'PUT', ['product' => $product_data]);
    }
}
?>