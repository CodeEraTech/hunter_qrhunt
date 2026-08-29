<?php
declare(strict_types=1);

function load_env(string $path): void
{
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false) putenv("{$key}={$value}");
    }
}

load_env(dirname(__DIR__) . '/.env');

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

const APP_ROOT = __DIR__ . '/..';
date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Kolkata'));

return [
    'env' => env('APP_ENV', 'production'),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'key' => (string) env('APP_KEY', ''),
    'session_timeout' => (int) env('SESSION_TIMEOUT', 1800),
    'cookie_secure' => filter_var(env('COOKIE_SECURE', '1'), FILTER_VALIDATE_BOOL),
    'razorpay_webhook_secret' => (string) env('RAZORPAY_WEBHOOK_SECRET', ''),
    'phonepe_webhook_secret' => (string) env('PHONEPE_WEBHOOK_SECRET', ''),
];
