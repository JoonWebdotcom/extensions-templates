<?php
namespace JoonWeb\EmbedApp\Database\Interfaces;

interface DatabaseInterface {
    public function connect();
    public function insert($table, $data);
    public function insertOrUpdate($table, $data, $uniqueKey);
    public function update($table, $data, $where);
    public function select($table, $where = [], $columns = '*');
    public function delete($table, $where);
    public function query($query, $params = []);
    public function beginTransaction();
    public function commit();
    public function rollback();
    public function getLastInsertId();
    public function tableExists($tableName);
}
?>