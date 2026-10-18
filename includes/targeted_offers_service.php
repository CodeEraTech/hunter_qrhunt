<?php
declare(strict_types=1);

/**
 * Creates a single targeted offer.
 */
function create_targeted_offer(array $data, int $adminId): int
{
    $pdo = db();
    $title = trim((string)($data['offer_title'] ?? ''));
    $targetType = strtoupper(trim((string)($data['target_type'] ?? 'PRODUCT')));
    $targetId = trim((string)($data['target_id'] ?? ''));
    $targetName = trim((string)($data['target_name'] ?? ''));
    $bonusType = strtoupper(trim((string)($data['bonus_type'] ?? 'PERCENTAGE')));
    $bonusValue = (float)($data['bonus_value'] ?? 0);
    $shopId = trim((string)($data['shop_id'] ?? ''));
    $minCart = max(0.0, (float)($data['min_cart'] ?? 0));
    $maxLimit = max(0.0, (float)($data['max_limit'] ?? 0));
    $startDate = trim((string)($data['start_date'] ?? ''));
    $endDate = trim((string)($data['end_date'] ?? ''));
    $status = strtoupper(trim((string)($data['status'] ?? 'ACTIVE')));

    if ($title === '') {
        throw new InvalidArgumentException('Offer title/name is required.');
    }
    if (!in_array($targetType, ['PRODUCT', 'CATEGORY', 'VENDOR', 'USER'], true)) {
        throw new InvalidArgumentException('Target type must be PRODUCT, CATEGORY, VENDOR, or USER.');
    }
    if ($targetId === '') {
        throw new InvalidArgumentException('Specific target ID is required.');
    }
    if (!in_array($bonusType, ['PERCENTAGE', 'FIXED'], true)) {
        throw new InvalidArgumentException('Bonus deduction type must be PERCENTAGE or FIXED.');
    }
    if ($bonusValue <= 0) {
        throw new InvalidArgumentException('Bonus value must be greater than zero.');
    }
    if ($bonusType === 'PERCENTAGE' && $bonusValue > 100) {
        throw new InvalidArgumentException('Percentage bonus cannot exceed 100%.');
    }
    if ($startDate === '' || $endDate === '') {
        throw new InvalidArgumentException('Start date and end date are required.');
    }
    if ($endDate <= $startDate) {
        throw new InvalidArgumentException('End date must be after start date.');
    }

    $shopVal = ($shopId === '' || strtoupper($shopId) === 'ALL') ? null : $shopId;

    $stmt = $pdo->prepare('INSERT INTO targeted_offers(
        offer_title, target_type, target_id, target_name,
        bonus_type, bonus_value, shop_id, min_cart, max_limit,
        start_date, end_date, status, created_by, created_at
    ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())');

    $stmt->execute([
        $title,
        $targetType,
        $targetId,
        $targetName ?: null,
        $bonusType,
        $bonusValue,
        $shopVal,
        $minCart,
        $maxLimit,
        $startDate,
        $endDate,
        $status,
        $adminId,
    ]);

    $offerId = (int)$pdo->lastInsertId();

    audit_log(
        $adminId,
        'TARGETED_OFFER_CREATED',
        'targeted_offers',
        (string)$offerId,
        null,
        [
            'offer_title' => $title,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'bonus' => $bonusValue . ($bonusType === 'PERCENTAGE' ? '%' : ' INR'),
        ]
    );

    return $offerId;
}

/**
 * Bulk imports targeted offers from parsed CSV/Excel rows.
 */
function bulk_import_targeted_offers(array $rows, int $adminId): int
{
    $pdo = db();
    $pdo->beginTransaction();
    $count = 0;
    try {
        $stmt = $pdo->prepare('INSERT INTO targeted_offers(
            offer_title, target_type, target_id, target_name,
            bonus_type, bonus_value, shop_id, min_cart, max_limit,
            start_date, end_date, status, created_by, created_at
        ) VALUES(?,?,?,?,?,?,?,?,?,?,?, "ACTIVE", ?, NOW())');

        foreach ($rows as $r) {
            $type = strtoupper(trim((string)($r['target_type'] ?? '')));
            $targetId = trim((string)($r['target_id'] ?? ''));
            $bonusType = strtoupper(trim((string)($r['bonus_type'] ?? 'PERCENTAGE')));
            $bonusValue = (float)($r['bonus_value'] ?? 0);
            $shopId = trim((string)($r['shop_id'] ?? ''));
            $minCart = max(0.0, (float)($r['min_cart'] ?? 0));
            $maxLimit = max(0.0, (float)($r['max_limit'] ?? 0));
            $start = trim((string)($r['start_date'] ?? ''));
            $end = trim((string)($r['end_date'] ?? ''));

            if (!in_array($type, ['PRODUCT', 'CATEGORY', 'VENDOR', 'USER'], true)) continue;
            if ($targetId === '' || $bonusValue <= 0 || $start === '' || $end === '') continue;

            $title = 'Targeted ' . $type . ' Offer: ' . $targetId;
            $shopVal = ($shopId === '' || strtoupper($shopId) === 'ALL') ? null : $shopId;

            $stmt->execute([
                $title,
                $type,
                $targetId,
                $targetId,
                $bonusType,
                $bonusValue,
                $shopVal,
                $minCart,
                $maxLimit,
                $start,
                $end,
                $adminId,
            ]);
            $count++;
        }

        audit_log($adminId, 'TARGETED_OFFERS_BULK_IMPORTED', 'targeted_offers', null, null, ['count' => $count]);
        $pdo->commit();
        return $count;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Priority engine: User-specific offer > Product-specific rule > Category rule > Vendor default rule.
 * Checks active time window, shop restriction, and minimum cart limit.
 * Computes bonus discount amount, capped at max_limit if configured.
 */
function find_matching_targeted_offer(
    int $userId,
    ?string $productId = null,
    ?string $categoryId = null,
    ?string $vendorId = null,
    float $cartAmount = 0.0
): ?array {
    $pdo = db();
    $now = date('Y-m-d H:i:s');

    // Priority checks sequence
    $checks = [
        ['type' => 'USER', 'id' => (string)$userId],
        ['type' => 'PRODUCT', 'id' => (string)($productId ?? '')],
        ['type' => 'CATEGORY', 'id' => (string)($categoryId ?? '')],
        ['type' => 'VENDOR', 'id' => (string)($vendorId ?? '')],
    ];

    foreach ($checks as $c) {
        if ($c['id'] === '') {
            continue;
        }

        $sql = 'SELECT * FROM targeted_offers 
                WHERE target_type = ? AND target_id = ? AND status = "ACTIVE"
                  AND ? BETWEEN start_date AND end_date
                  AND min_cart <= ?
                  AND (shop_id IS NULL OR shop_id = "" OR shop_id = "ALL" OR shop_id = ?)
                ORDER BY id DESC LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$c['type'], $c['id'], $now, $cartAmount, (string)($vendorId ?? '')]);
        if ($offer = $stmt->fetch()) {
            // Calculate bonus discount
            $bonusAmount = 0.0;
            if ($offer['bonus_type'] === 'PERCENTAGE') {
                $bonusAmount = round($cartAmount * ((float)$offer['bonus_value'] / 100.0), 2);
            } else {
                $bonusAmount = (float)$offer['bonus_value'];
            }

            // Cap at max_limit if positive
            $maxLimit = (float)$offer['max_limit'];
            if ($maxLimit > 0 && $bonusAmount > $maxLimit) {
                $bonusAmount = $maxLimit;
            }

            // Also cannot exceed total cart amount
            if ($bonusAmount > $cartAmount) {
                $bonusAmount = $cartAmount;
            }

            return [
                'offer' => $offer,
                'matched_priority' => $c['type'],
                'bonus_amount' => number_format($bonusAmount, 2, '.', ''),
            ];
        }
    }

    return null;
}

