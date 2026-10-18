<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $host = (string) env('DB_HOST', 'localhost');
    $port = (string) env('DB_PORT', '3306');
    $name = (string) env('DB_NAME', 'hunter_wallet');
    $user = (string) env('DB_USER', 'root');
    $pass = (string) env('DB_PASS', '');

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        throw new PDOException(sprintf(
            "Database connection failed (%s). Attempted connecting to database '%s' at '%s' with user '%s'. Please check DB_NAME, DB_USER, and DB_PASS in your .env or .env.example file.",
            $e->getMessage(),
            $name,
            $host,
            $user
        ), (int) $e->getCode(), $e);
    }
}

