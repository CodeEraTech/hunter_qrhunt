<?php
declare(strict_types=1);

require dirname(__DIR__) . '/_bootstrap.php';
require_post();

$user = api_user();
api_rate_limit($user, 'broadcast.claim', 15, 300);

$data = json_input();
$qrToken = trim((string)($data['qr_token'] ?? ''));
$pin = trim((string)($data['pin'] ?? ''));
$idempotencyKey = trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ($data['idempotency_key'] ?? '')));

try {
    $result = verify_and_claim_broadcast((int)$user['id'], $qrToken, $pin, $idempotencyKey);
    json_response(true, $result['message'] ?? 'Broadcast clue reward successfully claimed!', $result, $result['created'] ? 201 : 200);
} catch (InvalidArgumentException $e) {
    json_response(false, $e->getMessage(), null, 422);
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 409);
}

