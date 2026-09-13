<?php
declare(strict_types=1);
function wallet_direction(array $row): string
{
    $before=$row['previous_balance']??null;$after=$row['new_balance']??null;
    if($before!==null && $after!==null) return decimal_compare((string)$after,(string)$before)<0?'DEBIT':'CREDIT';
    return in_array($row['action']??$row['type']??'',['DEBIT','WITHDRAWAL','BONUS_DEBIT','PROMO_DEBIT','DISTRIBUTE'],true)?'DEBIT':'CREDIT';
}
function wallet_console_customer(string $search): ?array
{
    $search=trim($search);if($search==='')return null;
    $id=preg_match('/^(?:USR_|#)?([0-9]+)$/i',$search,$m)?$m[1]:null;
    $s=db()->prepare('SELECT u.id,u.name,u.mobile,u.email,u.status,u.is_verified,u.risk_level,w.balance,w.bonus_balance,w.promo_balance,w.status wallet_status FROM users u JOIN wallets w ON w.user_id=u.id WHERE u.mobile=? OR u.id=? ORDER BY (u.mobile=?) DESC LIMIT 1');$s->execute([$search,$id,$search]);return $s->fetch()?:null;
}
function wallet_console_adjust(int $userId,string $bucket,string $type,string $amount,string $reason,string $reference,string $key,int $adminId,?string $expectedBalance=null): array
{
    if(!in_array($bucket,['REAL','BONUS'],true)||!in_array($type,['CREDIT','DEBIT'],true)||trim($reason)===''||strlen($reason)>500||strlen($reference)>120||!preg_match('/^console:[a-f0-9]{48}$/',$key)||!preg_match('/^\d{1,12}(\.\d{1,2})?$/',$amount)||decimal_compare($amount,'0.00')<=0)throw new InvalidArgumentException('Valid wallet, amount and mandatory reason are required.');
    require_permission($type==='CREDIT'?'wallet.credit':'wallet.debit');if($bucket==='BONUS')require_permission('wallet.bonus');
    $pdo=db();$pdo->beginTransaction();try {
        $s=$pdo->prepare('SELECT * FROM wallets WHERE user_id=? FOR UPDATE');$s->execute([$userId]);$w=$s->fetch();if(!$w)throw new RuntimeException('Wallet not found.');
        $s=$pdo->prepare('SELECT * FROM wallet_transactions WHERE idempotency_key=?');$s->execute([$key]);$prior=$s->fetch();
        if($prior){if((int)$prior['user_id']!==$userId || $prior['bucket']!==$bucket || (int)$prior['created_by']!==$adminId || wallet_direction($prior)!==$type || decimal_compare($prior['amount'],$amount)!==0 || $prior['description']!==$reason || ($reference!=='' && $prior['reference']!==$reference))throw new RuntimeException('This confirmation key belongs to another adjustment.');$pdo->commit();return $prior+['created'=>false];}
        $column=$bucket==='REAL'?'balance':'bonus_balance';
        if($expectedBalance!==null && decimal_compare((string)$w[$column],$expectedBalance)!==0)throw new RuntimeException('The wallet balance changed after your preview. Fetch the customer and review again.');
        if($reference==='')$reference=transaction_code('ADJ');
        $r=$bucket==='REAL'?wallet_apply_in_transaction($pdo,$userId,$type,$amount,$reference,$reason,$adminId,$key):wallet_bucket_apply_in_transaction($pdo,$userId,'BONUS',$amount,$reference,$reason,$adminId,$key,$type);
        audit_log($adminId,'CONSOLE_'.$type,'wallet',(string)$userId,['balance'=>$r['previous_balance']],['balance'=>$r['new_balance'],'bucket'=>$bucket,'amount'=>$amount,'reference'=>$reference,'reason'=>$reason,'transaction_id'=>$r['transaction_id']]);$pdo->commit();return $r;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
