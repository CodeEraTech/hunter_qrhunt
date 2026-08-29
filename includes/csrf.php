<?php
declare(strict_types=1);

function csrf_token(): string { return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) json_response(false, 'Invalid security token', null, 419);
        http_response_code(419); exit('Invalid security token. Please refresh and try again.');
    }
}

