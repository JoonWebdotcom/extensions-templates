<?php
class SessionManager {
    public function startSession($site_domain, $token_data) {
        $_SESSION['site_domain'] = $site_domain;
        $_SESSION['access_token'] = $token_data['access_token'];
        $_SESSION['scope'] = $token_data['scope'];
        $_SESSION['expires_at'] = time() + ($token_data['expires_in'] ?? 86400);
        
        if (isset($token_data['associated_user'])) {
            $_SESSION['user'] = $token_data['associated_user'];
        }
        
        $_SESSION['authenticated_at'] = time();
    }
    
    public function isAuthenticated() {
        return isset($_SESSION['access_token']) && 
               isset($_SESSION['site_domain']) &&
               isset($_SESSION['expires_at']) &&
               $_SESSION['expires_at'] > time();
    }
    
    public function getAccessToken() {
        return $_SESSION['access_token'] ?? null;
    }
    
    public function getSiteDomain() {
        return $_SESSION['site_domain'] ?? null;
    }
    
    public function getUser() {
        return $_SESSION['user'] ?? null;
    }
    
    public function destroySession() {
        session_destroy();
    }
    
    public function isEmbeddedRequest() {
        return isset($_SERVER['HTTP_SEC_FETCH_DEST']) && 
               $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe' ||
               (isset($_SERVER['HTTP_REFERER']) && 
                strpos($_SERVER['HTTP_REFERER'], 'joonweb.com') !== false);
    }
}
?>