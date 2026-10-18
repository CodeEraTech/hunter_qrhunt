<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;

    $host = (string) env('DB_HOST', 'localhost');
    $port = (string) env('DB_PORT', '3306');
    $name = (string) env('DB_NAME', '');
    $user = (string) env('DB_USER', '');
    $pass = (string) env('DB_PASS', '');

    if ($name === '' || $user === '') {
        throw new RuntimeException("Database configuration is missing. Please define DB_NAME, DB_USER, and DB_PASS in your .env file.");
    }

    $hostsToTry = array_unique([$host, 'localhost', '127.0.0.1']);
    $lastException = null;

    foreach ($hostsToTry as $h) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $h, $port, $name);
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
        }
    }

    throw new PDOException(sprintf(
        "Database connection failed (%s). Attempted connecting to '%s' as user '%s'. Please check your credentials in .env.",
        $lastException ? $lastException->getMessage() : 'Unknown error',
        $name,
        $user
    ));
}

