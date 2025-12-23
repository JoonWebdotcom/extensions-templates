<?php
namespace JoonWeb\EmbedApp\Database\Factory;

use JoonWeb\EmbedApp\Database\Adapters\MySQLDatabase;
use JoonWeb\EmbedApp\Database\Adapters\SQLiteDatabase;

class DatabaseFactory {
    public static function create($config) {
        $driver = $config['driver'] ?? 'sqlite';
        
        switch ($driver) {
            case 'mysql':
                return new MySQLDatabase($config);
            case 'sqlite':
                return new SQLiteDatabase($config);
            default:
                throw new \Exception("Unsupported database driver: {$driver}");
        }
    }
}
?>