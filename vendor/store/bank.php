<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$store=db()->prepare('SELECT * FROM vendors WHERE id=?')->execute([$vid]);
$store=db()->query('SELECT * FROM vendors WHERE id='.$vid)->fetch();
$pageTitle='Bank & Settlement';
$pageSubtitle='Bank account, payout and settlement details';
require APP_ROOT.'/includes/vendor_header.php';
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($store['vendor_code'])?> · Settlement</p>
    <h1>Bank &amp; Settlement</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Bank Account on File</h2></div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>
    <tr><td>Account Holder</td><td><strong><?=e($store['bank_account_holder'] ?? '—')?></strong></td></tr>
    <tr><td>Account Number</td><td><?= $store['bank_account_number'] ? e(payment_account_mask($store['bank_account_number'])) : '—' ?></td></tr>
    <tr><td>IFSC Code</td><td><strong><?=e($store['bank_ifsc'] ?? '—')?></strong></td></tr>
    <tr><td>Bank Branch</td><td><?=e($store['bank_branch'] ?? '—')?></td></tr>
    <tr><td>UPI ID</td><td><strong><?=e($store['settlement_upi'] ?? '—')?></strong></td></tr>
    <tr><td>Settlement Cycle</td><td><strong><?=e($store['settlement_cycle'] ?? 'DAILY')?></strong></td></tr>
    <tr><td>Bank Verification</td><td><span class="store-status status-<?=e(strtolower($store['bank_verification_status'] ?? 'pending'))?>"><?=e($store['bank_verification_status'] ?? 'Not verified')?></span></td></tr>
    </tbody></table></div>
    <p class="help" style="margin-top:16px">Bank details are verified during onboarding. To update any field, contact administrator support.</p>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Settlement Summary</h2></div>
    <div class="grid stats">
        <div class="card">
            <span class="label">Escrow Balance</span>
            <span class="value"><?=money($store['escrow_balance'] ?? 0)?></span>
        </div>
        <div class="card">
            <span class="label">Commission Rate</span>
            <span class="value"><?=e(number_format($store['commission_rate'] ?? 0, 2))?>%</span>
        </div>
        <div class="card">
            <span class="label">Settlement Cycle</span>
            <span class="value"><?=e($store['settlement_cycle'] ?? 'DAILY')?></span>
        </div>
        <div class="card">
            <span class="label">Bank Verified</span>
            <span class="value"><?= $store['bank_verification_status']==='VERIFIED' ? '✅ Yes' : '❌ No' ?></span>
        </div>
    </div>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
