<?php
namespace JoonWeb\EmbedApp;
use PDO;
require_once __DIR__ . '/constants.php';
if(!defined('DB_MODULE')){ exit('CONSTANTS MISSING'); }

if(DB_MODULE == "sqllite"){
    // SQLite Database Class
    class Database {
        private $db_path;
        public $conn;

        public function __construct() {
            // Set SQLite database path relative to this file
            $this->db_path = __DIR__ . '/../app-db.sqllite';
        }

        public function connect() {
            try {
                // Ensure directory exists
                $dir = dirname($this->db_path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $this->conn = new PDO(
                    "sqlite:{$this->db_path}",
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_PERSISTENT => false
                    ]
                );

                // Enable foreign keys and set busy timeout
                $this->conn->exec('PRAGMA foreign_keys = ON;');
                $this->conn->exec('PRAGMA busy_timeout = 5000;');
                
                // Create tables if they don't exist
                $this->createTables();
                
            } catch (PDOException $e) {
                error_log("SQLite connection failed: " . $e->getMessage());
                return false;
            }
            return $this->conn;
        }

        // SQLite specific helper methods
        public function tableExists($tableName) {
            $stmt = $this->conn->prepare(
                "SELECT name FROM sqlite_master WHERE type='table' AND name=?"
            );
            $stmt->execute([$tableName]);
            return $stmt->fetch() !== false;
        }

        public function createTables() {
            // Create app_analytics table (matches MySQL structure)
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS app_analytics (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    site_domain TEXT NOT NULL,
                    event_type TEXT NOT NULL,
                    event_data TEXT,
                    user_agent TEXT,
                    ip_address TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Create indexes for app_analytics
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_analytics_site_domain ON app_analytics(site_domain)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_analytics_event_type ON app_analytics(event_type)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_analytics_created_at ON app_analytics(created_at)");

            // Create app_data table (matches MySQL structure)
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS app_data (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    site_domain TEXT NOT NULL,
                    data_key TEXT NOT NULL,
                    data_value TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(site_domain, data_key)
                )
            ");

            // Create indexes for app_data
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_data_site_domain ON app_data(site_domain)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_data_key ON app_data(data_key)");

            // Create sites table (matches your MySQL structure exactly)
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS sites (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    site_domain TEXT UNIQUE NOT NULL,
                    site_name TEXT,
                    access_token TEXT NOT NULL,
                    scope TEXT,
                    userid INTEGER,
                    name TEXT,
                    accountOwner INTEGER DEFAULT 0,
                    email TEXT,
                    currency TEXT,
                    timezone TEXT,
                    installed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    uninstalled_at DATETIME NULL,
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Create sessions table (for session management)
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS sessions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    session_id TEXT UNIQUE NOT NULL,
                    site_domain TEXT NOT NULL,
                    access_token TEXT NOT NULL,
                    scope TEXT,
                    expires_at DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }

        // Helper method to check if database is properly set up
        public function checkDatabase() {
            $tables = ['app_analytics', 'app_data', 'sites', 'sessions'];
            $missing = [];
            
            foreach ($tables as $table) {
                if (!$this->tableExists($table)) {
                    $missing[] = $table;
                }
            }
            
            return empty($missing) ? true : $missing;
        }
    }
} else {
    // MySQL Database Class
    class Database {
        private $host = DB_HOST;
        private $db_name = DB_NAME;
        private $username = DB_USER;
        private $password = DB_PASSWORD;
        public $conn;

        public function connect() {
            try {
                $this->conn = new PDO(
                    "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                return false;
            }
            return $this->conn;
        }
    }
}
?>
