<?php
namespace JoonWeb\EmbedApp;

class Fun extends SessionManager {
    
    public function __construct() {
        parent::__construct();

        if (!$this->db) {
            throw new \Exception("Database is not initialized in SessionManager.");
        }
        
        // Enable foreign keys for SQLite
        if (!$this->isMySQL) {
            $this->db->exec("PRAGMA foreign_keys = ON");
        }
        
        // create tables if missing
        $this->ensureTablesExist();
    }

    private function ensureTablesExist() {
        $this->constructAisensyTable($this->isMySQL);
        $this->constructAutomationsTable($this->isMySQL);
        $this->constructTriggeredEventsTable($this->isMySQL);
    }

    private function constructAisensyTable($isMySQL = false) {
        if ($isMySQL) {
            $sql = "CREATE TABLE IF NOT EXISTS `aisensy_apps` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `site_domain` VARCHAR(255) NOT NULL UNIQUE,
                `api_key` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `aisensy_apps` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `site_domain` TEXT NOT NULL UNIQUE,
                `api_key` TEXT NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        }

        try {
            return $this->db->exec($sql);
        } catch (\PDOException $e) {
            error_log("Error creating aisensy_apps table: " . $e->getMessage());
            return false;
        }
    }

    private function constructAutomationsTable($isMySQL = false) {
        if ($isMySQL) {
            $sql = "CREATE TABLE IF NOT EXISTS `automations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `site_domain` VARCHAR(255) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `trigger_name` VARCHAR(255),
                `campaign` VARCHAR(255) NOT NULL,
                `joonweb_event` VARCHAR(255) NOT NULL,
                `status` ENUM('active', 'draft', 'paused') DEFAULT 'draft',
                `variables` JSON,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_site_domain` (`site_domain`),
                INDEX `idx_status` (`status`),
                INDEX `idx_event` (`joonweb_event`),
                FOREIGN KEY (`site_domain`) REFERENCES `aisensy_apps`(`site_domain`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `automations` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `site_domain` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `trigger_name` TEXT,
                `campaign` TEXT NOT NULL,
                `joonweb_event` TEXT NOT NULL,
                `status` TEXT DEFAULT 'draft' CHECK(status IN ('active', 'draft', 'paused')),
                `variables` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`site_domain`) REFERENCES `aisensy_apps`(`site_domain`) ON DELETE CASCADE
            )";
        }

        try {
            $result = $this->db->exec($sql);
            
            // Create indexes for SQLite
            if (!$isMySQL) {
                $indexes = [
                    "CREATE INDEX IF NOT EXISTS idx_automations_site_domain ON automations(site_domain)",
                    "CREATE INDEX IF NOT EXISTS idx_automations_status ON automations(status)",
                    "CREATE INDEX IF NOT EXISTS idx_automations_event ON automations(joonweb_event)"
                ];
                
                foreach ($indexes as $indexSql) {
                    $this->db->exec($indexSql);
                }
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error creating automations table: " . $e->getMessage());
            return false;
        }
    }

    private function constructTriggeredEventsTable($isMySQL = false) {
        if ($isMySQL) {
            $sql = "CREATE TABLE IF NOT EXISTS `triggered_events` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `site_domain` VARCHAR(255) NOT NULL,
                `automation_id` INT NOT NULL,
                `event_type` VARCHAR(255) NOT NULL,
                `event_data` JSON,
                `triggered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('success', 'failed', 'processing') DEFAULT 'processing',
                `error_message` TEXT,
                `processed_at` TIMESTAMP NULL,
                INDEX `idx_site_domain` (`site_domain`),
                INDEX `idx_automation_id` (`automation_id`),
                INDEX `idx_event_type` (`event_type`),
                INDEX `idx_triggered_at` (`triggered_at`),
                INDEX `idx_status` (`status`),
                FOREIGN KEY (`site_domain`) REFERENCES `aisensy_apps`(`site_domain`) ON DELETE CASCADE,
                FOREIGN KEY (`automation_id`) REFERENCES `automations`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `triggered_events` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `site_domain` TEXT NOT NULL,
                `automation_id` INTEGER NOT NULL,
                `event_type` TEXT NOT NULL,
                `event_data` TEXT,
                `triggered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `status` TEXT DEFAULT 'processing' CHECK(status IN ('success', 'failed', 'processing')),
                `error_message` TEXT,
                `processed_at` DATETIME NULL,
                FOREIGN KEY (`site_domain`) REFERENCES `aisensy_apps`(`site_domain`) ON DELETE CASCADE,
                FOREIGN KEY (`automation_id`) REFERENCES `automations`(`id`) ON DELETE CASCADE
            )";
        }

        try {
            $result = $this->db->exec($sql);
            
            // Create indexes for SQLite
            if (!$isMySQL) {
                $indexes = [
                    "CREATE INDEX IF NOT EXISTS idx_triggered_site_domain ON triggered_events(site_domain)",
                    "CREATE INDEX IF NOT EXISTS idx_triggered_automation_id ON triggered_events(automation_id)",
                    "CREATE INDEX IF NOT EXISTS idx_triggered_event_type ON triggered_events(event_type)",
                    "CREATE INDEX IF NOT EXISTS idx_triggered_triggered_at ON triggered_events(triggered_at)",
                    "CREATE INDEX IF NOT EXISTS idx_triggered_status ON triggered_events(status)"
                ];
                
                foreach ($indexes as $indexSql) {
                    $this->db->exec($indexSql);
                }
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error creating triggered_events table: " . $e->getMessage());
            return false;
        }
    }

    public function InsertApiKey($site_domain, $api_key) {
        try {
            // Check if record exists
            $existing = $this->getAisensy($site_domain);
            
            if ($existing) {
                // Update existing record
                $timestamp = $this->isMySQL ? "CURRENT_TIMESTAMP" : "datetime('now')";
                $query = "UPDATE aisensy_apps SET api_key = :api_key WHERE site_domain = :site_domain";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':site_domain' => $site_domain,
                    ':api_key' => $api_key
                ]);
                
                return $stmt->rowCount() > 0;
                
            } else {
                // Insert new record
                $query = "INSERT INTO aisensy_apps (site_domain, api_key) VALUES (:site_domain, :api_key)";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':site_domain' => $site_domain,
                    ':api_key' => $api_key
                ]);
                
                return $this->isMySQL ? $stmt->rowCount() > 0 : $this->db->lastInsertId() !== false;
            }
            
        } catch (\PDOException $e) {
            error_log("Database error in InsertApiKey: " . $e->getMessage());
            return false;
        }
    }

    public function checkAPIBySite($site_domain) {
        $data = $this->getAisensy($site_domain);
        return $data ? $data['api_key'] : false;
    }

    public function getAisensy($site_domain) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM `aisensy_apps` WHERE `site_domain` = :site_domain LIMIT 1");
            $stmt->bindParam(':site_domain', $site_domain);
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error in getAisensy: " . $e->getMessage());
            return false;
        }
    }

    // Automation Methods
    public function createAutomation($site_domain, $data) {
        try {
            $query = "INSERT INTO automations (site_domain, name, trigger_name, campaign, joonweb_event, status, variables) 
                     VALUES (:site_domain, :name, :trigger_name, :campaign, :joonweb_event, :status, :variables)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':name' => $data['name'],
                ':trigger_name' => $data['trigger_name'] ?? null,
                ':campaign' => $data['campaign'],
                ':joonweb_event' => $data['joonweb_event'],
                ':status' => $data['status'] ?? 'draft',
                ':variables' => json_encode($data['variables'] ?? [])
            ]);
            
            return $this->isMySQL ? $stmt->rowCount() > 0 : $this->db->lastInsertId() !== false;
            
        } catch (\PDOException $e) {
            error_log("Database error in createAutomation: " . $e->getMessage());
            return false;
        }
    }

    public function updateAutomation($id, $site_domain, $data) {
        try {
            $timestamp = $this->isMySQL ? "CURRENT_TIMESTAMP" : "datetime('now')";
            $query = "UPDATE automations SET 
                     name = :name, 
                     trigger_name = :trigger_name, 
                     campaign = :campaign, 
                     joonweb_event = :joonweb_event, 
                     status = :status, 
                     variables = :variables,
                     updated_at = $timestamp
                     WHERE id = :id AND site_domain = :site_domain";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':id' => $id,
                ':site_domain' => $site_domain,
                ':name' => $data['name'],
                ':trigger_name' => $data['trigger_name'] ?? null,
                ':campaign' => $data['campaign'],
                ':joonweb_event' => $data['joonweb_event'],
                ':status' => $data['status'] ?? 'draft',
                ':variables' => json_encode($data['variables'] ?? [])
            ]);
            
            return $stmt->rowCount() > 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in updateAutomation: " . $e->getMessage());
            return false;
        }
    }

    public function getAutomations($site_domain = null, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            if ($site_domain) {
                $query = "SELECT * FROM automations WHERE site_domain = :site_domain ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':site_domain', $site_domain);
            } else {
                $query = "SELECT * FROM automations ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
            }
            
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
            $stmt->execute();
            
            $automations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($automations as &$automation) {
                if ($automation['variables']) {
                    $automation['variables'] = json_decode($automation['variables'], true);
                }
            }
            
            return ['automations' => $automations, 'total' => $this->getAutomationsCount($site_domain)];
            
        } catch (\PDOException $e) {
            error_log("Database error in getAutomations: " . $e->getMessage());
            return ['automations' => [], 'total' => 0];
        }
    }

    public function getAutomation($id, $site_domain = null) {
        try {
            if ($site_domain) {
                $query = "SELECT * FROM automations WHERE id = :id AND site_domain = :site_domain LIMIT 1";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':id' => $id, ':site_domain' => $site_domain]);
            } else {
                $query = "SELECT * FROM automations WHERE id = :id LIMIT 1";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':id' => $id]);
            }
            
            $automation = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($automation && $automation['variables']) {
                $automation['variables'] = json_decode($automation['variables'], true);
            }
            
            return $automation;
            
        } catch (\PDOException $e) {
            error_log("Database error in getAutomation: " . $e->getMessage());
            return false;
        }
    }

    private function getAutomationsCount($site_domain = null) {
        try {
            if ($site_domain) {
                $query = "SELECT COUNT(*) as count FROM automations WHERE site_domain = :site_domain";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':site_domain' => $site_domain]);
            } else {
                $query = "SELECT COUNT(*) as count FROM automations";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
            }
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in getAutomationsCount: " . $e->getMessage());
            return 0;
        }
    }

    public function deleteAutomation($id, $site_domain) {
        try {
            $query = "DELETE FROM automations WHERE id = :id AND site_domain = :site_domain";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id, ':site_domain' => $site_domain]);
            
            return $stmt->rowCount() > 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in deleteAutomation: " . $e->getMessage());
            return false;
        }
    }

    // Triggered Events Methods
    public function logTriggeredEvent($site_domain, $automation_id, $event_type, $event_data = [], $status = 'processing') {
        try {
            $query = "INSERT INTO triggered_events (site_domain, automation_id, event_type, event_data, status) 
                     VALUES (:site_domain, :automation_id, :event_type, :event_data, :status)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':automation_id' => $automation_id,
                ':event_type' => $event_type,
                ':event_data' => json_encode($event_data),
                ':status' => $status
            ]);
            
            return $this->isMySQL ? $stmt->rowCount() > 0 : $this->db->lastInsertId() !== false;
            
        } catch (\PDOException $e) {
            error_log("Database error in logTriggeredEvent: " . $e->getMessage());
            return false;
        }
    }

    public function getTriggeredEvents($site_domain = null, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            if ($site_domain) {
                $query = "SELECT te.*, a.name as automation_name 
                         FROM triggered_events te 
                         LEFT JOIN automations a ON te.automation_id = a.id 
                         WHERE te.site_domain = :site_domain 
                         ORDER BY te.triggered_at DESC 
                         LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':site_domain', $site_domain);
            } else {
                $query = "SELECT te.*, a.name as automation_name 
                         FROM triggered_events te 
                         LEFT JOIN automations a ON te.automation_id = a.id 
                         ORDER BY te.triggered_at DESC 
                         LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
            }
            
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
            $stmt->execute();
            
            $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($events as &$event) {
                if ($event['event_data']) {
                    $event['event_data'] = json_decode($event['event_data'], true);
                }
            }
            
            return ['events' => $events, 'total' => $this->getTriggeredEventsCount($site_domain)];
            
        } catch (\PDOException $e) {
            error_log("Database error in getTriggeredEvents: " . $e->getMessage());
            return ['events' => [], 'total' => 0];
        }
    }

    private function getTriggeredEventsCount($site_domain = null) {
        try {
            if ($site_domain) {
                $query = "SELECT COUNT(*) as count FROM triggered_events WHERE site_domain = :site_domain";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':site_domain' => $site_domain]);
            } else {
                $query = "SELECT COUNT(*) as count FROM triggered_events";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
            }
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in getTriggeredEventsCount: " . $e->getMessage());
            return 0;
        }
    }

    public function updateEventStatus($event_id, $status, $error_message = null) {
        try {
            $timestamp = $this->isMySQL ? "CURRENT_TIMESTAMP" : "datetime('now')";
            $query = "UPDATE triggered_events SET 
                     status = :status, 
                     error_message = :error_message,
                     processed_at = $timestamp
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':id' => $event_id,
                ':status' => $status,
                ':error_message' => $error_message
            ]);
            
            return $stmt->rowCount() > 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in updateEventStatus: " . $e->getMessage());
            return false;
        }
    }

    // Dashboard Stats
    public function getDashboardStats($site_domain) {
        try {
            $stats = [];
            
            // Total automations
            $query = "SELECT COUNT(*) as count FROM automations WHERE site_domain = :site_domain";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':site_domain' => $site_domain]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['total_automations'] = $result['count'] ?? 0;
            
            // Active automations
            $query = "SELECT COUNT(*) as count FROM automations WHERE site_domain = :site_domain AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':site_domain' => $site_domain]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['active_automations'] = $result['count'] ?? 0;
            
            // Total events
            $query = "SELECT COUNT(*) as count FROM triggered_events WHERE site_domain = :site_domain";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':site_domain' => $site_domain]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['total_events'] = $result['count'] ?? 0;
            
            // Triggered events (successful)
            $query = "SELECT COUNT(*) as count FROM triggered_events WHERE site_domain = :site_domain AND status = 'success'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':site_domain' => $site_domain]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['triggered_events'] = $result['count'] ?? 0;
            
            // Conversion rate
            $stats['conversion_rate'] = $stats['total_events'] > 0 ? 
                round(($stats['triggered_events'] / $stats['total_events']) * 100, 2) : 0;
            
            return $stats;
            
        } catch (\PDOException $e) {
            error_log("Database error in getDashboardStats: " . $e->getMessage());
            return [
                'total_automations' => 0,
                'active_automations' => 0,
                'total_events' => 0,
                'triggered_events' => 0,
                'conversion_rate' => 0
            ];
        }
    }

    // Utility Methods
    public function getAllSites() {
        try {
            $stmt = $this->db->prepare("SELECT site_domain, created_at FROM aisensy_apps ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error in getAllSites: " . $e->getMessage());
            return [];
        }
    }

    public function deleteSite($site_domain) {
        try {
            // This will cascade delete automations and triggered_events due to foreign key constraints
            $query = "DELETE FROM aisensy_apps WHERE site_domain = :site_domain";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':site_domain' => $site_domain]);
            
            return $stmt->rowCount() > 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in deleteSite: " . $e->getMessage());
            return false;
        }
    }

    // Get events by automation
    public function getEventsByAutomation($automation_id, $site_domain = null, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            if ($site_domain) {
                $query = "SELECT te.*, a.name as automation_name 
                         FROM triggered_events te 
                         LEFT JOIN automations a ON te.automation_id = a.id 
                         WHERE te.automation_id = :automation_id AND te.site_domain = :site_domain 
                         ORDER BY te.triggered_at DESC 
                         LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':automation_id', $automation_id);
                $stmt->bindParam(':site_domain', $site_domain);
            } else {
                $query = "SELECT te.*, a.name as automation_name 
                         FROM triggered_events te 
                         LEFT JOIN automations a ON te.automation_id = a.id 
                         WHERE te.automation_id = :automation_id 
                         ORDER BY te.triggered_at DESC 
                         LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':automation_id', $automation_id);
            }
            
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
            $stmt->execute();
            
            $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($events as &$event) {
                if ($event['event_data']) {
                    $event['event_data'] = json_decode($event['event_data'], true);
                }
            }
            
            return $events;
            
        } catch (\PDOException $e) {
            error_log("Database error in getEventsByAutomation: " . $e->getMessage());
            return [];
        }
    }

    // Get recent events for dashboard
    public function getRecentEvents($site_domain, $limit = 5) {
        try {
            $query = "SELECT te.*, a.name as automation_name 
                     FROM triggered_events te 
                     LEFT JOIN automations a ON te.automation_id = a.id 
                     WHERE te.site_domain = :site_domain 
                     ORDER BY te.triggered_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':site_domain', $site_domain);
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->execute();
            
            $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($events as &$event) {
                if ($event['event_data']) {
                    $event['event_data'] = json_decode($event['event_data'], true);
                }
            }
            
            return $events;
            
        } catch (\PDOException $e) {
            error_log("Database error in getRecentEvents: " . $e->getMessage());
            return [];
        }
    }

    // Add these methods to your Fun class

    public function getWebhookIdByAutomationId($automation_id, $site_domain) {
        try {
            $query = "SELECT automation_id FROM automations WHERE id = :automation_id AND site_domain = :site_domain LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':automation_id' => $automation_id,
                ':site_domain' => $site_domain
            ]);
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['id'] : false;
            
        } catch (\PDOException $e) {
            error_log("Database error in getWebhookIdByAutomationId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update automation status
     */
    public function updateAutomationStatus($id, $site_domain, $status) {
        try {
            $timestamp = $this->isMySQL ? "CURRENT_TIMESTAMP" : "datetime('now')";
            $query = "UPDATE automations SET 
                    status = :status,
                    updated_at = $timestamp
                    WHERE id = :id AND site_domain = :site_domain";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':id' => $id,
                ':site_domain' => $site_domain,
                ':status' => $status
            ]);
            
            return $stmt->rowCount() > 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in updateAutomationStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get automations by status
     */
    public function getAutomationsByStatus($site_domain, $status, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT * FROM automations 
                    WHERE site_domain = :site_domain AND status = :status 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':status' => $status,
                ':limit' => (int)$limit,
                ':offset' => (int)$offset
            ]);
            
            $automations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($automations as &$automation) {
                if ($automation['variables']) {
                    $automation['variables'] = json_decode($automation['variables'], true);
                }
            }
            
            return $automations;
            
        } catch (\PDOException $e) {
            error_log("Database error in getAutomationsByStatus: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get automations by event type
     */
    public function getAutomationsByEvent($site_domain, $event_type, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT * FROM automations 
                    WHERE site_domain = :site_domain AND joonweb_event = :event_type 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':event_type' => $event_type,
                ':limit' => (int)$limit,
                ':offset' => (int)$offset
            ]);
            
            $automations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($automations as &$automation) {
                if ($automation['variables']) {
                    $automation['variables'] = json_decode($automation['variables'], true);
                }
            }
            
            return $automations;
            
        } catch (\PDOException $e) {
            error_log("Database error in getAutomationsByEvent: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active automations for event processing
     */
    public function getActiveAutomationsByEvent($site_domain, $event_type) {
        try {
            $query = "SELECT * FROM automations 
                    WHERE site_domain = :site_domain 
                    AND joonweb_event = :event_type 
                    AND status = 'active' 
                    ORDER BY created_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':event_type' => $event_type
            ]);
            
            $automations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($automations as &$automation) {
                if ($automation['variables']) {
                    $automation['variables'] = json_decode($automation['variables'], true);
                }
            }
            
            return $automations;
            
        } catch (\PDOException $e) {
            error_log("Database error in getActiveAutomationsByEvent: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if automation name already exists for site
     */
    public function automationNameExists($site_domain, $name, $exclude_id = null) {
        try {
            if ($exclude_id) {
                $query = "SELECT COUNT(*) as count FROM automations 
                        WHERE site_domain = :site_domain 
                        AND name = :name 
                        AND id != :exclude_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':site_domain' => $site_domain,
                    ':name' => $name,
                    ':exclude_id' => $exclude_id
                ]);
            } else {
                $query = "SELECT COUNT(*) as count FROM automations 
                        WHERE site_domain = :site_domain 
                        AND name = :name";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':site_domain' => $site_domain,
                    ':name' => $name
                ]);
            }
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return ($result['count'] ?? 0) > 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in automationNameExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk update automation status
     */
    public function bulkUpdateAutomationStatus($site_domain, $ids, $status) {
        try {
            // Create placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $timestamp = $this->isMySQL ? "CURRENT_TIMESTAMP" : "datetime('now')";
            $query = "UPDATE automations SET 
                    status = ?,
                    updated_at = $timestamp
                    WHERE site_domain = ? 
                    AND id IN ($placeholders)";
            
            $params = array_merge([$status, $site_domain], $ids);
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
            
        } catch (\PDOException $e) {
            error_log("Database error in bulkUpdateAutomationStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get automation statistics
     */
    public function getAutomationStats($site_domain) {
        try {
            $query = "SELECT 
                    status,
                    COUNT(*) as count
                    FROM automations 
                    WHERE site_domain = :site_domain 
                    GROUP BY status";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':site_domain' => $site_domain]);
            
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $stats = [
                'active' => 0,
                'draft' => 0,
                'paused' => 0,
                'total' => 0
            ];
            
            foreach ($results as $row) {
                $status = $row['status'];
                $count = $row['count'];
                
                if (isset($stats[$status])) {
                    $stats[$status] = (int)$count;
                }
                $stats['total'] += (int)$count;
            }
            
            return $stats;
            
        } catch (\PDOException $e) {
            error_log("Database error in getAutomationStats: " . $e->getMessage());
            return [
                'active' => 0,
                'draft' => 0,
                'paused' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Search automations
     */
    public function searchAutomations($site_domain, $search_term, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $search_pattern = "%{$search_term}%";
            
            $query = "SELECT * FROM automations 
                    WHERE site_domain = :site_domain 
                    AND (name LIKE :search OR campaign LIKE :search OR joonweb_event LIKE :search)
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':search' => $search_pattern,
                ':limit' => (int)$limit,
                ':offset' => (int)$offset
            ]);
            
            $automations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($automations as &$automation) {
                if ($automation['variables']) {
                    $automation['variables'] = json_decode($automation['variables'], true);
                }
            }
            
            return $automations;
            
        } catch (\PDOException $e) {
            error_log("Database error in searchAutomations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get events by status
     */
    public function getEventsByStatus($site_domain, $status, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT te.*, a.name as automation_name 
                    FROM triggered_events te 
                    LEFT JOIN automations a ON te.automation_id = a.id 
                    WHERE te.site_domain = :site_domain AND te.status = :status 
                    ORDER BY te.triggered_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':site_domain' => $site_domain,
                ':status' => $status,
                ':limit' => (int)$limit,
                ':offset' => (int)$offset
            ]);
            
            $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($events as &$event) {
                if ($event['event_data']) {
                    $event['event_data'] = json_decode($event['event_data'], true);
                }
            }
            
            return $events;
            
        } catch (\PDOException $e) {
            error_log("Database error in getEventsByStatus: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old events
     */
    public function cleanupOldEvents($days_old = 30) {
        try {
            $timestamp = $this->isMySQL 
                ? "DATE_SUB(CURRENT_TIMESTAMP, INTERVAL $days_old DAY)" 
                : "datetime('now', '-$days_old days')";
            
            $query = "DELETE FROM triggered_events WHERE triggered_at < $timestamp";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->rowCount();
            
        } catch (\PDOException $e) {
            error_log("Database error in cleanupOldEvents: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get failed events for retry
     */
    public function getFailedEvents($site_domain = null, $limit = 10) {
        try {
            if ($site_domain) {
                $query = "SELECT te.*, a.name as automation_name 
                        FROM triggered_events te 
                        LEFT JOIN automations a ON te.automation_id = a.id 
                        WHERE te.site_domain = :site_domain AND te.status = 'failed' 
                        ORDER BY te.triggered_at ASC 
                        LIMIT :limit";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':site_domain' => $site_domain,
                    ':limit' => (int)$limit
                ]);
            } else {
                $query = "SELECT te.*, a.name as automation_name 
                        FROM triggered_events te 
                        LEFT JOIN automations a ON te.automation_id = a.id 
                        WHERE te.status = 'failed' 
                        ORDER BY te.triggered_at ASC 
                        LIMIT :limit";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':limit' => (int)$limit]);
            }
            
            $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($events as &$event) {
                if ($event['event_data']) {
                    $event['event_data'] = json_decode($event['event_data'], true);
                }
            }
            
            return $events;
            
        } catch (\PDOException $e) {
            error_log("Database error in getFailedEvents: " . $e->getMessage());
            return [];
        }
    }
}
?>