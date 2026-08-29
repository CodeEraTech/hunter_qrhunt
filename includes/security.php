<?php
declare(strict_types=1);

function send_security_headers(): void
{
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (($_SERVER['HTTPS'] ?? 'off') !== 'off') header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

function rate_limit(string $key, int $max, int $window): bool
{
    $now = time(); $bucket = $_SESSION['_rates'][$key] ?? ['start' => $now, 'count' => 0];
    if ($now - $bucket['start'] >= $window) $bucket = ['start' => $now, 'count' => 0];
    $bucket['count']++; $_SESSION['_rates'][$key] = $bucket;
    return $bucket['count'] <= $max;
}

function log_security(?int $userId, string $event, string $risk, string $description, array $metadata = []): void
{
    try {
        $stmt = db()->prepare('INSERT INTO security_events (user_id,event_type,risk_level,ip_address,device_id,user_agent,description,metadata,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([$userId, $event, $risk, client_ip(), $metadata['device_id'] ?? null, user_agent(), $description, safe_json($metadata)]);
    } catch (Throwable $e) { error_log('Security log failure: ' . $e->getMessage()); }
}

function encrypt_secret(string $plaintext): string
{
    global $config;
    if (strlen((string) $config['key']) < 32 || $config['key'] === 'replace-with-a-long-random-secret') throw new RuntimeException('APP_KEY must be a generated secret of at least 32 characters.');
    $key = hash('sha256', (string) $config['key'], true); $iv = random_bytes(12); $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) throw new RuntimeException('Could not encrypt secret.');
    return base64_encode($iv . $tag . $cipher);
}

function decrypt_secret(string $encoded): string
{
    global $config;
    if (strlen((string) $config['key']) < 32 || $config['key'] === 'replace-with-a-long-random-secret') throw new RuntimeException('APP_KEY must be a generated secret of at least 32 characters.');
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 29) throw new RuntimeException('Invalid encrypted secret.');
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', hash('sha256', (string) $config['key'], true), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    if ($plain === false) throw new RuntimeException('Could not decrypt secret.');
    return $plain;
}

function base32_decode_secret(string $value): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $value = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $value) ?? ''); $bits = ''; $output = '';
    foreach (str_split($value) as $char) { $index = strpos($alphabet, $char); if ($index !== false) $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT); }
    foreach (str_split($bits, 8) as $byte) if (strlen($byte) === 8) $output .= chr(bindec($byte));
    return $output;
}

function verify_totp(string $secret, string $code, int $window = 1): bool
{
    if (!preg_match('/^\d{6}$/', $code)) return false; $key = base32_decode_secret($secret); $counter = intdiv(time(), 30);
    for ($offset = -$window; $offset <= $window; $offset++) {
        $value = $counter + $offset; $binary = pack('N2', intdiv($value, 4294967296), $value % 4294967296); $hash = hash_hmac('sha1', $binary, $key, true); $pos = ord($hash[19]) & 0x0f;
        $number = ((ord($hash[$pos]) & 0x7f) << 24) | ((ord($hash[$pos + 1]) & 0xff) << 16) | ((ord($hash[$pos + 2]) & 0xff) << 8) | (ord($hash[$pos + 3]) & 0xff);
        if (hash_equals(str_pad((string) ($number % 1000000), 6, '0', STR_PAD_LEFT), $code)) return true;
    }
    return false;
}
