<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';require_permission('vendor_settlements.view');
require_once APP_ROOT.'/includes/finance_directory.php';require_once APP_ROOT.'/includes/finance_withdrawals.php';
$source=(string)input('source','VENDOR');$id=filter_var(input('id'),FILTER_VALIDATE_INT);
if(!$id || !in_array($source,['VENDOR','MONEY','SETTLEMENT'],true)){
    flash('error','Invalid withdrawal request parameters.');
    redirect('/admin/withdrawals/index.php');
}
if(is_post()) {
    verify_csrf();require_permission('vendor_settlements.approve');
    try {
        if(!finance_ready())throw new RuntimeException('Financial setup must be completed first.');
        $target=(string)input('status');$note=trim((string)input('note'));$reference=trim((string)input('reference'));
        if($source==='VENDOR') finance_vendor_withdrawal($id,$target,$note,$reference,(int)admin()['id']);
        elseif($source==='MONEY') {
            $pdo=db();$pdo->beginTransaction();
            try {
                $s=$pdo->prepare('SELECT * FROM vendor_money_requests WHERE id=? FOR UPDATE');$s->execute([$id]);$old=$s->fetch();
                $allowed=['PENDING'=>['UNDER_REVIEW','APPROVED','REJECTED','HELD'],'UNDER_REVIEW'=>['APPROVED','REJECTED','HELD'],'HELD'=>['UNDER_REVIEW','REJECTED'],'APPROVED'=>['PROCESSING'],'PROCESSING'=>['SETTLED','FAILED','DISPUTED']];
                if(!$old || !in_array($target,$allowed[$old['status']]??[],true) || $note==='' || strlen($note)>500) throw new RuntimeException('A valid next status and audit note are required.');
                $pdo->prepare('UPDATE vendor_money_requests SET status=?,admin_note=?,processed_by=?,processed_at=NOW() WHERE id=?')->execute([$target,$note,admin()['id'],$id]);
                audit_log((int)admin()['id'],'VENDOR_MONEY_'.$target,'vendor_money_requests',(string)$id,['status'=>$old['status']],['status'=>$target,'note'=>$note]);$pdo->commit();
            }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        }else throw new RuntimeException('Settlement history is read-only.');
        flash('success','Request updated.');
    }catch(Throwable $e){flash('error',$e instanceof PDOException?'Could not update request. The reference may already be recorded.':$e->getMessage());}
    redirect('/admin/withdrawals/vendor.php?source='.$source.'&id='.$id);
}
$sql=finance_withdrawals_sql($source==='MONEY'?'money':($source==='SETTLEMENT'?'settlements':'withdrawals'));
$s=db()->prepare('SELECT * FROM ('.$sql.') directory WHERE source=? AND id=?');$s->execute([$source,$id]);$r=$s->fetch();
if(!$r){
    flash('error','Withdrawal request not found.');
    redirect('/admin/withdrawals/index.php');
}
$pageTitle=$r['code'];$pageSubtitle='Vendor request and settlement details';require APP_ROOT.'/includes/header.php'; ?>
<section class="panel"><div class="panel-head"><h2><?=e($r['party'])?> · <?=e($r['party_id'])?></h2><a class="btn secondary" href="<?=e(app_url('/admin/withdrawals/index.php'))?>">Withdrawals</a></div>
<?php foreach(['source'=>'Record type','status'=>'Status','created_at'=>'Requested','method'=>'Payout mode','destination'=>'Destination','reference'=>'UTR / Reference','settled_at'=>'Processed','note'=>'Audit note'] as $key=>$label):?><div class="kv"><span><?=$label?></span><strong><?=e($r[$key]??'Not recorded')?></strong></div><?php endforeach?><div class="kv"><span>Requested / settlement amount</span><strong><?=money($r['amount'])?></strong></div><div class="kv"><span>Current vendor balance</span><strong><?=money($r['balance'])?></strong></div>
<?php if($source==='MONEY'):?><p class="help">Money-request status tracks the review workflow. It does not move wallet funds; record any approved distribution in Admin Wallet.</p><?php endif ?></section>
<?php $actions=($source==='MONEY'?['PENDING'=>['UNDER_REVIEW','APPROVED','REJECTED','HELD'],'UNDER_REVIEW'=>['APPROVED','REJECTED','HELD'],'HELD'=>['UNDER_REVIEW','REJECTED'],'APPROVED'=>['PROCESSING'],'PROCESSING'=>['SETTLED','FAILED','DISPUTED']]:['PENDING'=>['UNDER_REVIEW','APPROVED','REJECTED','HELD'],'UNDER_REVIEW'=>['APPROVED','REJECTED','HELD'],'HELD'=>['UNDER_REVIEW','REJECTED'],'APPROVED'=>['PROCESSING'],'PROCESSING'=>['PAID']])[$r['status']]??[];
if($source!=='SETTLEMENT' && $actions && can('vendor_settlements.approve') && finance_ready()): ?>
<section class="panel"><h2>Review request</h2><form method="post" class="form-grid" data-confirm="Confirm this request transition?"><?=csrf_field()?><div class="field"><label>Next status</label><select name="status"><?php foreach($actions as $a):?><option><?=$a?></option><?php endforeach?></select></div><div class="field"><label>Reason / Audit note</label><input name="note" maxlength="500" required></div><div class="field"><label>Bank UTR / Reference (required for Paid)</label><input name="reference" maxlength="120"></div><div class="field"><button class="btn">Confirm transition</button></div></form><p class="help">Withdrawal approval deducts the available vendor balance once. Mark Paid only after the bank transfer completes.</p></section>
<?php endif ?>
<?php if(finance_ready() && $source==='VENDOR'): $s=db()->prepare('SELECT e.*,a.name FROM finance_vendor_payout_events e JOIN admins a ON a.id=e.admin_id WHERE request_id=? ORDER BY e.id DESC');$s->execute([$id]);?><section class="panel"><h2>Approval &amp; processing history</h2><div class="table-wrap"><table><thead><tr><th>Date</th><th>Transition</th><th>Note</th><th>Admin / IP</th></tr></thead><tbody><?php foreach($s->fetchAll() as $event):?><tr><td><?=e($event['created_at'])?></td><td><?=e($event['old_status'].' → '.$event['new_status'])?></td><td><?=e($event['note'])?></td><td><?=e($event['name'].' · '.$event['ip_address'])?></td></tr><?php endforeach?></tbody></table></div></section><?php endif ?>
<?php require APP_ROOT.'/includes/footer.php'; ?>
