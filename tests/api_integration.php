<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$base = rtrim($argv[1] ?? 'http://127.0.0.1:8099', '/');$identity=getenv('TEST_API_IDENTITY');$password=getenv('TEST_API_PASSWORD');$deviceId=getenv('TEST_API_DEVICE_ID');if(!$identity||!$password||!$deviceId)throw new RuntimeException('TEST_API_IDENTITY, TEST_API_PASSWORD, and TEST_API_DEVICE_ID are required.');

function request(string $base, string $path, string $method = 'GET', array $headers = [], ?array $body = null): array
{
    $handle = curl_init($base . $path);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 10]);
    if ($body !== null) curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    $raw = curl_exec($handle); $code = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    if ($raw === false) throw new RuntimeException(curl_error($handle));
    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    return [$code, $decoded];
}

[$code] = request($base, '/api/wallet/balance.php');
if ($code !== 401) throw new RuntimeException('Unauthenticated wallet request was not rejected.');
[$code, $login] = request($base, '/api/auth/login.php', 'POST', ['Content-Type: application/json'], ['identity'=>$identity,'password'=>$password,'device_id'=>$deviceId]);
if ($code !== 200 || empty($login['data']['token'])) throw new RuntimeException('API login failed.');
$token = $login['data']['token'];
[$code] = request($base, '/api/wallet/balance.php', 'GET', ['Authorization: Bearer ' . $token]);
if ($code !== 401) throw new RuntimeException('Missing device header was not rejected.');
$headers = ['Authorization: Bearer ' . $token, 'X-Device-ID: ' . $deviceId];
[$code, $balance] = request($base, '/api/wallet/balance.php', 'GET', $headers);
if ($code !== 200 || $balance['data']['balance'] !== '500.00') throw new RuntimeException('Authorized balance request failed.');
$withdrawalHeaders = [...$headers, 'Content-Type: application/json', 'Idempotency-Key: integration-api-key-000001'];
$payload = ['amount'=>'150.00','payment_method'=>'UPI','payment_account'=>'123456789012'];
[$firstCode, $first] = request($base, '/api/withdrawals/create.php', 'POST', $withdrawalHeaders, $payload);
[$secondCode, $second] = request($base, '/api/withdrawals/create.php', 'POST', $withdrawalHeaders, $payload);
if ($firstCode !== 201 || $secondCode !== 201 || $first['data']['withdrawal_id'] !== $second['data']['withdrawal_id']) throw new RuntimeException('Withdrawal idempotency failed.');
[$code, $withdrawals] = request($base, '/api/withdrawals/index.php', 'GET', $headers);
if ($code !== 200 || count($withdrawals['data']['items']) !== 1 || $withdrawals['data']['items'][0]['payment_account_masked'] !== 'XXXX XXXX 9012') throw new RuntimeException('Withdrawal masking or listing failed.');
[$code, $balance] = request($base, '/api/wallet/balance.php', 'GET', $headers);
if ($balance['data']['balance'] !== '500.00') throw new RuntimeException('Pending withdrawal changed wallet balance.');
[$code] = request($base, '/api/auth/logout.php', 'POST', $headers);
if ($code !== 200) throw new RuntimeException('API logout failed.');
[$code] = request($base, '/api/wallet/balance.php', 'GET', $headers);
if ($code !== 401) throw new RuntimeException('Revoked API token remained valid.');
echo "API integration assertions passed\n";
