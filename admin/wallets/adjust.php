<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
if(!can('wallet.credit')&&!can('wallet.debit')){http_response_code(403);exit('Forbidden');}
$selected=(int)input('user_id',0);
if(is_post()){
    verify_csrf();$type=strtoupper((string)input('type'));require_permission($type==='DEBIT'?'wallet.debit':'wallet.credit');
    try{$result=wallet_adjust((int)input('user_id'),$type,trim((string)input('amount')),trim((string)input('reference')),trim((string)input('reason')),(int)admin()['id'],(string)input('idempotency_key'));flash('success','Adjustment completed: '.$result['transaction_id']);redirect('/admin/wallets/index.php');}
    catch(Throwable $e){$error=$e instanceof PDOException?'Could not complete the adjustment.':$e->getMessage();}
}
$users=db()->query("SELECT u.id,u.name,u.mobile,w.balance FROM users u JOIN wallets w ON w.user_id=u.id WHERE u.status='ACTIVE' AND w.status='ACTIVE' ORDER BY u.name")->fetchAll();
$adjustmentKey=idempotency_key('wallet-adjust');$pageTitle='Adjust wallet';$pageSubtitle='Creates an atomic ledger transaction—balance is never directly edited';require APP_ROOT.'/includes/header.php';?>
<?php if(isset($error)):?><div class="alert error"><?=e($error)?></div><?php endif?>
<section class="panel"><form method="post" class="form-grid" data-confirm="Confirm this wallet adjustment? This action will be audited."><?=csrf_field()?><input type="hidden" name="idempotency_key" value="<?=e($adjustmentKey)?>"><div class="field full"><label>User wallet</label><select name="user_id" required><option value="">Select a user</option><?php foreach($users as $u):?><option value="<?=e($u['id'])?>" <?=$selected===$u['id']?'selected':''?>>#<?=e($u['id'])?> · <?=e($u['name'])?> · <?=money($u['balance'])?></option><?php endforeach?></select></div><div class="field"><label>Transaction type</label><select name="type" required><?php if(can('wallet.credit')):?><option>CREDIT</option><?php endif?><?php if(can('wallet.debit')):?><option>DEBIT</option><?php endif?></select></div><div class="field"><label>Amount (INR)</label><input name="amount" inputmode="decimal" pattern="\d+(\.\d{1,2})?" required placeholder="1000.00"></div><div class="field"><label>Reference</label><input name="reference" maxlength="120" required placeholder="Ticket or bank reference"></div><div class="field full"><label>Reason</label><textarea name="reason" maxlength="500" required placeholder="Explain why this adjustment is necessary"></textarea></div><div class="field full"><button class="btn">Confirm adjustment</button></div></form></section>
<?php require APP_ROOT.'/includes/footer.php';
