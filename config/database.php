<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;

    $name = (string) env('DB_NAME', 'scancash_hunter_wallet');
    $user = (string) env('DB_USER', 'scancash_hunter_admin');
    $pass = (string) env('DB_PASS', '%hy%KCgVvj~Jeh*$');
    $port = (string) env('DB_PORT', '3306');
    $configuredHost = (string) env('DB_HOST', 'localhost');

    // Try configured host first, then localhost (UNIX socket), then 127.0.0.1 (TCP)
    $hostsToTry = array_unique([$configuredHost, 'localhost', '127.0.0.1']);
    $lastException = null;

    foreach ($hostsToTry as $host) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
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
        "Database connection failed (%s). Attempted connecting to database '%s' as user '%s'.",
        $lastException ? $lastException->getMessage() : 'Unknown error',
        $name,
        $user
    ));
}

