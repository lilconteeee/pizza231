<?php
namespace Models;
use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $db   = $_ENV['DB_NAME'] ?? 'gym_db';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';
            
            try {
                self::$pdo = new PDO(
                    "mysql:host=$host;dbname=$db;charset=utf8mb4",
                    $user, $pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
                );
            } catch (\PDOException $e) {
                die("Ошибка БД: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}