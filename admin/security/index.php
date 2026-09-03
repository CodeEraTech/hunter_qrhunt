<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_permission('security.view');
$pdo=db();
$count=static function(PDO $pdo,string $sql): int { try{return (int)$pdo->query($sql)->fetchColumn();}catch(Throwable){return 0;} };
$cards=[
 ['TOTAL STAFF',$count($pdo,"SELECT COUNT(*) FROM admins"),'staff'],
 ['ACTIVE STAFF',$count($pdo,"SELECT COUNT(*) FROM admins WHERE status='ACTIVE'"),'active'],
 ['SUSPENDED',$count($pdo,"SELECT COUNT(*) FROM admins WHERE status IN ('SUSPENDED','BLOCKED')"),'warning'],
 ['PENDING KYC',$count($pdo,"SELECT COUNT(*) FROM staff_profiles WHERE kyc_status='PENDING'"),'warning'],
 ['FAILED LOGINS',$count($pdo,"SELECT COUNT(*) FROM login_attempts WHERE successful=0 AND attempted_at>=DATE_SUB(NOW(),INTERVAL 24 HOUR)"),'danger'],
 ['LOCKED ACCOUNTS',$count($pdo,"SELECT COUNT(*) FROM admins WHERE status='LOCKED'"),'danger'],
 ['NEW DEVICES',$count($pdo,"SELECT COUNT(*) FROM user_devices WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)"),'info'],
 ['HIGH-RISK ACTIONS',$count($pdo,"SELECT COUNT(*) FROM security_events WHERE risk_level IN ('HIGH','CRITICAL') AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)"),'danger']
];
$events=$pdo->query("SELECT s.*,u.name FROM security_events s LEFT JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT 12")->fetchAll();
$pageTitle='Security & Governance';$pageSubtitle='Staff access, authentication and security monitoring';require APP_ROOT.'/includes/header.php';
?><section class="panel"><div class="panel-head"><div><h2>Security dashboard</h2><p class="help">Live governance metrics calculated from staff, login, device and security event records.</p></div><a class="btn secondary" href="<?=e(app_url('/admin/admins/staff.php'))?>">Staff management</a></div><div class="summary-grid governance-cards"><?php foreach($cards as [$label,$value,$tone]):?><article class="stat-card <?=e($tone)?>"><span><?=e($label)?></span><strong><?=e((string)$value)?></strong></article><?php endforeach?></div></section>
<section class="panel"><div class="panel-head"><h2>Recent security alerts</h2><a href="<?=e(app_url('/admin/fraud/index.php'))?>">View all events →</a></div><div class="table-wrap"><table><thead><tr><th>Severity</th><th>Date / time</th><th>Staff / user</th><th>Event</th><th>Target / details</th><th>Status</th></tr></thead><tbody><?php foreach($events as $event):?><tr><td><span class="risk <?=strtolower(e($event['risk_level']))?>"><?=e($event['risk_level'])?></span></td><td><?=e($event['created_at'])?></td><td><?=e($event['name']?:'System/Admin')?></td><td><?=e($event['event_type'])?></td><td><?=e($event['description'])?></td><td><span class="badge active">RECORDED</span></td></tr><?php endforeach?></tbody></table><?php if(!$events):?><div class="empty">No security alerts recorded.</div><?php endif?></div></section><?php require APP_ROOT.'/includes/footer.php';
