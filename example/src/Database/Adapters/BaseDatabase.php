<?php
namespace JoonWeb\EmbedApp\Database\Adapters;

use JoonWeb\EmbedApp\Database\Interfaces\DatabaseInterface;

abstract class BaseDatabase implements DatabaseInterface {
    protected $connection;
    protected $config;
    protected $isConnected = false;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    abstract public function connect();
    abstract public function insert($table, $data);
    abstract public function insertOrUpdate($table, $data, $uniqueKey);
    
    // Common implementations for all databases
    public function update($table, $data, $where) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :set_{$key}";
            $params[":set_{$key}"] = $value;
        }
        
        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "{$key} = :where_{$key}";
            $params[":where_{$key}"] = $value;
        }
        
        $query = "UPDATE {$table} SET " . implode(', ', $setParts) . 
                 " WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }
    
    public function select($table, $where = [], $columns = '*') {
        $params = [];
        $whereClause = '';
        
        if (!empty($where)) {
            $whereParts = [];
            foreach ($where as $key => $value) {
                $whereParts[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
            $whereClause = " WHERE " . implode(' AND ', $whereParts);
        }
        
        $query = "SELECT {$columns} FROM {$table}{$whereClause}";
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getLastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
}
?>