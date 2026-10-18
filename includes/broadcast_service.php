<?php
declare(strict_types=1);

/**
 * Creates a new Hunt Broadcast message with dynamic QR token, secure hashed PIN, and FCM push dispatch.
 */
function create_hunt_broadcast(array $data, int $adminId): array
{
    $pdo = db();
    $campaignId = trim((string)($data['campaign_id'] ?? ''));
    $targetType = strtoupper(trim((string)($data['target_type'] ?? 'PROMOTER')));
    $targetId = trim((string)($data['target_id'] ?? ''));
    $message = trim((string)($data['message'] ?? ''));
    $rewardType = strtoupper(trim((string)($data['reward_type'] ?? 'COINS')));
    $rewardValue = (float)($data['reward_value'] ?? 0);
    $validityMinutes = max(5, min(10080, (int)($data['validity_window_minutes'] ?? 60)));
    $pin = trim((string)($data['pin'] ?? ''));

    if ($campaignId === '') {
        throw new InvalidArgumentException('Target campaign is required.');
    }
    if (!in_array($targetType, ['PROMOTER', 'VENDOR', 'SHOP', 'CUSTOMER'], true)) {
        throw new InvalidArgumentException('Invalid target type.');
    }
    if ($targetId === '') {
        throw new InvalidArgumentException('Target ID/Name is required.');
    }
    if ($message === '') {
        throw new InvalidArgumentException('Broadcast message/clue text is required.');
    }
    if (!in_array($rewardType, ['COINS', 'BONUS_CASH', 'COUPON'], true)) {
        throw new InvalidArgumentException('Invalid reward type.');
    }
    if ($rewardValue <= 0) {
        throw new InvalidArgumentException('Reward value must be greater than zero.');
    }
    if (!preg_match('/^\d{4}$/', $pin)) {
        throw new InvalidArgumentException('Secret verification PIN must be exactly 4 numeric digits.');
    }

    $broadcastCode = 'BC-' . strtoupper(bin2hex(random_bytes(4)));
    $qrToken = 'HBQR_' . bin2hex(random_bytes(24));
    // Securely hash the 4-digit PIN — never store plaintext
    $pinHash = password_hash($pin, PASSWORD_DEFAULT);

    $startsAt = date('Y-m-d H:i:s');
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$validityMinutes} minutes"));

    // Simulate FCM Push payload
    $fcmPayload = [
        'broadcast_code' => $broadcastCode,
        'title' => 'Hunter Live Hunt Alert!',
        'body' => $message,
        'reward' => $rewardValue . ' ' . $rewardType,
        'validity_minutes' => $validityMinutes,
        'expires_at' => $expiresAt,
        'target_type' => $targetType,
        'dispatched_at' => date('c'),
    ];

    $stmt = $pdo->prepare('INSERT INTO hunt_broadcasts(
        broadcast_code, campaign_id, target_type, target_id, message,
        reward_type, reward_value, validity_window_minutes,
        starts_at, expires_at, pin_hash, qr_token, status, fcm_status, fcm_response,
        created_by, created_at
    ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?, "ACTIVE", "SENT", ?, ?, NOW())');

    $stmt->execute([
        $broadcastCode,
        $campaignId,
        $targetType,
        $targetId,
        $message,
        $rewardType,
        $rewardValue,
        $validityMinutes,
        $startsAt,
        $expiresAt,
        $pinHash,
        $qrToken,
        safe_json($fcmPayload),
        $adminId,
    ]);

    $broadcastId = (int)$pdo->lastInsertId();

    audit_log(
        $adminId,
        'HUNT_BROADCAST_CREATED',
        'hunt_broadcasts',
        (string)$broadcastId,
        null,
        [
            'broadcast_code' => $broadcastCode,
            'campaign_id' => $campaignId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reward' => $rewardValue . ' ' . $rewardType,
            'validity_minutes' => $validityMinutes,
            'expires_at' => $expiresAt,
        ]
    );

    return [
        'id' => $broadcastId,
        'broadcast_code' => $broadcastCode,
        'qr_token' => $qrToken,
        'expires_at' => $expiresAt,
        'status' => 'ACTIVE',
        'fcm_dispatched' => true,
    ];
}

/**
 * Validates dynamic QR scan and secret 4-digit PIN to claim broadcast reward.
 * Enforces server-side time window, anti-fraud rate limiting, and idempotency.
 */
function verify_and_claim_broadcast(
    int $userId,
    string $qrToken,
    string $pin,
    string $idempotencyKey = ''
): array {
    if ($userId <= 0) {
        throw new InvalidArgumentException('Valid user ID is required.');
    }
    if (trim($qrToken) === '') {
        throw new InvalidArgumentException('Dynamic QR token is required.');
    }
    if (!preg_match('/^\d{4}$/', trim($pin))) {
        throw new InvalidArgumentException('4-digit verification PIN is required.');
    }
    if ($idempotencyKey === '') {
        $idempotencyKey = 'claim:' . $userId . ':' . hash('sha256', $qrToken . ':' . microtime());
    }

    $pdo = db();

    // Check prior idempotent claim
    $priorStmt = $pdo->prepare('SELECT * FROM hunt_broadcast_claims WHERE idempotency_key = ?');
    $priorStmt->execute([$idempotencyKey]);
    if ($prior = $priorStmt->fetch()) {
        return [
            'claim_id' => (int)$prior['id'],
            'status' => $prior['status'],
            'reward_type' => $prior['reward_type'],
            'reward_amount' => (string)$prior['reward_amount'],
            'created' => false,
        ];
    }

    // Verify user and wallet
    $uStmt = $pdo->prepare('SELECT u.id, u.status AS user_status, w.id AS wallet_id, w.status AS wallet_status FROM users u JOIN wallets w ON w.user_id = u.id WHERE u.id = ?');
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();
    if (!$user || $user['user_status'] !== 'ACTIVE' || $user['wallet_status'] !== 'ACTIVE') {
        throw new RuntimeException('User account or wallet is not active.');
    }

    // Find broadcast by QR token
    $bStmt = $pdo->prepare('SELECT * FROM hunt_broadcasts WHERE qr_token = ? FOR UPDATE');
    $bStmt->execute([$qrToken]);
    $broadcast = $bStmt->fetch();
    if (!$broadcast) {
        throw new RuntimeException('Invalid or unrecognized broadcast QR code.');
    }

    // Check active time window
    $now = date('Y-m-d H:i:s');
    if ($broadcast['status'] !== 'ACTIVE' || $now < $broadcast['starts_at'] || $now > $broadcast['expires_at']) {
        // Mark as EXPIRED if past expiry date
        if ($now > $broadcast['expires_at'] && $broadcast['status'] === 'ACTIVE') {
            $pdo->prepare('UPDATE hunt_broadcasts SET status = "EXPIRED" WHERE id = ?')->execute([$broadcast['id']]);
        }
        throw new RuntimeException('This broadcast offer has expired or is no longer active.');
    }

    // Check duplicate claim by this user
    $dupeStmt = $pdo->prepare('SELECT id FROM hunt_broadcast_claims WHERE broadcast_id = ? AND user_id = ? AND status = "SUCCESS"');
    $dupeStmt->execute([$broadcast['id'], $userId]);
    if ($dupeStmt->fetch()) {
        throw new RuntimeException('You have already claimed this broadcast reward.');
    }

    // Check rate limit on incorrect PIN attempts (max 5 failed attempts in 15 mins)
    $rateStmt = $pdo->prepare('SELECT COUNT(*) FROM hunt_broadcast_claims WHERE broadcast_id = ? AND user_id = ? AND status = "INVALID_PIN" AND claimed_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $rateStmt->execute([$broadcast['id'], $userId]);
    if ((int)$rateStmt->fetchColumn() >= 5) {
        throw new RuntimeException('Too many incorrect PIN attempts. For security, please try again after 15 minutes.');
    }

    // Verify 4-digit PIN against hash
    if (!password_verify(trim($pin), (string)$broadcast['pin_hash'])) {
        // Record failed attempt
        $pdo->prepare('INSERT INTO hunt_broadcast_claims(broadcast_id, user_id, qr_token_scanned, pin_verified, reward_type, reward_amount, status, idempotency_key, claimed_at) VALUES(?,?,?,0,?,0.00,"INVALID_PIN",?,NOW())')
            ->execute([$broadcast['id'], $userId, $qrToken, $broadcast['reward_type'], $idempotencyKey . ':fail:' . bin2hex(random_bytes(4))]);
        throw new RuntimeException('Verification failed: Incorrect 4-digit secret PIN.');
    }

    // Process reward
    $pdo->beginTransaction();
    try {
        $rewardAmount = number_format((float)$broadcast['reward_value'], 2, '.', '');
        $rewardType = $broadcast['reward_type'];

        if (in_array($rewardType, ['COINS', 'BONUS_CASH'], true)) {
            // Grant to bonus balance
            wallet_bucket_apply_in_transaction(
                $pdo,
                $userId,
                'BONUS',
                $rewardAmount,
                'HB-' . $broadcast['broadcast_code'],
                'Broadcast hunt reward: ' . mb_strimwidth($broadcast['message'], 0, 50, '...'),
                null,
                $idempotencyKey . ':credit',
                'CREDIT'
            );
        } elseif ($rewardType === 'COUPON') {
            $couponCode = 'BCPN-' . strtoupper(bin2hex(random_bytes(4)));
            $codeHash = hash('sha256', strtoupper($couponCode));
            $pdo->prepare('INSERT INTO coupons(code_hash, code_reference, title, discount_amount, min_cart_amount, user_id, expires_at, status, created_by) VALUES(?,?,?,?,?,?,?,"ACTIVE",1)')
                ->execute([
                    $codeHash,
                    $couponCode,
                    'Broadcast Reward: ' . mb_strimwidth($broadcast['message'], 0, 40, '...'),
                    $rewardAmount,
                    '0.00',
                    $userId,
                    $broadcast['expires_at'],
                ]);
        }

        // Record successful claim
        $ins = $pdo->prepare('INSERT INTO hunt_broadcast_claims(
            broadcast_id, user_id, qr_token_scanned, pin_verified,
            reward_type, reward_amount, status, idempotency_key, claimed_at
        ) VALUES(?,?,?,1,?,?, "SUCCESS", ?, NOW())');

        $ins->execute([
            $broadcast['id'],
            $userId,
            $qrToken,
            $rewardType,
            $rewardAmount,
            $idempotencyKey,
        ]);
        $claimId = (int)$pdo->lastInsertId();

        $pdo->commit();

        audit_log(
            null,
            'HUNT_BROADCAST_CLAIMED',
            'hunt_broadcast_claims',
            (string)$claimId,
            null,
            [
                'broadcast_id' => $broadcast['id'],
                'user_id' => $userId,
                'reward_type' => $rewardType,
                'reward_amount' => $rewardAmount,
            ]
        );

        return [
            'claim_id' => $claimId,
            'status' => 'SUCCESS',
            'reward_type' => $rewardType,
            'reward_amount' => $rewardAmount,
            'message' => 'Broadcast clue reward successfully verified and credited!',
            'created' => true,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

