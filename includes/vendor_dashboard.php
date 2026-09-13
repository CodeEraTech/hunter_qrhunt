<?php
declare(strict_types=1);

/** vendorId must come from vendor_user(), never URL or form input. */
function vendor_dashboard_data(PDO $pdo, int $vendorId, array $pages = []): array
{
    if ($vendorId < 1) throw new InvalidArgumentException('An authenticated store is required.');
    $query = static function (string $sql) use ($pdo, $vendorId): PDOStatement {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt;
    };
    $record = $query('SELECT * FROM vendors WHERE id=?')->fetch();
    if (!$record) throw new RuntimeException('Store not found.');
    $store = ['shop_name'=>$record['shop_name'], 'vendor_code'=>$record['vendor_code'],
        'bank_masked'=>empty($record['bank_account_number']) ? null : payment_account_mask($record['bank_account_number']),
        'available_balance'=>isset($record['escrow_balance']) ? (float)$record['escrow_balance'] : 0.0,
        'commission_rate'=>isset($record['commission_rate']) ? (float)$record['commission_rate'] : 0.0,
        'kyc_status'=>$record['kyc_status'] ?? null,
        'status'=>$record['status'] ?? null,
        'business_category'=>$record['business_category'] ?? null,
    ];
    $definitions = [
        'withdrawals' => [
            'summary' => "SELECT COUNT(*) total, COALESCE(SUM(amount),0) amount,
                COALESCE(SUM(CASE WHEN vpr.status IN ('PENDING','UNDER_REVIEW') THEN amount ELSE 0 END),0) pending,
                COALESCE(SUM(CASE WHEN vpr.status IN ('PENDING','UNDER_REVIEW') THEN 1 ELSE 0 END),0) pending_count,
                COALESCE(SUM(CASE WHEN vpr.status IN ('APPROVED','PROCESSING','PAID') THEN amount ELSE 0 END),0) approved
                FROM vendor_payout_requests vpr WHERE vpr.vendor_id=?",
            'rows' => 'SELECT vpr.id,vpr.amount,vpr.status,vpr.requested_at,vpr.paid_at FROM vendor_payout_requests vpr WHERE vpr.vendor_id=? ORDER BY vpr.requested_at DESC,vpr.id DESC',
        ],
        'products' => [
            'summary' => "SELECT COUNT(*) total,
                COALESCE(SUM(CASE WHEN vp.status='APPROVED' THEN 1 ELSE 0 END),0) approved,
                COALESCE(SUM(CASE WHEN vp.status IN ('PENDING_REVIEW','RESUBMITTED') THEN 1 ELSE 0 END),0) pending
                FROM vendor_products vp WHERE vp.vendor_id=?",
            'rows' => 'SELECT vp.product_code,vp.title,vp.price,vp.metadata,vp.status,vp.admin_note,vp.submitted_at FROM vendor_products vp WHERE vp.vendor_id=? ORDER BY vp.submitted_at DESC,vp.id DESC',
        ],
        'payments' => [
            'summary' => "SELECT COUNT(*) total, COALESCE(SUM(bill_amount),0) amount,
                COALESCE(SUM(CASE WHEN c.status='SUCCESS' THEN bill_amount ELSE 0 END),0) approved,
                COALESCE(SUM(CASE WHEN c.status='PENDING' THEN bill_amount ELSE 0 END),0) pending
                FROM wallet_checkout_transactions c WHERE c.merchant_id=?",
            'rows' => 'SELECT c.checkout_id,c.bill_amount,c.status,c.created_at,u.name customer_name,
                s.settlement_id,s.status settlement_status,s.platform_fee,s.merchant_amount,s.settled_at
                FROM wallet_checkout_transactions c LEFT JOIN users u ON u.id=c.user_id
                LEFT JOIN merchant_settlements s ON s.checkout_id=c.checkout_id AND s.merchant_id=c.merchant_id
                WHERE c.merchant_id=? ORDER BY c.created_at DESC,c.id DESC',
        ],
    ];
    $data = ['store'=>$store];
    foreach ($definitions as $section=>$definition) {
        try {
            $summary = $query($definition['summary'])->fetch();
            $lastPage = max(1, (int) ceil((int) $summary['total'] / 20));
            $page = max(1, min($lastPage, (int) ($pages[$section] ?? 1)));
            $rows = $query($definition['rows'].' LIMIT 20 OFFSET '.(($page-1)*20))->fetchAll();
            $data[$section] = compact('summary','rows','page','lastPage');
        } catch (PDOException $e) {
            // Older installations may lack these modules. Never display missing records as zero.
            if ($e->getCode() !== '42S02') throw $e;
            error_log('Vendor dashboard module unavailable: '.$section);
            $data[$section] = null;
        }
    }

    // Recent orders (latest 5 wallet checkouts for this vendor)
    $data['recent_orders'] = [];
    try {
        $stmt = $query("SELECT checkout_id,bill_amount,c.status,c.created_at,u.name customer_name
            FROM wallet_checkout_transactions c LEFT JOIN users u ON u.id=c.user_id
            WHERE c.merchant_id=? ORDER BY c.created_at DESC,c.id DESC LIMIT 5");
        $data['recent_orders'] = $stmt->fetchAll();
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S02') throw $e;
    }

    // Recent product approval activity (latest 5)
    $data['recent_products'] = [];
    try {
        $stmt = $query("SELECT product_code,title,price,vp.status,admin_note,submitted_at
            FROM vendor_products vp WHERE vp.vendor_id=? ORDER BY vp.submitted_at DESC,vp.id DESC LIMIT 5");
        $data['recent_products'] = $stmt->fetchAll();
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S02') throw $e;
    }

    return $data;
}

function vendor_dashboard_status(string $status): string
{
    return match ($status) {
        'PENDING_REVIEW'=>'Pending review', 'RESUBMITTED'=>'Resubmitted for review',
        'SUCCESS'=>'Successful', default=>ucfirst(strtolower(str_replace('_',' ',$status))),
    };
}
