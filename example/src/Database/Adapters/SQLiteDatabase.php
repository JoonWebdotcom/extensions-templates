<?php
namespace JoonWeb\EmbedApp\Database\Adapters;

class SQLiteDatabase extends BaseDatabase {
    public function connect() {
        try {
            $dbPath = $this->config['path'] ?? __DIR__ . '/../../../app-db.sqlite';
            $dir = dirname($dbPath);
            
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $this->connection = new \PDO(
                "sqlite:{$dbPath}",
                null,
                null,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
            
            // SQLite optimizations
            $this->connection->exec('PRAGMA foreign_keys = ON;');
            $this->connection->exec('PRAGMA busy_timeout = 5000;');
            $this->connection->exec('PRAGMA journal_mode = WAL;');
            
            $this->isConnected = true;
            return $this->connection;
        } catch (\PDOException $e) {
            throw new \Exception("SQLite connection failed: " . $e->getMessage());
        }
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($data);
    }
    
    public function insertOrUpdate($table, $data, $uniqueKey) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT OR REPLACE INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($data);
    }
    
    public function tableExists($tableName) {
        $stmt = $this->connection->prepare(
            "SELECT name FROM sqlite_master WHERE type='table' AND name=?"
        );
        $stmt->execute([$tableName]);
        return $stmt->fetch() !== false;
    }
    
    public function query($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }
    
    public function delete($table, $where) {
        $whereParts = [];
        $params = [];
        
        foreach ($where as $key => $value) {
            $whereParts[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        $query = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }
}
?>