<?php
namespace JoonWeb\EmbedApp\Database\Adapters;

class MySQLDatabase extends BaseDatabase {
    public function connect() {
        try {
            $this->connection = new \PDO(
                "mysql:host={$this->config['host']};dbname={$this->config['database']};charset=utf8mb4",
                $this->config['username'],
                $this->config['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
            $this->isConnected = true;
            return $this->connection;
        } catch (\PDOException $e) {
            throw new \Exception("MySQL connection failed: " . $e->getMessage());
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
        
        $updateParts = [];
        foreach ($data as $key => $value) {
            if ($key !== $uniqueKey) {
                $updateParts[] = "{$key} = VALUES({$key})";
            }
        }
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})
                  ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
        
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($data);
    }
    
    public function tableExists($tableName) {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) FROM information_schema.tables 
            WHERE table_schema = ? AND table_name = ?
        ");
        $stmt->execute([$this->config['database'], $tableName]);
        return $stmt->fetchColumn() > 0;
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