<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('withdrawals.view');

$id = filter_var(input('id'), FILTER_VALIDATE_INT);
$source = (string) input('source');

if (!$id || !in_array($source, ['CUSTOMER','VENDOR'], true)) {
    http_response_code(400);
    exit('Invalid parameters');
}

$pdo = db();

if ($source === 'CUSTOMER') {
    $stmt = $pdo->prepare("SELECT w.*, u.name, u.email, u.mobile, u.is_verified, u.status user_status, u.risk_level user_risk,
        wa.balance, wa.bonus_balance, wa.promo_balance,
        d.device_name, d.device_id, d.ip_address device_ip, d.last_active_at, d.platform, d.os_version,
        wt.transaction_id, wt.type, wt.amount, wt.bucket, wt.status tx_status, wt.created_at tx_created_at,
        p.reference payout_reference,
        wa2.balance as current_balance
        FROM withdrawals w
        JOIN users u ON u.id = w.user_id
        JOIN wallets wa ON wa.id = w.wallet_id
        JOIN wallets wa2 ON wa2.user_id = u.id
        LEFT JOIN user_devices d ON d.user_id = u.id AND d.status='ACTIVE'
        LEFT JOIN wallet_transactions wt ON wt.user_id = u.id
        LEFT JOIN finance_payout_records p ON p.kind='CUSTOMER' AND p.request_id = w.id
        WHERE w.id = ?
        ORDER BY wt.id DESC LIMIT 10");
    $stmt->execute([$id]);
    $rows = $stmt->fetchAll();
    if (!$rows) { http_response_code(404); exit('Not found'); }

    $wd = $rows[0];
    $transactions = [];
    foreach ($rows as $r) {
        if ($r['transaction_id']) {
            $transactions[] = [
                'type' => $r['type'],
                'amount' => $r['amount'],
                'bucket' => $r['bucket'],
                'status' => $r['tx_status'],
                'created_at' => $r['tx_created_at'],
            ];
        }
    }

    // Get security events
    $evStmt = $pdo->prepare("SELECT * FROM security_events WHERE user_id = ? ORDER BY id DESC LIMIT 8");
    $evStmt->execute([$wd['user_id']]);
    $securityEvents = $evStmt->fetchAll();

    // Get withdrawal attempts
    $attStmt = $pdo->prepare("SELECT * FROM withdrawal_attempts WHERE withdrawal_id = ? ORDER BY id DESC LIMIT 10");
    $attStmt->execute([$id]);
    $attempts = $attStmt->fetchAll();

    // Calculate post-payout balance
    $postPayoutBalance = max(0, (float)$wd['balance'] - (float)$wd['amount']);
    $deduction = min((float)$wd['amount'], (float)$wd['balance']);

    // Bank/UPI details
    $bankName = '';
    $ifsc = '';
    $accountMasked = $wd['payment_account_masked'] ?? '—';
    $upiId = '';

    // Parse destination for UPI/bank
    if (str_contains(strtolower($wd['payment_method']), 'upi')) {
        $upiId = $wd['payment_account_masked'] ?? '—';
    } else {
        // Try to get from users or vendors table
        $bankStmt = $pdo->prepare("SELECT bank_account_number, bank_ifsc, bank_name, settlement_upi FROM users WHERE id = ?");
        $bankStmt->execute([$wd['user_id']]);
        $bankInfo = $bankStmt->fetch();
        if ($bankInfo) {
            $accountMasked = $bankInfo['bank_account_number'] ? payment_account_mask($bankInfo['bank_account_number']) : '—';
            $ifsc = $bankInfo['bank_ifsc'] ?? '—';
            $bankName = $bankInfo['bank_name'] ?? '—';
            $upiId = $bankInfo['settlement_upi'] ?? '—';
        }
    }
} else { // VENDOR
    $stmt = $pdo->prepare("SELECT r.*, v.vendor_code, v.shop_name, v.name, v.email, v.mobile, v.bank_account_number, v.bank_ifsc, v.bank_name, v.settlement_upi, v.escrow_balance, v.bank_verification_status
        FROM vendor_payout_requests r
        JOIN vendors v ON v.id = r.vendor_id
        WHERE r.id = ?");
    $stmt->execute([$id]);
    $wd = $stmt->fetch();
    if (!$wd) { http_response_code(404); exit('Not found'); }
    $transactions = [];
    $securityEvents = [];
    $attempts = [];
    $postPayoutBalance = max(0, (float)$wd['escrow_balance'] - (float)$wd['amount']);
    $deduction = min((float)$wd['amount'], (float)$wd['escrow_balance']);
    $bankName = $wd['bank_name'] ?? '—';
    $ifsc = $wd['bank_ifsc'] ?? '—';
    $accountMasked = $wd['bank_account_number'] ? payment_account_mask($wd['bank_account_number']) : '—';
    $upiId = $wd['settlement_upi'] ?? '—';
    $payoutReference = null;
    if (finance_ready()) {
        $ref = $pdo->prepare("SELECT reference FROM finance_payout_records WHERE kind='VENDOR' AND request_id = ?");
        $ref->execute([$id]);
        $payoutReference = $ref->fetchColumn();
    }
}
?>
<div class="panel">
    <div class="panel-head">
        <h2><?=e($source)?> Withdrawal Detail</h2>
        <span class="badge <?=strtolower(e($wd['status']))?>"><?=e($wd['status'])?></span>
    </div>
    <div class="kv"><span>Request ID</span><strong><?=e($source==='CUSTOMER'?$wd['withdrawal_id']:'VWD-'.$wd['id'])?></strong></div>
    <div class="kv"><span>UTR / Reference</span><strong><?=e($wd['payout_reference'] ?? $payoutReference ?? 'Not recorded')?></strong></div>
    <div class="kv"><span>Created</span><strong><?=e($wd['created_at'] ?? $wd['requested_at'])?></strong></div>
    <div class="kv"><span>Status Updated</span><strong><?=e($wd['processed_at'] ?? $wd['paid_at'] ?? $wd['reviewed_at'] ?? '—')?></strong></div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Customer / Vendor Profile</h2></div>
    <div class="kv"><span>Name</span><strong><?=e($wd['name'] ?? $wd['shop_name'])?></strong></div>
    <div class="kv"><span>ID</span><strong><?=e($source==='CUSTOMER'?'USR_'.$wd['user_id']:$wd['vendor_code'])?></strong></div>
    <div class="kv"><span>Mobile</span><strong><?=e($wd['mobile'])?></strong></div>
    <div class="kv"><span>Email</span><strong><?=e($wd['email'] ?? '—')?></strong></div>
    <?php if ($source==='CUSTOMER'): ?>
    <div class="kv"><span>KYC Status</span><strong><?=$wd['is_verified']?'<span class="badge success">Verified</span>':'<span class="badge warning">Pending</span>'?></strong></div>
    <div class="kv"><span>Account Status</span><strong><?=e($wd['user_status'])?></strong></div>
    <div class="kv"><span>User Risk</span><strong><?=e($wd['user_risk'])?></strong></div>
    <div class="kv"><span>Active Device</span><strong><?=e($wd['device_name'] ?? '—')?> · <?=e($wd['device_ip'] ?? '—')?></strong></div>
    <div class="kv"><span>Device Platform</span><strong><?=e($wd['platform'] ?? '—')?> · <?=e($wd['os_version'] ?? '—')?></strong></div>
    <div class="kv"><span>Last Active</span><strong><?=e($wd['last_active_at'] ?? '—')?></strong></div>
    <?php else: ?>
    <div class="kv"><span>Bank Verification</span><strong><?=e($wd['bank_verification_status'])?></strong></div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head"><h2>Wallet Balances</h2></div>
    <?php if ($source==='CUSTOMER'): ?>
    <div class="kv"><span>Real Cash Balance (pre-withdrawal)</span><strong><?=money($wd['balance'])?></strong></div>
    <div class="kv"><span>Bonus Balance (non-withdrawable)</span><strong><?=money($wd['bonus_balance'])?></strong></div>
    <div class="kv"><span>Promo Balance (non-withdrawable)</span><strong><?=money($wd['promo_balance'])?></strong></div>
    <div class="kv"><span>Withdrawal Amount</span><strong><?=money($wd['amount'])?></strong></div>
    <div class="kv"><span>Deduction from Real Cash</span><strong><?=money($deduction)?></strong></div>
    <div class="kv"><span>Post-Payout Real Cash Balance</span><strong style="color:#256845"><?=money($postPayoutBalance)?></strong></div>
    <?php else: ?>
    <div class="kv"><span>Escrow Balance (pre-withdrawal)</span><strong><?=money($wd['escrow_balance'])?></strong></div>
    <div class="kv"><span>Withdrawal Amount</span><strong><?=money($wd['amount'])?></strong></div>
    <div class="kv"><span>Deduction from Escrow</span><strong><?=money($deduction)?></strong></div>
    <div class="kv"><span>Post-Payout Escrow Balance</span><strong style="color:#256845"><?=money($postPayoutBalance)?></strong></div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head"><h2>Verified Payout Destination</h2></div>
    <div class="kv"><span>Payout Method</span><strong><?=e($wd['payment_method'] ?? 'BANK')?></strong></div>
    <?php if ($upiId !== '—' && $upiId !== ''): ?>
    <div class="kv"><span>UPI ID / VPA</span><strong><?=e($upiId)?></strong></div>
    <?php endif; ?>
    <div class="kv"><span>Bank Name</span><strong><?=e($bankName)?></strong></div>
    <div class="kv"><span>Account Number</span><strong><?=e($accountMasked)?></strong></div>
    <div class="kv"><span>IFSC Code</span><strong><?=e($ifsc)?></strong></div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Mini-Passbook (Recent Transactions)</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Date & Time</th><th>Type</th><th>Bucket</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?=e($t['created_at'])?></td>
            <td><?=e($t['type'])?></td>
            <td><?=e($t['bucket'])?></td>
            <td><?=money($t['amount'])?></td>
            <td><span class="badge <?=strtolower(e($t['status']))?>"><?=e($t['status'])?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?><tr><td colspan="5" class="empty">No recent transactions.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<?php if ($securityEvents): ?>
<div class="panel">
    <div class="panel-head"><h2>Security Indicators</h2></div>
    <?php foreach ($securityEvents as $ev): ?>
    <div class="kv"><span class="risk <?=strtolower(e($ev['risk_level']))?>"><?=e($ev['risk_level'])?></span><strong><?=e($ev['event_type'])?> · <?=e($ev['created_at'])?></strong></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($attempts): ?>
<div class="panel">
    <div class="panel-head"><h2>Processing History</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Date</th><th>Outcome</th><th>IP</th><th>Reason</th></tr></thead>
        <tbody>
        <?php foreach ($attempts as $a): ?>
        <tr><td><?=e($a['created_at'])?></td><td><?=e($a['outcome'])?></td><td><?=e($a['ip_address']??'—')?></td><td><?=e($a['reason']??'—')?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>