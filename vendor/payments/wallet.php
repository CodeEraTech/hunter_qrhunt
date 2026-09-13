<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$vcode=$vendor['vendor_code'];
$store=db()->query('SELECT * FROM vendors WHERE id='.$vid)->fetch();
$pageTitle='Wallet / Available Balance';
$pageSubtitle='Your store financial position';
require APP_ROOT.'/includes/vendor_header.php';
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vcode)?> · Payments</p>
    <h1>Available Balance</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Financial Position</h2></div>
    <div class="grid stats">
        <div class="card store-metric metric-green">
            <h2>Available / Escrow Balance</h2>
            <strong><?=money($store['escrow_balance'] ?? 0)?></strong>
            <p>Withdrawable amount after settlement</p>
        </div>
        <div class="card store-metric metric-teal">
            <h2>Total Settled Payments</h2>
            <strong><?=money($store['commission_rate'] ?? 0)?>%</strong>
            <p>Commission rate on customer payments</p>
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
    <p class="help" style="margin-top:20px">When your available balance exceeds the minimum withdrawal threshold, you can request a payout from the <a href="<?=e(app_url('/vendor/payments/withdraw.php'))?>">Withdraw Money</a> page.</p>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
