<?php
namespace JoonWeb\EmbedApp;

class SessionManager {
    protected $db;
    protected $isMySQL;
    
    public function __construct() {
        $this->db = (new Database())->connect();
        $this->isMySQL = (DB_MODULE === "mysql");
        $this->check = "SessionManager initialized";
    }
    
    public function startSession($site_domain, $token_data) {
        // Store in session
        $_SESSION['site_domain'] = $site_domain;
        $_SESSION['access_token'] = $token_data['access_token'];
        $_SESSION['scope'] = $token_data['scope'];
        $_SESSION['expires_at'] = time() + ($token_data['expires_in'] ?? 86400);

       
        
        // Store in database
        $this->storeSiteInDatabase($site_domain, $token_data);
        
        // Track installation
        $this->trackAnalytics($site_domain, 'app_installed');
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
    
    private function storeSiteInDatabase($site_domain, $token_data) {
        $scope = is_array($token_data['scope']) ? implode(",", $token_data['scope']) : $token_data['scope'];
        
        if ($this->isMySQL) {
            // MySQL optimized query
            $query = "INSERT INTO sites (site_domain, access_token, scope, installed_at, is_active) 
                      VALUES (:site_domain, :access_token, :scope, NOW(), 1)
                      ON DUPLICATE KEY UPDATE 
                      access_token = VALUES(access_token),
                      scope = VALUES(scope),
                      is_active = 1,
                      uninstalled_at = NULL,
                      updated_at = NOW()";
        } else {
            // SQLite optimized query - INSERT OR REPLACE is more efficient
            $query = "INSERT OR REPLACE INTO sites 
                      (site_domain, access_token, scope, installed_at, is_active, uninstalled_at, updated_at) 
                      VALUES (:site_domain, :access_token, :scope, 
                              datetime('now'), 1, NULL, datetime('now'))";
        }
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':access_token' => $token_data['access_token'],
                ':scope' => $scope
            ]);
            return true;
        } catch (\PDOException $e) {
            error_log("Database error in storeSiteInDatabase: " . $e->getMessage());
            return false;
        }
    }
    
    public function trackAnalytics($site_domain, $event_type, $event_data = []) {
        $query = "INSERT INTO app_analytics (site_domain, event_type, event_data, user_agent, ip_address) 
                  VALUES (:site_domain, :event_type, :event_data, :user_agent, :ip_address)";
        
        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':site_domain' => $site_domain,
                ':event_type' => $event_type,
                ':event_data' => json_encode($event_data),
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (\PDOException $e) {
            error_log("Database error in trackAnalytics: " . $e->getMessage());
            return false;
        }
    }
    
    public function getSiteFromDatabase($site_domain) {
        $query = "SELECT * FROM sites WHERE site_domain = :site_domain AND is_active = 1";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':site_domain' => $site_domain]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Database error in getSiteFromDatabase: " . $e->getMessage());
            return false;
        }
    }
    
    public function saveAppData($site_domain, $key, $value) {
        if ($this->isMySQL) {
            $query = "INSERT INTO app_data (site_domain, data_key, data_value) 
                      VALUES (:site_domain, :data_key, :data_value)
                      ON DUPLICATE KEY UPDATE 
                      data_value = VALUES(data_value),
                      updated_at = NOW()";
        } else {
            $query = "INSERT OR REPLACE INTO app_data 
                      (site_domain, data_key, data_value, updated_at) 
                      VALUES (:site_domain, :data_key, :data_value, datetime('now'))";
        }
        
        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':site_domain' => $site_domain,
                ':data_key' => $key,
                ':data_value' => json_encode($value, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (\PDOException $e) {
            error_log("Database error in saveAppData: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAppData($site_domain, $key) {
        $query = "SELECT data_value FROM app_data 
                  WHERE site_domain = :site_domain AND data_key = :data_key";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':data_key' => $key
            ]);
            
            $result = $stmt->fetch();
            return $result ? json_decode($result['data_value'], true) : null;
        } catch (\PDOException $e) {
            error_log("Database error in getAppData: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Bulk save app data for better performance
     */
    public function saveMultipleAppData($site_domain, $data_array) {
        if ($this->isMySQL) {
            $query = "INSERT INTO app_data (site_domain, data_key, data_value) 
                      VALUES (:site_domain, :data_key, :data_value)
                      ON DUPLICATE KEY UPDATE 
                      data_value = VALUES(data_value),
                      updated_at = NOW()";
        } else {
            $query = "INSERT OR REPLACE INTO app_data 
                      (site_domain, data_key, data_value, updated_at) 
                      VALUES (:site_domain, :data_key, :data_value, datetime('now'))";
        }
        
        try {
            $stmt = $this->db->prepare($query);
            $this->db->beginTransaction();
            
            foreach ($data_array as $key => $value) {
                $stmt->execute([
                    ':site_domain' => $site_domain,
                    ':data_key' => $key,
                    ':data_value' => json_encode($value, JSON_UNESCAPED_UNICODE)
                ]);
            }
            
            return $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Database error in saveMultipleAppData: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all app data for a site
     */
    public function getAllAppData($site_domain) {
        $query = "SELECT data_key, data_value FROM app_data WHERE site_domain = :site_domain";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':site_domain' => $site_domain]);
            
            $results = $stmt->fetchAll();
            $data = [];
            
            foreach ($results as $row) {
                $data[$row['data_key']] = json_decode($row['data_value'], true);
            }
            
            return $data;
        } catch (\PDOException $e) {
            error_log("Database error in getAllAppData: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions() {
        $query = "DELETE FROM sessions WHERE expires_at < " . 
                 ($this->isMySQL ? "NOW()" : "datetime('now')");
        
        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Database error in cleanupExpiredSessions: " . $e->getMessage());
            return false;
        }
    }
}
?>