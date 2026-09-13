<?php
declare(strict_types=1);

function finance_ready(): bool
{
    try { db()->query('SELECT id FROM finance_master_account LIMIT 1'); return true; }
    catch (PDOException $e) { if ($e->getCode()==='42S02') return false; throw $e; }
}
function finance_payload(array $input): array
{
    $operation=(string)($input['operation']??''); $target=(string)($input['target']??'');
    $amount=trim((string)($input['amount']??'')); $reason=trim((string)($input['reason']??''));
    $reference=trim((string)($input['reference']??'')); $recipient=(int)($input['recipient_id']??0);
    if (!in_array($operation,['FUND','DISTRIBUTE','RECALL'],true) || !preg_match('/^\d{1,12}(\.\d{1,2})?$/',$amount) || decimal_compare($amount,'0.00')<=0 || $reason==='' || strlen($reason)>500 || strlen($reference)>120) throw new InvalidArgumentException('Enter a positive amount and an audit reason of at most 500 characters.');
    if ($operation==='FUND') { $target='MASTER'; $recipient=0; if ($reference==='') throw new InvalidArgumentException('The received funding reference is required.'); }
    elseif (!in_array($target,['VENDOR','REAL','BONUS'],true) || $recipient<1) throw new InvalidArgumentException('Select a target and a valid recipient ID.');
    return ['operation'=>$operation,'target'=>$target,'amount'=>cents_to_decimal(decimal_to_cents($amount)),'reason'=>$reason,'reference'=>$reference,'recipient_id'=>$recipient];
}
function finance_authorize(array $payload): void
{
    require_permission($payload['operation']==='FUND' ? 'wallet.master.fund' : 'wallet.master.distribute');
}
/** Called with security row locked, failures persist even when authentication is rejected. */
function finance_security_failure(PDO $pdo, int $adminId): never
{
    $pdo->prepare('UPDATE finance_admin_security SET failures=failures+1,locked_until=IF(failures>=5,DATE_ADD(NOW(),INTERVAL 15 MINUTE),locked_until) WHERE admin_id=?')->execute([$adminId]);
    $pdo->commit();
    log_security(null,'MASTER_WALLET_AUTH_FAILED','HIGH','Master wallet security verification failed',['admin_id'=>$adminId]);
    throw new RuntimeException('Invalid security code. Five failed attempts lock wallet operations for 15 minutes.');
}
function finance_security_row(PDO $pdo,int $adminId): array
{
    $s=$pdo->prepare('SELECT s.*,a.status,a.mfa_enabled,a.mfa_secret_encrypted FROM finance_admin_security s JOIN admins a ON a.id=s.admin_id WHERE s.admin_id=? FOR UPDATE');$s->execute([$adminId]);$row=$s->fetch();
    if (!$row || $row['status']!=='ACTIVE' || !$row['mfa_enabled'] || !$row['mfa_secret_encrypted']) throw new RuntimeException('Set up your wallet PIN and authenticator MFA first.');
    if ($row['locked_until'] && strtotime($row['locked_until'])>time()) throw new RuntimeException('Wallet security is temporarily locked. Try again later.');
    if ($row['locked_until']) $pdo->prepare('UPDATE finance_admin_security SET failures=0,locked_until=NULL WHERE admin_id=?')->execute([$adminId]);
    return $row;
}
function finance_verify_pin(int $adminId,string $pin): void
{
    $pdo=db();$pdo->beginTransaction();
    try { $row=finance_security_row($pdo,$adminId); if (!preg_match('/^\d{6}$/',$pin) || !password_verify($pin,$row['pin_hash'])) finance_security_failure($pdo,$adminId); $pdo->commit(); }
    catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function finance_set_pin(int $adminId,string $password,string $pin,string $otp): void
{
    if (!preg_match('/^\d{6}$/',$pin)) throw new InvalidArgumentException('Choose a six-digit PIN.');
    $pdo=db();$pdo->beginTransaction();
    try {
        $s=$pdo->prepare('SELECT * FROM admins WHERE id=? AND status="ACTIVE" FOR UPDATE');$s->execute([$adminId]);$a=$s->fetch();
        $h=hash('sha256','wallet-pin-setup:'.$adminId);$s=$pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE identity_hash=? AND successful=0 AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');$s->execute([$h]);
        if ((int)$s->fetchColumn()>=5) throw new RuntimeException('Too many PIN setup attempts. Try again in 15 minutes.');
        $ok=$a && password_verify($password,$a['password_hash']) && $a['mfa_enabled'] && $a['mfa_secret_encrypted'] && verify_totp(decrypt_secret($a['mfa_secret_encrypted']),$otp);
        $pdo->prepare('INSERT INTO login_attempts(identity_hash,ip_address,successful) VALUES(?,?,?)')->execute([$h,client_ip(),$ok?1:0]);
        if (!$ok) { $pdo->commit(); throw new RuntimeException('Current password and enabled authenticator MFA are required.'); }
        $pdo->prepare('INSERT INTO finance_admin_security(admin_id,pin_hash,last_otp_hash,last_otp_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE pin_hash=VALUES(pin_hash),last_otp_hash=VALUES(last_otp_hash),last_otp_at=NOW()')->execute([$adminId,password_hash($pin,PASSWORD_DEFAULT),hash('sha256',$otp)]);
        audit_log($adminId,'WALLET_PIN_SET','master_wallet');$pdo->commit();unset($_SESSION['finance_pending']);
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
/** Both account updates, OTP consumption and the ledger commit together. */
function finance_transfer(int $adminId,array $payload,string $key,string $otp): string
{
    $p=finance_payload($payload); finance_authorize($p);
    if (!preg_match('/^master:[a-f0-9]{48}$/',$key)) throw new InvalidArgumentException('Invalid operation key.');
    $pending=$_SESSION['finance_pending']??null;
    if (!$pending || ($pending['admin_id']??0)!==$adminId || ($pending['expires']??0)<time() || !hash_equals((string)($pending['key']??''),$key) || ($pending['payload']??[])!==$p) throw new RuntimeException('Verify your PIN and review these exact transfer details first.');
    $pdo=db();$pdo->beginTransaction();
    try {
        $master=$pdo->query('SELECT balance FROM finance_master_account WHERE id=1 FOR UPDATE')->fetchColumn();
        if ($master===false) throw new RuntimeException('Master account is not initialized.');
        $s=$pdo->prepare('SELECT transaction_id,admin_id FROM finance_master_entries WHERE idempotency_key=?');$s->execute([$key]);
        if ($old=$s->fetch()) { if ((int)$old['admin_id']!==$adminId) throw new RuntimeException('Invalid operation owner.'); $pdo->commit();return $old['transaction_id']; }
        $security=finance_security_row($pdo,$adminId);
        if (!verify_totp(decrypt_secret($security['mfa_secret_encrypted']),$otp) || ($security['last_otp_at'] && strtotime($security['last_otp_at'])>time()-90 && hash_equals((string)$security['last_otp_hash'],hash('sha256',$otp)))) finance_security_failure($pdo,$adminId);
        $new=$p['operation']==='DISTRIBUTE' ? decimal_subtract((string)$master,$p['amount']) : decimal_add((string)$master,$p['amount']);
        if (decimal_compare($new,'0.00')<0) throw new RuntimeException('Insufficient master wallet balance.');
        $txn=transaction_code('ADM');$before=null;$after=null;
        if ($p['target']==='VENDOR') {
            $s=$pdo->prepare('SELECT escrow_balance FROM vendors WHERE id=? AND status="ACTIVE" FOR UPDATE');$s->execute([$p['recipient_id']]);$before=$s->fetchColumn();
            if ($before===false) throw new RuntimeException('Active vendor not found.');
            $after=$p['operation']==='DISTRIBUTE' ? decimal_add((string)$before,$p['amount']) : decimal_subtract((string)$before,$p['amount']);
            if (decimal_compare($after,'0.00')<0) throw new RuntimeException('Insufficient vendor balance.');
            $pdo->prepare('UPDATE vendors SET escrow_balance=? WHERE id=?')->execute([$after,$p['recipient_id']]);
        } elseif ($p['target']!=='MASTER') {
            $operation=$p['operation']==='DISTRIBUTE'?'CREDIT':'DEBIT';
            $result=$p['target']==='REAL'
                ? wallet_apply_in_transaction($pdo,$p['recipient_id'],$operation,$p['amount'],$txn,$p['reason'],$adminId,$key.':target')
                : wallet_bucket_apply_in_transaction($pdo,$p['recipient_id'],'BONUS',$p['amount'],$txn,$p['reason'],$adminId,$key.':target',$operation);
            $before=$result['previous_balance'];$after=$result['new_balance'];
        }
        $pdo->prepare('UPDATE finance_master_account SET balance=? WHERE id=1')->execute([$new]);
        $pdo->prepare('INSERT INTO finance_master_entries(transaction_id,operation,target,recipient_id,amount,previous_balance,new_balance,recipient_before,recipient_after,reference,funding_reference,reason,idempotency_key,admin_id,ip_address,user_agent) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$txn,$p['operation'],$p['target'],$p['recipient_id']?:null,$p['amount'],$master,$new,$before,$after,$p['reference'],$p['operation']==='FUND'?$p['reference']:null,$p['reason'],$key,$adminId,client_ip(),user_agent()]);
        $pdo->prepare('UPDATE finance_admin_security SET failures=0,locked_until=NULL,last_otp_hash=?,last_otp_at=NOW() WHERE admin_id=?')->execute([hash('sha256',$otp),$adminId]);
        audit_log($adminId,'MASTER_'.$p['operation'],'master_wallet',$txn,['balance'=>$master],$p+['balance'=>$new,'pin_verified'=>true,'otp_verified'=>true]);
        $pdo->commit();return $txn;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
