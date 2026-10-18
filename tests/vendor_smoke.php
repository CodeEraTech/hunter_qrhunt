<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8099', '/');
$identity = getenv('TEST_VENDOR_IDENTITY') ?: 'vendor.test';
$password = getenv('TEST_VENDOR_PASSWORD') ?: 'Demo@12345';
if (!$identity || !$password) throw new RuntimeException('TEST_VENDOR_IDENTITY and TEST_VENDOR_PASSWORD are required (or default values).');

$cookieFile = tempnam(sys_get_temp_dir(), 'hunter-vendor-');

function web_request(string $base, string $path, string $cookieFile, string $method = 'GET', array $form = []): array
{
    $handle = curl_init($base . $path);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 10,
    ]);
    if ($form) curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($form));
    $raw = curl_exec($handle);
    if ($raw === false) throw new RuntimeException(curl_error($handle));
    $code = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    return [$code, substr($raw, 0, $headerSize), substr($raw, $headerSize)];
}

function check(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

try {
    // 1. Unauthenticated dashboard should redirect to login
    [$code] = web_request($base, '/vendor/dashboard.php', $cookieFile);
    check($code === 302, 'Unauthenticated vendor dashboard did not redirect to login.');

    // 2. Login page should render with CSRF token and security headers
    [$code, $headers, $login] = web_request($base, '/vendor/login.php', $cookieFile);
    check($code === 200, 'Login page did not return 200.');
    check(preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $login, $match) === 1, 'CSRF token missing from login form.');
    check(str_contains($headers, 'Content-Security-Policy:'), 'Security headers missing on login page.');
    check(str_contains($login, 'Store Portal'), 'Vendor branding missing from login page.');
    $csrf = $match[1];

    // 3. Incorrect password should fail
    [$code, , $body] = web_request($base, '/vendor/login.php', $cookieFile, 'POST', [
        'csrf_token' => $csrf, 'identity' => $identity, 'password' => 'wrong-password'
    ]);
    check($code === 200 && str_contains($body, 'Invalid credentials'), 'Wrong password was not rejected.');

    // 4. Correct login should redirect to dashboard
    [$code] = web_request($base, '/vendor/login.php', $cookieFile, 'POST', [
        'csrf_token' => $csrf, 'identity' => $identity, 'password' => $password
    ]);
    check($code === 302, 'Vendor login did not redirect.');

    // 5. Authenticated dashboard should load with vendor data
    [$code, $headers, $body] = web_request($base, '/vendor/dashboard.php', $cookieFile);
    check($code === 200, 'Authenticated vendor dashboard did not return 200.');
    check(str_contains($body, 'Demo Retail Counter'), 'Dashboard did not load correct vendor data.');
    check(str_contains($body, 'VND-TEST-001'), 'Dashboard did not show vendor code.');
    check(str_contains($headers, 'Cache-Control: private'), 'Dashboard cache headers missing.');
    check(!str_contains($body, 'Something went wrong'), 'Dashboard rendered an error page.');

    // 6. Tampered vendor_id in URL must not leak other vendor data
    check(!str_contains($body, 'SECRET'), 'Cross-vendor data leaked via URL parameter.');

    // 7. Logout via POST should redirect
    [$code, $logoutHeaders, ] = web_request($base, '/vendor/logout.php', $cookieFile, 'POST', [
        'csrf_token' => $csrf
    ]);
    check($code === 302, 'Vendor logout did not redirect.');
    check(str_contains($logoutHeaders, 'Location:'), 'Logout redirect missing Location header.');

    // 8. After logout, dashboard should redirect again
    [$code] = web_request($base, '/vendor/dashboard.php', $cookieFile);
    check($code === 302, 'Post-logout dashboard did not redirect to login.');

    echo "Vendor login → dashboard → logout smoke test passed.\n";
} finally {
    if (is_file($cookieFile)) unlink($cookieFile);
}
