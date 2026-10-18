<?php
declare(strict_types=1);

if (!function_exists('load_env')) {
    function load_env(string $path): void
    {
        if (!is_file($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            $GLOBALS['__APP_ENV'][$key] = $value;
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            if (function_exists('putenv')) {
                @putenv("{$key}={$value}");
            }
        }
    }
}

$baseDir = dirname(__DIR__);
if (is_file($baseDir . '/.env')) {
    load_env($baseDir . '/.env');
} elseif (is_file($baseDir . '/.env.example')) {
    load_env($baseDir . '/.env.example');
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        if (isset($GLOBALS['__APP_ENV'][$key])) {
            return $GLOBALS['__APP_ENV'][$key];
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        if (function_exists('getenv')) {
            $val = getenv($key);
            if ($val !== false) return $val;
        }
        return $default;
    }
}

const APP_ROOT = __DIR__ . '/..';
date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Kolkata'));

return [
    'env' => env('APP_ENV', 'production'),
    'url' => rtrim((string) env('APP_URL', 'https://scancash.online'), '/'),
    'key' => (string) env('APP_KEY', '5c0e35560256548dc53d84f26307b0263724067b679d0bd4ce0facab3f4f7f49'),
    'session_timeout' => (int) env('SESSION_TIMEOUT', 1800),
    'cookie_secure' => filter_var(env('COOKIE_SECURE', '1'), FILTER_VALIDATE_BOOL),
    'razorpay_webhook_secret' => (string) env('RAZORPAY_WEBHOOK_SECRET', ''),
    'phonepe_webhook_secret' => (string) env('PHONEPE_WEBHOOK_SECRET', ''),
];
