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
require_once APP_ROOT . '/includes/withdrawals.php';
require_once APP_ROOT . '/includes/hunts.php';
require_once APP_ROOT . '/includes/payments.php';
require_once APP_ROOT . '/includes/vendors.php';
require_once APP_ROOT . '/includes/video.php';

ini_set('display_errors', $config['env'] === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/logs/application.log');
error_reporting(E_ALL);
set_exception_handler(static function (Throwable $e): never {
    error_log(sprintf("Uncaught %s: %s in %s:%d", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    http_response_code(500);
    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => false, 'message' => 'Something went wrong. Please try again.', 'data' => null]);
    } else {
        echo 'Something went wrong. Please try again.';
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
