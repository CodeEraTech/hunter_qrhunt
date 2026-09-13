<?php
declare(strict_types=1);
function finance_payout_reference(PDO $pdo,string $kind,int $id,string $reference,string $note,int $adminId): void
{
    $reference=trim($reference);
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._:\/-]{3,119}$/',$reference)) throw new InvalidArgumentException('Enter a valid bank UTR / transfer reference (4–120 characters).');
    $pdo->prepare('INSERT INTO finance_payout_records(kind,request_id,reference,note,admin_id) VALUES(?,?,?,?,?)')->execute([$kind,$id,$reference,substr($note,0,500),$adminId]);
}
function finance_vendor_withdrawal(int $id,string $target,string $note,string $reference,int $adminId): void
{
    require_permission('vendor_settlements.approve');
    $pdo=db();$pdo->beginTransaction();
    try {
        $s=$pdo->prepare('SELECT r.*,v.escrow_balance,v.status vendor_status FROM vendor_payout_requests r JOIN vendors v ON v.id=r.vendor_id WHERE r.id=? FOR UPDATE');$s->execute([$id]);$r=$s->fetch();
        if(!$r) throw new RuntimeException('Request not found.');
        $allowed=['PENDING'=>['UNDER_REVIEW','APPROVED','REJECTED','HELD'],'UNDER_REVIEW'=>['APPROVED','REJECTED','HELD'],'HELD'=>['UNDER_REVIEW','REJECTED'],'APPROVED'=>['PROCESSING'],'PROCESSING'=>['PAID']];
        if(!in_array($target,$allowed[$r['status']]??[],true)) throw new RuntimeException('Invalid withdrawal transition.');
        if(trim($note)==='' || strlen($note)>500) throw new InvalidArgumentException('An audit note of at most 500 characters is required.');
        if($target==='APPROVED') {
            if($r['vendor_status']!=='ACTIVE' || decimal_compare((string)$r['escrow_balance'],(string)$r['amount'])<0) throw new RuntimeException('Vendor is inactive or the balance is insufficient.');
            $new=decimal_subtract((string)$r['escrow_balance'],(string)$r['amount']);
            $pdo->prepare('UPDATE vendors SET escrow_balance=? WHERE id=?')->execute([$new,$r['vendor_id']]);
            $pdo->prepare('INSERT INTO finance_vendor_debits(request_id,vendor_id,amount,previous_balance,new_balance) VALUES(?,?,?,?,?)')->execute([$id,$r['vendor_id'],$r['amount'],$r['escrow_balance'],$new]);
        }
        if(in_array($target,['PROCESSING','PAID'],true)) {
            $s=$pdo->prepare('SELECT request_id FROM finance_vendor_debits WHERE request_id=?');$s->execute([$id]);
            if(!$s->fetch()) throw new RuntimeException('This legacy approval has no recorded wallet debit. Reconcile it before payout.');
        }
        if($target==='PAID') finance_payout_reference($pdo,'VENDOR',$id,$reference,$note,$adminId);
        $pdo->prepare('UPDATE vendor_payout_requests SET status=?,paid_at=IF(?="PAID",NOW(),paid_at) WHERE id=?')->execute([$target,$target,$id]);
        $pdo->prepare('INSERT INTO finance_vendor_payout_events(request_id,old_status,new_status,note,admin_id,ip_address) VALUES(?,?,?,?,?,?)')->execute([$id,$r['status'],$target,$note,$adminId,client_ip()]);
        audit_log($adminId,'VENDOR_WITHDRAWAL_'.$target,'vendor_payout_requests',(string)$id,['status'=>$r['status']],['status'=>$target,'note'=>$note,'reference'=>$reference]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
