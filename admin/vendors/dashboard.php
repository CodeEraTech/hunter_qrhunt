<?php declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_permission('vendors.view');
$pdo=db();
$stats=$pdo->query("SELECT COUNT(*) total,COALESCE(SUM(status='ACTIVE'),0) active,COALESCE(SUM(status='INACTIVE'),0) inactive,COALESCE(SUM(kyc_status='PENDING'),0) kyc_pending FROM vendors")->fetch();
$withdrawals=(int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status IN ('PENDING','UNDER_REVIEW','PROCESSING')")->fetchColumn();
$settlements=(int)$pdo->query("SELECT COUNT(*) FROM merchant_settlements WHERE status='PENDING'")->fetchColumn();
$pageTitle='Vendor dashboard';$pageSubtitle='Master overview of onboarding, KYC and settlement activity';
require APP_ROOT.'/includes/header.php';
?>
<section class="panel">
  <div class="panel-head"><h2>Vendor management &amp; onboarding</h2><div class="actions"><?php if(can('vendors.manage')):?><a class="btn" href="<?=e(app_url('/admin/vendors/onboarding.php'))?>">Add vendor</a><?php endif?><a class="btn secondary" href="<?=e(app_url('/admin/vendors/index.php'))?>">Vendor directory</a></div></div>
  <div class="grid stats">
    <div class="card"><span class="label">Total vendors</span><span class="value"><?=e($stats['total'])?></span></div>
    <div class="card"><span class="label">Active vendors</span><span class="value"><?=e($stats['active'])?></span></div>
    <div class="card"><span class="label">Pending KYC / approvals</span><span class="value"><?=e((int)$stats['kyc_pending']+(int)$stats['inactive'])?></span></div>
    <div class="card"><span class="label">Pending withdrawals</span><span class="value"><?=e($withdrawals)?></span></div>
    <div class="card"><span class="label">Pending settlements</span><span class="value"><?=e($settlements)?></span></div>
  </div>
</section>
<section class="panel"><h2>Quick actions</h2><div class="actions">
  <a class="btn secondary" href="<?=e(app_url('/admin/vendors/index.php'))?>">Vendor list &amp; profiles</a>
  <?php if(can('vendors.manage')):?><a class="btn secondary" href="<?=e(app_url('/admin/vendors/onboarding.php'))?>">Review onboarding</a><?php endif?>
  <?php if(can('withdrawals.view')):?><a class="btn secondary" href="<?=e(app_url('/admin/withdrawals/index.php'))?>">Withdrawal requests</a><?php endif?>
  <?php if(can('reports.view')):?><a class="btn secondary" href="<?=e(app_url('/admin/reports/hunts.php?type=redemptions'))?>">Redemption report</a><?php endif?>
  <span class="help">Product approvals and vendor QR approvals require dedicated workflow tables and are not enabled in this installation.</span>
</div></section>
<?php require APP_ROOT.'/includes/footer.php';
