<?php
declare(strict_types=1);

function transition_withdrawal(int $id, string $target, ?string $reason, int $adminId): void
{
    $allowed = ['PENDING'=>['UNDER_REVIEW','REJECTED','CANCELLED'],'UNDER_REVIEW'=>['APPROVED','REJECTED'],'APPROVED'=>['PROCESSING'],'PROCESSING'=>['PAID']];
    $pdo = db(); $pdo->beginTransaction();
    try {
        $stmt=$pdo->prepare('SELECT w.*, wa.balance, u.status user_status FROM withdrawals w JOIN wallets wa ON wa.id=w.wallet_id JOIN users u ON u.id=w.user_id WHERE w.id=? FOR UPDATE');
        $stmt->execute([$id]); $wd=$stmt->fetch(); if (!$wd) throw new RuntimeException('Withdrawal not found.');
        if (!in_array($target, $allowed[$wd['status']] ?? [], true)) throw new RuntimeException('Invalid withdrawal status transition.');
        if ($target === 'REJECTED' && trim((string)$reason) === '') throw new InvalidArgumentException('Rejection reason is required.');
        if ($target === 'APPROVED') {
            if ($wd['user_status'] !== 'ACTIVE' || decimal_compare((string)$wd['balance'], (string)$wd['amount']) < 0) throw new RuntimeException('User is inactive or balance is insufficient.');
            $limitStmt=$pdo->prepare('SELECT * FROM transaction_limits WHERE user_id=? OR user_id IS NULL ORDER BY user_id IS NULL ASC LIMIT 1');$limitStmt->execute([$wd['user_id']]);$limits=$limitStmt->fetch();
            if($limits&&(decimal_compare((string)$wd['amount'],(string)$limits['min_withdrawal'])<0||decimal_compare((string)$wd['amount'],(string)$limits['max_withdrawal'])>0)) throw new RuntimeException('Withdrawal no longer meets current transaction limits.');
            $daily=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id=? AND id<>? AND reviewed_at>=CURDATE() AND status IN ('APPROVED','PROCESSING','PAID')");
            $daily->execute([$wd['user_id'],$id]);
            if($limits&&decimal_compare(decimal_add((string)$daily->fetchColumn(),(string)$wd['amount']),(string)$limits['daily_withdrawal'])>0) throw new RuntimeException('Approval would exceed the daily withdrawal limit.');
            $critical=$pdo->prepare("SELECT COUNT(*) FROM security_events WHERE user_id=? AND risk_level='CRITICAL' AND created_at>DATE_SUB(NOW(),INTERVAL 24 HOUR)");$critical->execute([$wd['user_id']]);
            if((int)$critical->fetchColumn()>0) throw new RuntimeException('Critical recent security activity requires investigation before approval.');
            $other=$pdo->prepare("SELECT COUNT(*) FROM withdrawals WHERE user_id=? AND id<>? AND status IN ('PENDING','UNDER_REVIEW')");$other->execute([$wd['user_id'],$id]);if((int)$other->fetchColumn()>0)throw new RuntimeException('Another withdrawal for this user is still pending review.');
            $new=decimal_subtract((string)$wd['balance'], (string)$wd['amount']); $txn=transaction_code('TXN');
            $pdo->prepare('UPDATE wallets SET balance=?,updated_at=NOW() WHERE id=?')->execute([$new,$wd['wallet_id']]);
            $pdo->prepare('INSERT INTO wallet_transactions (transaction_id,user_id,wallet_id,type,amount,previous_balance,new_balance,status,reference,description,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())')
                ->execute([$txn,$wd['user_id'],$wd['wallet_id'],'WITHDRAWAL',$wd['amount'],$wd['balance'],$new,'SUCCESS',$wd['withdrawal_id'],'Withdrawal approved',$adminId]);
        }
        $reviewed = in_array($target,['UNDER_REVIEW','APPROVED','REJECTED'],true) ? date('Y-m-d H:i:s') : $wd['reviewed_at'];
        $processed = $target === 'PAID' ? date('Y-m-d H:i:s') : $wd['processed_at'];
        $pdo->prepare('UPDATE withdrawals SET status=?,rejection_reason=?,reviewed_at=?,processed_at=?,reviewed_by=?,updated_at=NOW() WHERE id=?')->execute([$target,$target==='REJECTED'?$reason:null,$reviewed,$processed,$adminId,$id]);
        $pdo->prepare('INSERT INTO withdrawal_attempts(withdrawal_id,user_id,amount,ip_address,outcome,reason) VALUES(?,?,?,?,?,?)')->execute([$id,$wd['user_id'],$wd['amount'],client_ip(),'ADMIN_'.$target,$reason]);
        audit_log($adminId,'WITHDRAWAL_'.$target,'withdrawals',(string)$id,['status'=>$wd['status']],['status'=>$target,'reason'=>$reason]);
        $pdo->commit();
    } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
}
