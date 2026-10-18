<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/security.php';
require_once APP_ROOT . '/includes/csrf.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/permissions.php';
require_once APP_ROOT . '/includes/audit.php';
require_once APP_ROOT . '/includes/wallet.php';
require_once APP_ROOT . '/includes/wallet_console.php';
require_once APP_ROOT . '/includes/withdrawals.php';
require_once APP_ROOT . '/includes/hunts.php';
require_once APP_ROOT . '/includes/payments.php';
require_once APP_ROOT . '/includes/vendors.php';
require_once APP_ROOT . '/includes/video.php';
require_once APP_ROOT . '/includes/question_service.php';
require_once APP_ROOT . '/includes/broadcast_service.php';
require_once APP_ROOT . '/includes/targeted_offers_service.php';

ini_set('display_errors', $config['env'] === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/logs/application.log');
error_reporting(E_ALL);
set_exception_handler(static function (Throwable $e) use ($config): never {
    error_log(sprintf("Uncaught %s: %s in %s:%d", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    http_response_code(500);
    $isDebug = ($config['env'] === 'development' || filter_var(env('APP_DEBUG', '0'), FILTER_VALIDATE_BOOL));
    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => false,
            'message' => $isDebug ? $e->getMessage() : 'Something went wrong. Please try again.',
            'data' => $isDebug ? ['exception' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()] : null
        ]);
    } else {
        if ($isDebug) {
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Application Error</title><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#0d1117;color:#e6edf3;padding:40px;line-height:1.6}h1{color:#f85149;margin-top:0}.box{background:#161b22;border:1px solid #30363d;border-radius:8px;padding:20px;margin-bottom:20px}.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;background:#21262d;padding:3px 6px;border-radius:4px;color:#79c0ff}.trace{white-space:pre-wrap;font-family:ui-monospace,monospace;font-size:12px;color:#8b949e}</style></head><body>';
            echo '<h1>⚠ Application Error (' . htmlspecialchars(get_class($e)) . ')</h1>';
            echo '<div class="box">';
            echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> <span class="mono">' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</span></p>';
            if (!file_exists(APP_ROOT . '/.env')) {
                echo '<p style="color:#e3b341"><strong>Notice:</strong> No <code>.env</code> file detected. Please create <code>.env</code> in your root directory from <code>.env.example</code> with valid database credentials.</p>';
            }
            echo '</div>';
            echo '<div class="box"><div class="trace">' . htmlspecialchars($e->getTraceAsString()) . '</div></div>';
            echo '</body></html>';
        } else {
            echo 'Something went wrong. Please try again.';
        }
    }
    exit;
});

$isApiRequest=str_contains($_SERVER['REQUEST_URI']??'', '/api/');
if (!$isApiRequest && session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1');
    session_name(str_contains($_SERVER['REQUEST_URI']??'', '/vendor/')?'hunter_vendor':'hunter_admin');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $config['cookie_secure'],
        'httponly' => true, 'samesite' => 'Strict',
    ]);
    session_start();
}

send_security_headers();
if(!$isApiRequest)enforce_session_timeout((int) $config['session_timeout']);
