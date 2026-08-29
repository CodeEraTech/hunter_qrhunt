<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';require_permission('dashboard.view');
$stats=db()->query("SELECT
 (SELECT COUNT(*) FROM users) total_users,
 (SELECT COUNT(*) FROM users WHERE status='ACTIVE') active_users,
 (SELECT COUNT(*) FROM users WHERE status='BLOCKED') blocked_users,
 (SELECT COALESCE(SUM(balance),0) FROM wallets WHERE status='ACTIVE') wallet_balance,
 (SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type IN ('CREDIT','REFUND') AND status='SUCCESS') total_credits,
 (SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type IN ('DEBIT','WITHDRAWAL') AND status='SUCCESS') total_debits,
 (SELECT COUNT(*) FROM withdrawals WHERE status IN ('PENDING','UNDER_REVIEW')) pending_withdrawals,
 (SELECT COUNT(*) FROM withdrawals WHERE status='APPROVED') approved_withdrawals,
 (SELECT COUNT(*) FROM withdrawals WHERE status='REJECTED') rejected_withdrawals,
 (SELECT COUNT(*) FROM wallet_transactions WHERE created_at>=CURDATE()) today_transactions,
 (SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE requested_at>=CURDATE()) today_withdrawals,
 (SELECT COUNT(*) FROM security_events WHERE risk_level IN ('HIGH','CRITICAL') AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)) suspicious_events,
 (SELECT COUNT(DISTINCT user_id) FROM hunt_user_activations WHERE status='ACTIVE') active_hunt_users,
 (SELECT COUNT(*) FROM hunts WHERE status='ACTIVE' AND NOW() BETWEEN start_at AND end_at) active_hunts,
 (SELECT COUNT(*) FROM hunt_scans WHERE scanned_at>=CURDATE()) today_scans,
 (SELECT COUNT(*) FROM hunt_scans WHERE result IN ('VALIDATED','COMPLETED') AND scanned_at>=CURDATE()) successful_scans,
 (SELECT COUNT(*) FROM rewards WHERE status='PENDING') pending_rewards,
 (SELECT COUNT(*) FROM rewards WHERE status='APPROVED') approved_rewards,
 (SELECT COALESCE(SUM(amount),0) FROM rewards WHERE status='APPROVED') reward_cost,
 (SELECT COUNT(*) FROM gift_redemptions WHERE redeemed_at>=CURDATE()) vendor_redemptions")->fetch();
$extra=db()->query("SELECT COALESCE(SUM(CASE WHEN bucket='REAL' AND type IN ('DEBIT','WITHDRAWAL') THEN amount ELSE 0 END),0) real_burns,COALESCE(SUM(CASE WHEN bucket='PROMO' AND type IN ('PROMO_DEBIT','PROMO_REVERSAL') THEN amount ELSE 0 END),0) promo_burns,(SELECT COALESCE(SUM(amount),0) FROM qr_promo_grants WHERE status='ACTIVE' AND (expires_at IS NULL OR expires_at>NOW())) active_promo,(SELECT COALESCE(SUM(merchant_amount),0) FROM merchant_settlements WHERE status='SETTLED') merchant_revenue,(SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type='PROMO_DEBIT' AND description LIKE 'Expired%') expired_reversals FROM wallet_transactions")->fetch();$stats=array_merge($stats,$extra);
$categorySummary=db()->query("SELECT c.id,c.name,c.character_name,c.status,COUNT(DISTINCT s.spot_id) spots,COALESCE(SUM(CASE WHEN s.is_active=1 THEN 1 ELSE 0 END),0) active_spots FROM video_categories c LEFT JOIN spot_campaigns s ON s.category_id=c.id GROUP BY c.id ORDER BY c.name")->fetchAll();
$recent=db()->query('SELECT t.*,u.name FROM wallet_transactions t JOIN users u ON u.id=t.user_id ORDER BY t.id DESC LIMIT 8')->fetchAll();
$withdrawals=db()->query('SELECT w.*,u.name FROM withdrawals w JOIN users u ON u.id=w.user_id ORDER BY w.id DESC LIMIT 6')->fetchAll();
$actions=db()->query('SELECT l.action,l.module,l.created_at,a.name FROM audit_logs l LEFT JOIN admins a ON a.id=l.admin_id ORDER BY l.id DESC LIMIT 6')->fetchAll();
$pageTitle='Overview';require APP_ROOT.'/includes/header.php';?>
<div class="grid stats">
<?php foreach([
['Total users',$stats['total_users'],$stats['active_users'].' active'],['Active Hunt users',$stats['active_hunt_users'],'Current activations'],['Active hunts',$stats['active_hunts'],'Within date window'],["Today's scans",$stats['today_scans'],$stats['successful_scans'].' successful'],['Pending rewards',$stats['pending_rewards'],'Awaiting review'],['Approved rewards',$stats['approved_rewards'],money($stats['reward_cost']).' total cost'],['Wallet liability',money($stats['wallet_balance']),'Backend controlled'],['Pending withdrawals',$stats['pending_withdrawals'],'Awaiting action'],["Today's redemptions",$stats['vendor_redemptions'],'Vendor verified'],['Suspicious activity',$stats['suspicious_events'],'High/critical · 7 days'],['Total credits',money($stats['total_credits']),'Successful ledger entries'],['Total debits',money($stats['total_debits']),'Including withdrawals'],['Real cash burns',money($stats['real_burns']),'Settled wallet usage'],['Promo burns',money($stats['promo_burns']),'Promo deductions'],['Active promo market',money($stats['active_promo']),'Unexpired grants'],['Merchant settled',money($stats['merchant_revenue']),'After platform fee'],['Expired reversals',money($stats['expired_reversals']),'Reconciled promo expiry']
] as $card):?><div class="card"><span class="label"><?=e($card[0])?></span><span class="value"><?=e($card[1])?></span><span class="trend"><?=e($card[2])?></span></div><?php endforeach?>
</div>
<section class="panel category-summary"><div class="panel-head"><h2>Category performance</h2><a href="<?=e(app_url('/admin/categories/index.php'))?>">Manage categories →</a></div><div class="category-cards"><?php foreach($categorySummary as $cat):?><div class="category-card"><div class="category-icon">✦</div><div><strong><?=e($cat['name'])?></strong><small><?=e($cat['character_name']??'Theme bucket')?></small></div><span class="category-metric"><?=e($cat['spots'])?> <small>spots</small></span><span class="badge <?=strtolower(e($cat['status']))?>"><?=e($cat['status'])?></span></div><?php endforeach?><?php if(!$categorySummary):?><div class="empty">No categories configured.</div><?php endif?></div></section>
<div class="grid two-col"><section class="panel"><div class="panel-head"><h2>Recent wallet activity</h2><a href="<?=e(app_url('/admin/wallets/index.php'))?>">View all →</a></div><div class="table-wrap"><table><thead><tr><th>Transaction</th><th>User</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead><tbody><?php foreach($recent as $row):?><tr><td><?=e($row['transaction_id'])?></td><td><?=e($row['name'])?></td><td><?=e($row['type'])?></td><td><?=money($row['amount'])?></td><td><span class="badge <?=strtolower(e($row['status']))?>"><?=e($row['status'])?></span></td></tr><?php endforeach?></tbody></table></div></section><section class="panel"><div class="panel-head"><h2>Recent withdrawals</h2><a href="<?=e(app_url('/admin/withdrawals/index.php'))?>">Review →</a></div><?php foreach($withdrawals as $row):?><div class="kv"><span><?=e($row['name'])?></span><strong><?=money($row['amount'])?> · <?=e($row['status'])?></strong></div><?php endforeach?></section></div>
<section class="panel"><div class="panel-head"><h2>Recent administrator actions</h2><?php if(can('audit.view')):?><a href="<?=e(app_url('/admin/audit/index.php'))?>">Audit log →</a><?php endif?></div><div class="table-wrap"><table><thead><tr><th>Admin</th><th>Action</th><th>Module</th><th>Date</th></tr></thead><tbody><?php foreach($actions as $row):?><tr><td><?=e($row['name']??'System')?></td><td><?=e($row['action'])?></td><td><?=e($row['module'])?></td><td><?=e($row['created_at'])?></td></tr><?php endforeach?></tbody></table></div></section>
<?php require APP_ROOT.'/includes/footer.php';
