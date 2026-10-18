<?php
declare(strict_types=1);

/**
 * Normalizes question answer string for reliable matching.
 * Converts to lowercase, strips punctuation, collapses whitespace, trims.
 */
function normalize_question_answer(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    // Remove all punctuation marks
    $text = preg_replace('/[[:punct:]]/u', ' ', $text) ?? $text;
    // Collapse multiple whitespace characters into single space
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

/**
 * Checks whether a submitted answer matches the primary answer or any alternative synonyms.
 */
function match_question_answer(string $submitted, string $primary, array $synonyms = []): bool
{
    $normSubmitted = normalize_question_answer($submitted);
    if ($normSubmitted === '') {
        return false;
    }

    if (hash_equals(normalize_question_answer($primary), $normSubmitted)) {
        return true;
    }

    foreach ($synonyms as $syn) {
        if (is_string($syn) && hash_equals(normalize_question_answer($syn), $normSubmitted)) {
            return true;
        }
    }

    return false;
}

/**
 * Validates and processes a question answer attempt.
 * Rewards correct attempts, safely applies negative marking penalties on incorrect attempts
 * without causing negative wallet balances, and returns fallback offers.
 * Fully protected by idempotency key.
 */
function process_question_attempt(
    int $userId,
    int $riddleId,
    string $submittedAnswer,
    ?int $collectionId = null,
    string $idempotencyKey = ''
): array {
    if ($userId <= 0 || $riddleId <= 0) {
        throw new InvalidArgumentException('Valid user ID and question ID are required.');
    }
    if (trim($submittedAnswer) === '') {
        throw new InvalidArgumentException('Submitted answer cannot be empty.');
    }
    if ($idempotencyKey === '') {
        $idempotencyKey = 'attempt:' . $userId . ':' . $riddleId . ':' . hash('sha256', $submittedAnswer . ':' . microtime());
    }

    $pdo = db();

    // Check idempotency first
    $stmtPrior = $pdo->prepare('SELECT * FROM question_attempts WHERE idempotency_key = ?');
    $stmtPrior->execute([$idempotencyKey]);
    if ($prior = $stmtPrior->fetch()) {
        return [
            'attempt_id' => (int)$prior['id'],
            'is_correct' => (bool)$prior['is_correct'],
            'penalty_applied' => (string)$prior['penalty_applied'],
            'reward_type' => $prior['reward_type'],
            'reward_amount' => (string)$prior['reward_amount'],
            'fallback_offer' => $prior['fallback_offer'],
            'created' => false,
        ];
    }

    // Fetch user and wallet
    $uStmt = $pdo->prepare('SELECT u.id, u.status AS user_status, w.id AS wallet_id, w.balance, w.bonus_balance, w.status AS wallet_status FROM users u JOIN wallets w ON w.user_id = u.id WHERE u.id = ?');
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();
    if (!$user || $user['user_status'] !== 'ACTIVE' || $user['wallet_status'] !== 'ACTIVE') {
        throw new RuntimeException('User or wallet is not active.');
    }

    // Fetch riddle
    $rStmt = $pdo->prepare('SELECT * FROM riddle_pool WHERE riddle_id = ?');
    $rStmt->execute([$riddleId]);
    $riddle = $rStmt->fetch();
    if (!$riddle || $riddle['status'] !== 'ACTIVE') {
        throw new RuntimeException('Question is not active or does not exist.');
    }

    // Fetch collection if provided or if riddle has collection_id
    $effectiveCollectionId = $collectionId ?? ($riddle['collection_id'] ? (int)$riddle['collection_id'] : null);
    $collection = null;
    if ($effectiveCollectionId) {
        $cStmt = $pdo->prepare('SELECT * FROM question_collections WHERE collection_id = ?');
        $cStmt->execute([$effectiveCollectionId]);
        $collection = $cStmt->fetch() ?: null;
    }

    // Check answer match
    $synonyms = json_decode((string)$riddle['synonyms'], true) ?: [];
    $isCorrect = match_question_answer($submittedAnswer, (string)$riddle['primary_answer'], $synonyms);

    $penaltyApplied = '0.00';
    $rewardType = null;
    $rewardAmount = '0.00';
    $fallbackOffer = null;

    $pdo->beginTransaction();
    try {
        if ($isCorrect) {
            $rewardType = $riddle['reward_type'] ?? 'COINS';
            $rewardAmount = number_format((float)($riddle['reward_value'] ?? 50.00), 2, '.', '');
            $expiryDays = (int)($riddle['reward_expiry_days'] ?? 7);
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));

            if ($rewardType === 'COINS') {
                // Grant to bonus bucket
                wallet_bucket_apply_in_transaction(
                    $pdo,
                    $userId,
                    'BONUS',
                    $rewardAmount,
                    'RDL-' . $riddleId,
                    'Reward for solving riddle #' . $riddleId,
                    null,
                    $idempotencyKey . ':reward',
                    'CREDIT'
                );
            } elseif (in_array($rewardType, ['CASH', 'CASHBACK'], true)) {
                // Grant to real balance
                wallet_apply_in_transaction(
                    $pdo,
                    $userId,
                    'CREDIT',
                    $rewardAmount,
                    'RDL-' . $riddleId,
                    $rewardType . ' reward for solving riddle #' . $riddleId,
                    null,
                    $idempotencyKey . ':reward'
                );
            } elseif ($rewardType === 'COUPON') {
                // Issue coupon
                $couponCode = 'CPN-' . strtoupper(bin2hex(random_bytes(4)));
                $codeHash = hash('sha256', strtoupper($couponCode));
                $pdo->prepare('INSERT INTO coupons(code_hash, code_reference, title, discount_amount, min_cart_amount, user_id, expires_at, status, created_by) VALUES(?,?,?,?,?,?,?,"ACTIVE",1)')
                    ->execute([
                        $codeHash,
                        $couponCode,
                        'Winner Coupon: ' . mb_strimwidth($riddle['riddle_text'], 0, 40, '...'),
                        $rewardAmount,
                        '0.00',
                        $userId,
                        $expiresAt,
                    ]);
            }
            // FREE_PIN can be validated at merchant counter
        } else {
            // INCORRECT ANSWER
            // Check collection negative marking points
            $negativePoints = 0;
            if ($collection && isset($collection['negative_marking_points'])) {
                $negativePoints = max(0, (int)$collection['negative_marking_points']);
            }

            if ($negativePoints > 0) {
                // Safety guard: prevent negative wallet balances!
                // Fetch latest wallet balance with row lock
                $wLock = $pdo->prepare('SELECT bonus_balance, balance FROM wallets WHERE id = ? FOR UPDATE');
                $wLock->execute([$user['wallet_id']]);
                $wRow = $wLock->fetch();

                // Deduct from bonus first if available, else real balance, capped at available balance
                $bonusAvail = (float)($wRow['bonus_balance'] ?? 0);
                $realAvail = (float)($wRow['balance'] ?? 0);
                $totalAvail = $bonusAvail + $realAvail;

                $targetPenalty = min((float)$negativePoints, $totalAvail);

                if ($targetPenalty > 0) {
                    if ($bonusAvail >= $targetPenalty) {
                        $penaltyStr = number_format($targetPenalty, 2, '.', '');
                        wallet_bucket_apply_in_transaction(
                            $pdo,
                            $userId,
                            'BONUS',
                            $penaltyStr,
                            'PENALTY-RDL-' . $riddleId,
                            'Negative marking penalty for wrong answer on riddle #' . $riddleId,
                            null,
                            $idempotencyKey . ':penalty',
                            'DEBIT'
                        );
                        $penaltyApplied = $penaltyStr;
                    } else {
                        // Deduct whatever bonus exists, rest from real
                        if ($bonusAvail > 0) {
                            $bonusDeduct = number_format($bonusAvail, 2, '.', '');
                            wallet_bucket_apply_in_transaction(
                                $pdo,
                                $userId,
                                'BONUS',
                                $bonusDeduct,
                                'PENALTY-RDL-' . $riddleId,
                                'Penalty (Bonus) for wrong answer',
                                null,
                                $idempotencyKey . ':pen_bonus',
                                'DEBIT'
                            );
                        }
                        $rest = $targetPenalty - $bonusAvail;
                        if ($rest > 0 && $realAvail >= $rest) {
                            $realDeduct = number_format($rest, 2, '.', '');
                            wallet_apply_in_transaction(
                                $pdo,
                                $userId,
                                'DEBIT',
                                $realDeduct,
                                'PENALTY-RDL-' . $riddleId,
                                'Penalty for wrong answer on riddle #' . $riddleId,
                                null,
                                $idempotencyKey . ':pen_real'
                            );
                        }
                        $penaltyApplied = number_format($targetPenalty, 2, '.', '');
                    }
                }
            }

            // Resolve fallback offer precedence: Question level > Collection level > Neutral default
            if (!empty($riddle['fallback_offer_title'])) {
                $fallbackOffer = trim((string)$riddle['fallback_offer_title']);
            } elseif ($collection && !empty($collection['fallback_offer_title'])) {
                $fallbackOffer = trim((string)$collection['fallback_offer_title']);
            } else {
                $fallbackOffer = 'No consolation offer configured. Keep hunting for more rewards!';
            }
        }

        // Insert attempt record
        $ins = $pdo->prepare('INSERT INTO question_attempts(user_id, riddle_id, collection_id, spot_id, submitted_answer, is_correct, penalty_applied, reward_type, reward_amount, fallback_offer, idempotency_key) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([
            $userId,
            $riddleId,
            $effectiveCollectionId,
            $riddle['spot_id'],
            $submittedAnswer,
            $isCorrect ? 1 : 0,
            $penaltyApplied,
            $rewardType,
            $rewardAmount,
            $fallbackOffer,
            $idempotencyKey,
        ]);
        $attemptId = (int)$pdo->lastInsertId();

        $pdo->commit();

        return [
            'attempt_id' => $attemptId,
            'is_correct' => $isCorrect,
            'penalty_applied' => $penaltyApplied,
            'reward_type' => $rewardType,
            'reward_amount' => $rewardAmount,
            'fallback_offer' => $fallbackOffer,
            'created' => true,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

