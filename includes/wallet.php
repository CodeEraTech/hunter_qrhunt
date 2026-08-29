<?php
declare(strict_types=1);

/** Apply one balance mutation while the caller holds an open database transaction. */
function wallet_apply_in_transaction(PDO $pdo, int $userId, string $type, string $amount, string $reference, string $description, ?int $adminId, string $idempotencyKey): array
{
    if (!$pdo->inTransaction()) throw new LogicException('Wallet mutation requires an active transaction.');
    if (!in_array($type, ['CREDIT','DEBIT','WITHDRAWAL','REFUND','ADJUSTMENT'], true) || !preg_match('/^\d{1,16}(\.\d{1,2})?$/', $amount) || decimal_compare($amount, '0.00') <= 0 || strlen($idempotencyKey) < 12 || strlen($idempotencyKey) > 100) throw new InvalidArgumentException('Invalid transaction details.');
    $prior=$pdo->prepare('SELECT id,transaction_id,previous_balance,new_balance FROM wallet_transactions WHERE idempotency_key=? FOR UPDATE');$prior->execute([$idempotencyKey]);
    if($existing=$prior->fetch()) return $existing+['created'=>false];
    $stmt=$pdo->prepare('SELECT w.*,u.status user_status FROM wallets w JOIN users u ON u.id=w.user_id WHERE w.user_id=? FOR UPDATE');$stmt->execute([$userId]);$wallet=$stmt->fetch();
    if(!$wallet||$wallet['status']!=='ACTIVE'||$wallet['user_status']!=='ACTIVE')throw new RuntimeException('Wallet or user is not active.');
    $previous=(string)$wallet['balance'];$isCredit=in_array($type,['CREDIT','REFUND'],true);$new=$isCredit?decimal_add($previous,$amount):decimal_subtract($previous,$amount);
    if(decimal_compare($new,'0.00')<0)throw new RuntimeException('Insufficient wallet balance.');
    $txn=transaction_code('TXN');$pdo->prepare('UPDATE wallets SET balance=?,updated_at=NOW() WHERE id=?')->execute([$new,$wallet['id']]);
    $pdo->prepare('INSERT INTO wallet_transactions(transaction_id,user_id,wallet_id,type,amount,previous_balance,new_balance,status,reference,description,idempotency_key,created_by,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())')->execute([$txn,$userId,$wallet['id'],$type,$amount,$previous,$new,'SUCCESS',substr($reference,0,120),substr($description,0,500),$idempotencyKey,$adminId]);
    return ['id'=>(int)$pdo->lastInsertId(),'transaction_id'=>$txn,'previous_balance'=>$previous,'new_balance'=>$new,'created'=>true];
}

function wallet_bucket_apply_in_transaction(PDO $pdo,int $userId,string $bucket,string $amount,string $reference,string $description,?int $adminId,string $idempotencyKey,string $operation): array
{
    if(!$pdo->inTransaction())throw new LogicException('Wallet mutation requires an active transaction.');
    if(!in_array($bucket,['REAL','BONUS','PROMO'],true)||!preg_match('/^\d{1,16}(\.\d{1,2})?$/',$amount)||decimal_compare($amount,'0.00')<=0||strlen($idempotencyKey)<8)throw new InvalidArgumentException('Invalid bucket transaction.');
    $prior=$pdo->prepare('SELECT id,transaction_id,previous_balance,new_balance FROM wallet_transactions WHERE idempotency_key=? FOR UPDATE');$prior->execute([$idempotencyKey]);if($old=$prior->fetch())return $old+['created'=>false];
    $s=$pdo->prepare('SELECT w.*,u.status user_status FROM wallets w JOIN users u ON u.id=w.user_id WHERE w.user_id=? FOR UPDATE');$s->execute([$userId]);$wallet=$s->fetch();if(!$wallet||$wallet['status']!=='ACTIVE'||$wallet['user_status']!=='ACTIVE')throw new RuntimeException('Wallet or user is not active.');
    $column=['REAL'=>'balance','BONUS'=>'bonus_balance','PROMO'=>'promo_balance'][$bucket];$previous=(string)$wallet[$column];$credit=str_contains($operation,'CREDIT');$new=$credit?decimal_add($previous,$amount):decimal_subtract($previous,$amount);if(decimal_compare($new,'0.00')<0)throw new RuntimeException('Insufficient '.$bucket.' balance.');
    $txn=transaction_code('TXN');$pdo->prepare("UPDATE wallets SET {$column}=?,updated_at=NOW() WHERE id=?")->execute([$new,$wallet['id']]);$type=['REAL'=>'ADJUSTMENT','BONUS'=>'BONUS_'.($credit?'CREDIT':'DEBIT'),'PROMO'=>'PROMO_'.($credit?'CREDIT':'DEBIT')][$bucket];
    $pdo->prepare('INSERT INTO wallet_transactions(transaction_id,user_id,wallet_id,type,bucket,amount,previous_balance,new_balance,status,reference,description,idempotency_key,created_by,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())')->execute([$txn,$userId,$wallet['id'],$type,$bucket,$amount,$previous,$new,'SUCCESS',substr($reference,0,120),substr($description,0,500),$idempotencyKey,$adminId]);return ['id'=>(int)$pdo->lastInsertId(),'transaction_id'=>$txn,'previous_balance'=>$previous,'new_balance'=>$new,'created'=>true];
}

function wallet_grant_bucket(int $userId,string $bucket,string $amount,string $reference,string $description,?int $adminId,string $idempotencyKey,?string $expiresAt=null): array
{
    if(!in_array($bucket,['BONUS','PROMO'],true))throw new InvalidArgumentException('Only bonus and promo buckets can be granted here.');$pdo=db();$pdo->beginTransaction();try{$r=wallet_bucket_apply_in_transaction($pdo,$userId,$bucket,$amount,$reference,$description,$adminId,$idempotencyKey,'CREDIT');if($r['created']&&$expiresAt){$wallet=$pdo->prepare('SELECT id FROM wallets WHERE user_id=?');$wallet->execute([$userId]);$walletId=(int)$wallet->fetchColumn();$pdo->prepare('INSERT INTO wallet_expiry_jobs(bucket,wallet_id,amount,reference,expires_at) VALUES(?,?,?,?,?)')->execute([$bucket,$walletId,$amount,$reference,$expiresAt]);$pdo->prepare("UPDATE wallets SET {$bucket}_expires_at=? WHERE id=?")->execute([$expiresAt,$walletId]);}if($r['created'])audit_log($adminId,'WALLET_'.$bucket.'_CREDIT','wallet',(string)$userId,null,['amount'=>$amount,'reference'=>$reference,'expires_at'=>$expiresAt]);$pdo->commit();return $r;}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

function wallet_checkout(int $userId,string $billAmount,?int $merchantId,string $key,?string $couponCode=null,?string $grantId=null): array
{
    if(!preg_match('/^\d{1,16}(\.\d{1,2})?$/',$billAmount)||decimal_compare($billAmount,'0.00')<=0||!preg_match('/^[A-Za-z0-9._:-]{16,100}$/',$key))throw new InvalidArgumentException('Invalid checkout details.');
    $pdo=db();$pdo->beginTransaction();
    try {
        $old=$pdo->prepare('SELECT * FROM wallet_checkout_transactions WHERE user_id=? AND idempotency_key=? FOR UPDATE');$old->execute([$userId,$key]);if($existing=$old->fetch()){$pdo->commit();return $existing+['created'=>false];}
        $coupon=null;if($couponCode!==null&&trim($couponCode)!==''){$s=$pdo->prepare('SELECT * FROM coupons WHERE code_hash=? AND status="ACTIVE" AND (user_id IS NULL OR user_id=?) FOR UPDATE');$s->execute([hash('sha256',strtoupper(trim($couponCode))),$userId]);$coupon=$s->fetch();if(!$coupon)throw new RuntimeException('Coupon is invalid, used, or expired.');if($coupon['expires_at']!==null&&$coupon['expires_at']<date('Y-m-d H:i:s'))throw new RuntimeException('Coupon has expired.');if(decimal_compare($billAmount,(string)$coupon['min_cart_amount'])<0)throw new RuntimeException('Minimum cart amount is '.money($coupon['min_cart_amount']).'.');if($coupon['merchant_id']!==null&&$merchantId!==(int)$coupon['merchant_id'])throw new RuntimeException('Coupon is restricted to another merchant.');$billAmount=decimal_compare($billAmount,(string)$coupon['discount_amount'])<=0?'0.01':decimal_subtract($billAmount,(string)$coupon['discount_amount']);}
        $w=$pdo->prepare('SELECT * FROM wallets WHERE user_id=? AND status="ACTIVE" FOR UPDATE');$w->execute([$userId]);$wallet=$w->fetch();if(!$wallet)throw new RuntimeException('Wallet is not active.');$rule=(float)$pdo->query('SELECT bonus_burn_percent FROM wallet_bonus_rules ORDER BY id LIMIT 1')->fetchColumn();$remaining=$billAmount;$promo=decimal_compare((string)$wallet['promo_balance'],$remaining)<0?(string)$wallet['promo_balance']:$remaining;$remaining=decimal_subtract($remaining,$promo);$maxBonus=cents_to_decimal((int)round(decimal_to_cents($billAmount)*$rule/100));$bonus=decimal_compare((string)$wallet['bonus_balance'],$maxBonus)<0?(string)$wallet['bonus_balance']:$maxBonus;if(decimal_compare($bonus,$remaining)>0)$bonus=$remaining;$remaining=decimal_subtract($remaining,$bonus);$real=decimal_compare((string)$wallet['balance'],$remaining)<0?(string)$wallet['balance']:$remaining;if(decimal_compare($real,$remaining)<0)throw new RuntimeException('Insufficient combined wallet balance.');$checkout=transaction_code('CHK');foreach([['PROMO',$promo,'Promo cash checkout'],['BONUS',$bonus,'Bonus balance checkout'],['REAL',$real,'Real cash checkout']] as [$bucket,$amount,$desc])if(decimal_compare($amount,'0.00')>0)wallet_bucket_apply_in_transaction($pdo,$userId,$bucket,$amount,$checkout,$desc,null,$key.':'.strtolower($bucket),'DEBIT');$pdo->prepare('INSERT INTO wallet_checkout_transactions(checkout_id,user_id,merchant_id,bill_amount,promo_used,bonus_used,real_used,status,idempotency_key) VALUES(?,?,?,?,?,?,?,"SUCCESS",?)')->execute([$checkout,$userId,$merchantId,$billAmount,$promo,$bonus,$real,$key]);
        if($coupon)$pdo->prepare('UPDATE coupons SET status="USED",used_at=NOW() WHERE id=?')->execute([$coupon['id']]);if($grantId)$pdo->prepare('UPDATE qr_promo_grants SET status="SPENT",spent_at=NOW(),checkout_reference=? WHERE grant_id=? AND user_id=? AND status="ACTIVE"')->execute([$checkout,$grantId,$userId]);if($merchantId){$fee=cents_to_decimal((int)round(decimal_to_cents($billAmount)*2/100));$pdo->prepare('INSERT INTO merchant_settlements(settlement_id,checkout_id,merchant_id,bill_amount,promo_amount,customer_paid,platform_fee,merchant_amount,status,settled_at) VALUES(?,?,?,?,?,?,?, ?,"SETTLED",NOW())')->execute([transaction_code('SET'),$checkout,$merchantId,$billAmount,$promo,decimal_add($bonus,$real),$fee,decimal_subtract($billAmount,$fee)]);}
        $pdo->commit();audit_log(null,'WALLET_CHECKOUT','wallet_checkout',$checkout,null,['user_id'=>$userId,'bill_amount'=>$billAmount,'promo_used'=>$promo,'bonus_used'=>$bonus,'real_used'=>$real,'coupon_id'=>$coupon['id']??null]);return ['checkout_id'=>$checkout,'bill_amount'=>$billAmount,'promo_used'=>$promo,'bonus_used'=>$bonus,'real_used'=>$real,'status'=>'SUCCESS','created'=>true];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

function process_wallet_expiries(int $limit=100): int
{
    $pdo=db();$processed=0;for($i=0;$i<$limit;$i++){ $pdo->beginTransaction();try{$s=$pdo->query("SELECT j.*,w.user_id,w.bonus_balance,w.promo_balance FROM wallet_expiry_jobs j JOIN wallets w ON w.id=j.wallet_id WHERE j.status='PENDING' AND j.expires_at<=NOW() ORDER BY j.id LIMIT 1 FOR UPDATE");$job=$s->fetch();if(!$job){$pdo->commit();break;}$current=(string)$job[$job['bucket']==='BONUS'?'bonus_balance':'promo_balance'];$amount=decimal_compare($current,(string)$job['amount'])<0?$current:(string)$job['amount'];if(decimal_compare($amount,'0.00')>0)wallet_bucket_apply_in_transaction($pdo,(int)$job['user_id'],$job['bucket'],$amount,$job['reference'],'Expired '.$job['bucket'].' balance',null,'expiry-wallet:'.$job['id'],'DEBIT');$pdo->prepare("UPDATE wallet_expiry_jobs SET status='PROCESSED',processed_at=NOW() WHERE id=?")->execute([$job['id']]);$column=$job['bucket']==='BONUS'?'bonus_expires_at':'promo_expires_at';$pdo->prepare("UPDATE wallets SET {$column}=NULL WHERE id=?")->execute([$job['wallet_id']]);if($job['bucket']==='PROMO')$pdo->prepare('UPDATE qr_promo_grants SET status="EXPIRED",reversed_at=NOW() WHERE grant_id=? AND status="ACTIVE"')->execute([$job['reference']]);audit_log(null,'WALLET_'.$job['bucket'].'_EXPIRED','wallet',(string)$job['wallet_id'],null,['amount'=>$amount,'reference'=>$job['reference']]);$pdo->commit();$processed++;}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Wallet expiry failed: '.$e->getMessage());break;}}return $processed;
}

function wallet_adjust(int $userId, string $type, string $amount, string $reference, string $description, int $adminId, string $idempotencyKey): array
{
    if (!in_array($type, ['CREDIT','DEBIT'], true) || !preg_match('/^\d{1,16}(\.\d{1,2})?$/', $amount) || decimal_compare($amount, '0.00') <= 0 || !preg_match('/^wallet-adjust:[a-f0-9]{48}$/', $idempotencyKey)) throw new InvalidArgumentException('Invalid transaction details.');
    $pdo = db(); $pdo->beginTransaction();
    try {
        $result=wallet_apply_in_transaction($pdo,$userId,$type,$amount,$reference,$description,$adminId,$idempotencyKey);
        if($result['created'])audit_log($adminId,'WALLET_'.$type,'wallet',(string)$userId,['balance'=>$result['previous_balance']],['balance'=>$result['new_balance'],'amount'=>$amount,'transaction_id'=>$result['transaction_id'],'reason'=>$description]);
        $pdo->commit();return $result;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

function wallet_reverse(int $transactionId, string $reason, int $adminId): array
{
    if(trim($reason)==='')throw new InvalidArgumentException('A reversal reason is required.');$pdo=db();$pdo->beginTransaction();
    try{$stmt=$pdo->prepare('SELECT t.*,w.balance,w.status wallet_status,u.status user_status FROM wallet_transactions t JOIN wallets w ON w.id=t.wallet_id JOIN users u ON u.id=t.user_id WHERE t.id=? FOR UPDATE');$stmt->execute([$transactionId]);$original=$stmt->fetch();
        if(!$original||$original['status']!=='SUCCESS'||$original['type']==='REVERSAL')throw new RuntimeException('Only successful, unreversed transactions can be reversed.');
        $exists=$pdo->prepare('SELECT transaction_id FROM wallet_transactions WHERE original_transaction_id=?');$exists->execute([$transactionId]);if($exists->fetch())throw new RuntimeException('This transaction was already reversed.');
        if($original['wallet_status']!=='ACTIVE')throw new RuntimeException('Wallet is not active.');$previous=(string)$original['balance'];$decrease=in_array($original['type'],['CREDIT','REFUND'],true);$new=$decrease?decimal_subtract($previous,(string)$original['amount']):decimal_add($previous,(string)$original['amount']);if(decimal_compare($new,'0.00')<0)throw new RuntimeException('The wallet does not have enough funds to reverse this credit.');
        $txn=transaction_code('TXN');$pdo->prepare('UPDATE wallets SET balance=?,updated_at=NOW() WHERE id=?')->execute([$new,$original['wallet_id']]);$pdo->prepare("UPDATE wallet_transactions SET status='REVERSED' WHERE id=?")->execute([$transactionId]);
        $pdo->prepare('INSERT INTO wallet_transactions(transaction_id,user_id,wallet_id,type,amount,previous_balance,new_balance,status,reference,description,original_transaction_id,created_by,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())')->execute([$txn,$original['user_id'],$original['wallet_id'],'REVERSAL',$original['amount'],$previous,$new,'SUCCESS',$original['transaction_id'],$reason,$transactionId,$adminId]);
        audit_log($adminId,'WALLET_REVERSAL','wallet',(string)$original['wallet_id'],['transaction_id'=>$original['transaction_id'],'balance'=>$previous],['transaction_id'=>$txn,'balance'=>$new,'reason'=>$reason]);$pdo->commit();return ['transaction_id'=>$txn,'previous_balance'=>$previous,'new_balance'=>$new];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
