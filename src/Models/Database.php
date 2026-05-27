<?php

namespace App\Models;

class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../Config/database.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";

        $this->pdo = new \PDO($dsn, $config['user'], $config['pass'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
