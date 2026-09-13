<?php
 declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_permission('wallet.master.view');
require_once APP_ROOT.'/includes/admin_wallet.php';
$ready=finance_ready();$adminId=(int)admin()['id'];
if (is_post()) {
    verify_csrf();
    try {
        if (!$ready) throw new RuntimeException('The master wallet is not configured yet.');
        $action=(string)input('action');
        if ($action==='pin') {
            if (!can('wallet.master.distribute') && !can('wallet.master.fund')) { http_response_code(403);exit('Forbidden'); }
            finance_set_pin($adminId,(string)input('password'),(string)input('pin'),(string)input('otp'));
            flash('success','Wallet PIN saved. Use a fresh authenticator code for the next operation.');
        } elseif ($action==='prepare') {
            $payload=finance_payload($_POST);finance_authorize($payload);finance_verify_pin($adminId,(string)input('pin'));
            $_SESSION['finance_pending']=['payload'=>$payload,'admin_id'=>$adminId,'key'=>idempotency_key('master'),'expires'=>time()+300];
        } elseif ($action==='confirm') {
            $pending=$_SESSION['finance_pending']??null;
            if (!$pending || $pending['admin_id']!==$adminId || $pending['expires']<time()) throw new RuntimeException('The confirmation expired. Enter your PIN again.');
            $txn=finance_transfer($adminId,$pending['payload'],$pending['key'],(string)input('otp'));
            unset($_SESSION['finance_pending']);flash('success','Recorded successfully: '.$txn);
        } elseif ($action==='cancel') unset($_SESSION['finance_pending']);
        else throw new InvalidArgumentException('Unknown action.');
    } catch(Throwable $e) { flash('error',$e instanceof PDOException?'The operation could not be recorded. Check the reference and available balances.':$e->getMessage()); }
    redirect('/admin/wallets/admin.php');
}
$pageTitle='Admin Wallet';$pageSubtitle='Master balance, fund distribution and permanent audit history';
$balance=null;$pools=[];$history=[];$total=0;$page=max(1,min(100000,page_int()));
if ($ready) {
    $balance=db()->query('SELECT balance FROM finance_master_account WHERE id=1')->fetchColumn();
    $pools=db()->query('SELECT (SELECT COALESCE(SUM(escrow_balance),0) FROM vendors) vendor,(SELECT COALESCE(SUM(balance),0) FROM wallets) real_money,(SELECT COALESCE(SUM(bonus_balance),0) FROM wallets) bonus')->fetch();
    $total=(int)db()->query('SELECT COUNT(*) FROM finance_master_entries')->fetchColumn();
    $history=db()->query('SELECT e.*,a.name admin_name FROM finance_master_entries e JOIN admins a ON a.id=e.admin_id ORDER BY e.id DESC LIMIT 20 OFFSET '.(($page-1)*20))->fetchAll();
}
require APP_ROOT.'/includes/header.php';
?>
<section class="finance-master"><p>ADMIN MASTER WALLET BALANCE</p><strong><?=$balance!==null?money($balance):'Not configured'?></strong><small>Recorded funding available for distribution</small></section>
<?php if (!$ready): ?><section class="panel"><h2>Master wallet setup required</h2><p>The financial ledger has not been initialized. Contact your system administrator to complete setup.</p></section><?php else: ?>
<section class="panel"><div class="panel-head"><h2>Fund Distribution &amp; Security · PIN → OTP</h2></div>
<?php $pending=$_SESSION['finance_pending']??null;if ($pending && $pending['expires']>=time() && $pending['admin_id']===$adminId): $p=$pending['payload']; ?>
<div class="finance-review"><h3>Confirm this exact operation</h3><p><?=e($p['operation'])?> · <?=e($p['target'])?> <?= $p['recipient_id']?'#'.e($p['recipient_id']):''?> · <strong><?=money($p['amount'])?></strong></p><p><?=e($p['reason'])?></p><p>Reference: <?=e($p['reference']?:'—')?></p></div>
<form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="confirm"><div class="field"><label for="confirm-otp">Step 2 · Authenticator OTP</label><input id="confirm-otp" name="otp" pattern="\d{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required></div><div class="field"><p class="help">PIN verified. Confirmation expires after five minutes. An OTP cannot be reused.</p><button class="btn">Confirm transfer</button></div></form>
<form method="post"><?=csrf_field()?><input type="hidden" name="action" value="cancel"><button class="btn secondary">Cancel</button></form>
<?php elseif(can('wallet.master.distribute') || can('wallet.master.fund')): ?>
<form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="prepare">
<div class="field"><label for="operation">Operation</label><select id="operation" name="operation"><?php if(can('wallet.master.distribute')):?><option value="DISTRIBUTE">Credit recipient from master</option><option value="RECALL">Debit recipient back to master</option><?php endif;if(can('wallet.master.fund')):?><option value="FUND">Record received master funding</option><?php endif?></select></div>
<div class="field"><label for="target">Target wallet</label><select id="target" name="target"><option value="VENDOR">Vendor Wallet</option><option value="REAL">User Real Money</option><option value="BONUS">Qoin Bonus (₹ bonus balance)</option></select></div>
<div class="field"><label for="recipient">Vendor / User numeric ID</label><input id="recipient" name="recipient_id" type="number" min="1"><small>Not required for received master funding.</small></div>
<div class="field"><label for="amount">Amount (₹)</label><input id="amount" name="amount" inputmode="decimal" pattern="\d{1,12}(\.\d{1,2})?" required></div>
<div class="field"><label for="reference">Bank / transaction reference</label><input id="reference" name="reference" maxlength="120"><small>Required when recording received funding. This records a receipt; it does not charge a bank account.</small></div>
<div class="field"><label for="reason">Audit reason</label><input id="reason" name="reason" maxlength="500" required></div>
<div class="field"><label for="pin">Step 1 · Six-digit admin PIN</label><input id="pin" name="pin" type="password" pattern="\d{6}" maxlength="6" inputmode="numeric" autocomplete="off" required></div>
<div class="field"><p class="help">Review the details, then confirm with your authenticator OTP. Both balances and the audit record are saved together.</p><button class="btn">Verify PIN &amp; review</button></div>
</form><?php else: ?><p>You have read-only access to this wallet.</p><?php endif ?>
</section>
<div class="finance-pools"><article class="finance-pool pool-vendor"><h2>Vendor Wallets Pool</h2><strong><?=money($pools['vendor'])?></strong></article><article class="finance-pool pool-real"><h2>User Real Money Pool</h2><strong><?=money($pools['real_money'])?></strong></article><article class="finance-pool pool-bonus"><h2>Qoin Bonus Pool</h2><strong><?=money($pools['bonus'])?></strong><small>Existing bonus wallet value in rupees</small></article></div>
<section class="panel"><div class="panel-head"><h2>Master Transaction &amp; Distribution History</h2></div><div class="table-wrap"><table><thead><tr><th>Transaction / Date</th><th>Target</th><th>Operation / Amount</th><th>Master balance</th><th>Recipient balance</th><th>Security / Audit</th></tr></thead><tbody>
<?php foreach($history as $r): ?><tr><td><a href="<?=e(app_url('/admin/wallets/detail.php?source=MASTER&id='.$r['id']))?>"><?=e($r['transaction_id'])?></a><br><small><?=e($r['created_at'])?></small></td><td><?=e($r['target'])?> <?=e($r['recipient_id']?'#'.$r['recipient_id']:'')?></td><td><?=e($r['operation'])?><br><?=money($r['amount'])?></td><td><?=money($r['previous_balance'])?> → <?=money($r['new_balance'])?></td><td><?=$r['recipient_before']!==null?money($r['recipient_before']).' → '.money($r['recipient_after']):'—'?></td><td>PIN + OTP verified<br><?=e($r['reason'])?><br><small><?=e($r['admin_name'])?> · <?=e($r['ip_address'])?> · <?=e($r['reference'])?></small></td></tr><?php endforeach;if(!$history):?><tr><td colspan="6" class="empty">No master wallet entries yet.</td></tr><?php endif ?>
</tbody></table></div><div class="pagination"><?php if($page>1):?><a href="?page=<?=$page-1?>">Previous</a><?php endif;if($page*20<$total):?><a href="?page=<?=$page+1?>">Next</a><?php endif ?></div></section>
<?php if(can('wallet.master.distribute') || can('wallet.master.fund')): ?><details class="panel"><summary>Set or change wallet PIN</summary><p>Requires your current password and an enabled authenticator MFA account.</p><form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="pin"><div class="field"><label>Current password</label><input name="password" type="password" autocomplete="current-password" required></div><div class="field"><label>New six-digit PIN</label><input name="pin" type="password" pattern="\d{6}" maxlength="6" autocomplete="new-password" required></div><div class="field"><label>Authenticator OTP</label><input name="otp" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" required></div><div class="field"><button class="btn">Save PIN</button></div></form></details><?php endif ?>
<?php endif ?>
<section class="panel"><h2>Wallet tools</h2><div class="actions"><?php if(can('wallet.credit')||can('wallet.debit')):?><a class="btn secondary" href="<?=e(app_url('/admin/wallets/adjust.php'))?>">Customer Wallet Console</a><?php endif;if(can('wallet.bonus')):?><a class="btn secondary" href="<?=e(app_url('/admin/wallet/bonus.php'))?>">Bonus &amp; promotions</a><?php endif;if(can('vendor_settlements.view')):?><a class="btn secondary" href="<?=e(app_url('/admin/withdrawals/index.php?tab=money'))?>">Vendor money requests</a><?php endif;if(can('audit.view')):?><a class="btn secondary" href="<?=e(app_url('/admin/audit/index.php'))?>">Audit records</a><?php endif ?></div></section>
<?php require APP_ROOT.'/includes/footer.php'; ?>
