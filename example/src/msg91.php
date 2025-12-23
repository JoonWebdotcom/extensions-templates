<?php
namespace JoonWeb\EmbedApp;

/**
 * MSG91 API Integration Class
 * Documentation: https://docs.msg91.com/
 */
class MSG91 {
    protected $api_key;
    protected $api_base_url = "https://control.msg91.com/api/v5/";
    protected $timeout = 30;
    protected $verify_ssl = true;
    
    public function __construct($api_key = null) {
        if ($api_key !== null) {
            $this->api_key = $api_key;
        }
    }

    /**
     * Generic API call method with improved error handling
     */
    protected function callApi($endpoint, $method = 'GET', $data = [], $headers = []) {
        $url = $this->api_base_url . ltrim($endpoint, '/');
        $ch = curl_init($url);

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->api_key) {
            $defaultHeaders[] = 'authkey: ' . $this->api_key;
        }
        
        $finalHeaders = array_merge($defaultHeaders, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verify_ssl,
            CURLOPT_SSL_VERIFYHOST => $this->verify_ssl ? 2 : 0,
            CURLOPT_HTTPHEADER => $finalHeaders,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'GET' && !empty($data)) {
            $url = $url . '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                'raw_response' => $response,
                'http_code' => $httpCode
            ];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $result,
            'http_code' => $httpCode
        ];
    }

    /**
     * Campaign Management Methods
     */
    
    public function getCampaignsList($page = 1, $limit = 10) {
        $endpoint = "campaign/api/campaigns";
        return $this->callApi($endpoint, 'GET', ['page' => $page, 'limit' => $limit]);
    }

    public function getCampaignBySlug($slug) {
        $endpoint = "campaign/api/campaigns/{$slug}";
        return $this->callApi($endpoint, 'GET');
    }

    public function updateCampaign($slug, $campaignData) {
        $endpoint = "campaign/api/campaigns/{$slug}";
        return $this->callApi($endpoint, 'PUT', $campaignData);
    }

    /**
     * Improved runCampaign method with better structure
     */
    public function runCampaign($campaign_slug, $recipients, $variables = [], $options = []) {
        $endpoint = "campaign/api/campaigns/{$campaign_slug}/run";
        
        $data = [
            "data" => [
                "sendTo" => [],
                "options" => $options
            ]
        ];

        foreach ($recipients as $recipient) {
            $sendTo = [
                "to" => [],
                "variables" => $variables
            ];
            
            if (isset($recipient['email'])) {
                $sendTo["to"][] = [
                    "name" => $recipient['name'] ?? '',
                    "email" => $recipient['email'],
                    "mobiles" => $recipient['mobile'] ?? '',
                    "variables" => $recipient['variables'] ?? []
                ];
            }
            else if(isset($recipient['mobile'])) {
                $sendTo["to"][] = [
                    "name" => $recipient['name'] ?? '',
                    "mobiles" => $recipient['mobile'],
                    "variables" => $recipient['variables'] ?? []
                ];
            }
            
            $data["data"]["sendTo"][] = $sendTo;
        }

        return $this->callApi($endpoint, 'POST', $data);
    }

    /**
     * Send SMS (Traditional MSG91 SMS API)
     */
    public function sendSMS($to, $message, $sender = null, $route = null, $country = null) {
        $endpoint = "sms/send";
        
        $data = [
            "sender" => $sender ?? "SOCKET",
            "route" => $route ?? "4", // Transactional route
            "country" => $country ?? "91", // India by default
            "sms" => [
                [
                    "message" => $message,
                    "to" => is_array($to) ? $to : [$to]
                ]
            ]
        ];

        return $this->callApi($endpoint, 'POST', $data);
    }

    /**
     * Send Bulk SMS
     */
    public function sendBulkSMS($messages, $sender = null, $route = null, $country = null) {
        $endpoint = "sms/send";
        
        $data = [
            "sender" => $sender ?? "SOCKET",
            "route" => $route ?? "4",
            "country" => $country ?? "91",
            "sms" => $messages
        ];

        return $this->callApi($endpoint, 'POST', $data);
    }

    /**
     * Check SMS Balance
     */
    public function checkBalance($plan_type = null) {
        $endpoint = "balance.php";
        
        $params = [];
        if ($plan_type !== null) {
            $params['type'] = $plan_type; // 1 for transactional, 2 for promotional
        }
        
        // Balance endpoint uses different base URL
        $oldUrl = $this->api_base_url;
        $this->api_base_url = "https://control.msg91.com/api/";
        $result = $this->callApi($endpoint, 'GET', $params);
        $this->api_base_url = $oldUrl;
        
        return $result;
    }

    /**
     * Send OTP
     */
    public function sendOTP($mobile, $otp = null, $template_id = null) {
        $endpoint = "otp";
        
        $data = [
            "mobile" => $mobile,
            "authkey" => $this->api_key
        ];
        
        if ($otp !== null) {
            $data["otp"] = $otp;
        }
        
        if ($template_id !== null) {
            $data["template_id"] = $template_id;
        }

        return $this->callApi($endpoint . "/send", 'POST', $data);
    }

    /**
     * Verify OTP
     */
    public function verifyOTP($mobile, $otp) {
        $endpoint = "otp/verify";
        
        $data = [
            "mobile" => $mobile,
            "otp" => $otp,
            "authkey" => $this->api_key
        ];

        return $this->callApi($endpoint, 'POST', $data);
    }

    /**
     * Resend OTP
     */
    public function resendOTP($mobile, $retry_type = "text") {
        $endpoint = "otp/retry";
        
        $data = [
            "mobile" => $mobile,
            "retrytype" => $retry_type,
            "authkey" => $this->api_key
        ];

        return $this->callApi($endpoint, 'POST', $data);
    }

    /**
     * WhatsApp API Methods
     */
    
    public function sendWhatsApp($to, $template_id, $parameters = [], $components = []) {
        $endpoint = "whatsapp/whatsapp.php";
        
        $data = [
            "recipients" => [
                [
                    "mobiles" => is_array($to) ? $to : [$to],
                    "content" => [
                        "type" => "template",
                        "template" => [
                            "templateId" => $template_id,
                            "parameters" => $parameters,
                            "components" => $components
                        ]
                    ]
                ]
            ]
        ];

        return $this->callApi($endpoint, 'POST', $data);
    }

    /**
     * Email API Methods
     */
    
    public function sendEmail($to, $subject, $content, $from = null, $cc = [], $bcc = []) {
        $endpoint = "email";
        
        $data = [
            "to" => is_array($to) ? $to : [$to],
            "subject" => $subject,
            "body" => $content,
            "from" => $from,
            "cc" => $cc,
            "bcc" => $bcc
        ];

        return $this->callApi($endpoint . "/send", 'POST', $data);
    }

    /**
     * Voice API Methods
     */
    
    public function sendVoiceCall($to, $message, $language = "en") {
        $endpoint = "voice";
        
        $data = [
            "to" => is_array($to) ? $to : [$to],
            "message" => $message,
            "language" => $language
        ];

        return $this->callApi($endpoint . "/send", 'POST', $data);
    }

    /**
     * Template Management
     */
    
    public function getTemplates($type = null, $page = 1, $limit = 10) {
        $endpoint = "template";
        $params = ['page' => $page, 'limit' => $limit];
        
        if ($type !== null) {
            $params['type'] = $type; // sms, whatsapp, email, voice
        }
        
        return $this->callApi($endpoint, 'GET', $params);
    }

    /**
     * Analytics & Reports
     */
    
    public function getSMSReports($from_date = null, $to_date = null, $page = 1, $limit = 100) {
        $endpoint = "reports";
        $params = ['page' => $page, 'limit' => $limit];
        
        if ($from_date !== null) {
            $params['from'] = $from_date;
        }
        
        if ($to_date !== null) {
            $params['to'] = $to_date;
        }

        return $this->callApi($endpoint, 'GET', $params);
    }

    public function getCampaignReports($campaign_slug, $from_date = null, $to_date = null) {
        $endpoint = "campaign/api/campaigns/{$campaign_slug}/reports";
        
        $params = [];
        if ($from_date !== null) {
            $params['from'] = $from_date;
        }
        
        if ($to_date !== null) {
            $params['to'] = $to_date;
        }

        return $this->callApi($endpoint, 'GET', $params);
    }

    /**
     * Authentication & Configuration
     */
    
    public function validateApiKey($api_key = null) {
        $keyToValidate = $api_key ?? $this->api_key;
        
        if (!$keyToValidate) {
            return [
                'success' => false,
                'error' => 'API key is required'
            ];
        }

        // Check balance as a way to validate API key
        $oldKey = $this->api_key;
        $this->api_key = $keyToValidate;
        $result = $this->checkBalance();
        $this->api_key = $oldKey;
        
        if (isset($result['success']) && $result['success']) {
            return [
                'success' => true,
                'message' => 'API key is valid'
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Invalid API key'
            ];
        }
    }

    public function setAPIkey($api_key) {
        $this->api_key = $api_key;
        return true;
    }

    public function setBaseUrl($url) {
        $this->api_base_url = rtrim($url, '/') . '/';
    }

    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    public function setVerifySSL($verify) {
        $this->verify_ssl = $verify;
    }

    /**
     * Utility Methods
     */
    
    public function formatMobileNumber($number, $country_code = "91") {
        // Remove all non-digit characters
        $number = preg_replace('/\D/', '', $number);
        
        // Remove leading zeros
        $number = ltrim($number, '0');
        
        // Add country code if not present
        if (substr($number, 0, strlen($country_code)) !== $country_code) {
            $number = $country_code . $number;
        }
        
        return $number;
    }

    public function validateMobileNumber($number, $country_code = "91") {
        $formatted = $this->formatMobileNumber($number, $country_code);
        
        // Basic validation: check if it's numeric and has reasonable length
        if (!is_numeric($formatted)) {
            return false;
        }
        
        $length = strlen($formatted);
        return $length >= 10 && $length <= 15;
    }

    /**
     * Deprecated methods kept for backward compatibility
     */
    
    public function checkMsg91ApiKey($apiKey) {
        return $this->validateApiKey($apiKey);
    }

    public function sendMessage($api_key, $username, $to, $message, $campaign_name, $media = [], $source = '') {
        // This is a legacy method - using the new campaign method instead
        $recipients = [['email' => $to]];
        return $this->runCampaign($campaign_name, $recipients, $message);
    }
}