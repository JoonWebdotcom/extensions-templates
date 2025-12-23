<?php
namespace JoonWeb\EmbedApp;
// API of AISENSY App
class Aisensy {
    protected $api_key;
    protected $api_base_url = "https://backend.aisensy.com";
    public function __construct($api_key=null) {
        // Set Aisensy API Key
        if ($api_key !== null) {
            $this->api_key = $api_key;
        }
    }

    function checkAiSensyApiKey($apiKey) {
        $url = $this->api_base_url . "campaign/t1/api/v2";

        $payload = json_encode([
            "apiKey" => $apiKey
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['status']) && $result['status'] === true) {
            return true;
        } else {
            return $result;
        }
    }

    public function sendMessage($api_key, $username, $to, $message, $campaign_name, $media=[],$source='') {
        $payload = [
                "apiKey" => $api_key,
                "userName" => $username,
                "destination" => $to,
                "campaignName" => $campaign_name,
        ];
        if(isset($message) && !empty($message)){ $payload['templateParams'] = $message; }
        $headers = array(
            'Content-Type: application/json'
        );
        if(isset($media) && !empty($media)){ 
            $payload['media'] = [
                'filename' => $campaign_name,
                'url' => $media[0]
            ];
        }

        // Payload logging for debugging
        error_log("Aisensy Payload: " . json_encode($payload));

        $mail_curl = curl_init();
        curl_setopt_array($mail_curl, array(
            CURLOPT_URL => $this->api_base_url . "/campaign/t1/api/v2",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYSTATUS => FALSE,
            CURLOPT_POSTFIELDS =>  json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
        ));
        $mail_res = curl_exec($mail_curl);
        $mail_err = curl_error($mail_curl);
        $response_code = curl_getinfo($mail_curl, CURLINFO_HTTP_CODE);
        error_log("Aisensy Response Code: " . $response_code);
        error_log("Aisensy Response: " . $mail_res);
        curl_close($mail_curl);
        if ($mail_err) {
            $response = [
                'success' => false,
                'error' => $mail_err,
            ];
        } else {
            $response_data = json_decode($mail_res, true);
            if ($response_code == 401) {
                return $response = [
                    'success' => false,
                    'error' => 'Unauthorized: Check your API key or credentials',
                    'response' => $response_data,
                ];
            } else if ($response_code >= 200 && $response_code < 300) {
                return $response = [
                    'success' => true,
                    'data' => $response_data,
                ];
            } else {
                return $response = [
                    'success' => false,
                    'data' => $response_data,
                    'http_code' => $response_code,
                ];
            }
        }
    }

    public function validateApiKey($api_key = null) {
        $keyToValidate = $api_key ?? $this->api_key;
        return $keyToValidate ?? false;
    }
    
    public function setAPIkey($api_key){
        $this->api_key = $api_key;
        return true;
    }

}

