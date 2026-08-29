<?php
declare(strict_types=1);
require dirname(__DIR__).'/_bootstrap.php';
require_post();$user=api_user();$data=json_input();$key=trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY']??''));
if(!preg_match('/^[A-Za-z0-9._:-]{16,100}$/',$key))json_response(false,'A valid Idempotency-Key header is required',null,422);
if($prior=idempotent_response((int)$user['id'],'withdrawals.create',$key)){http_response_code((int)$prior['response_code']);echo $prior['response_body'];exit;}
api_rate_limit($user,'withdrawals.create',10,600);
$amount=trim((string)($data['amount']??''));$method=trim((string)($data['payment_method']??''));$account=(string)($data['payment_account']??$data['payment_account_masked']??'');
if(!preg_match('/^\d{1,16}(\.\d{1,2})?$/',$amount)||decimal_compare($amount,'0.00')<=0||$method===''||strlen($method)>50||$account==='')json_response(false,'Invalid withdrawal details',null,422);
$attempts=db()->prepare("SELECT COUNT(*) FROM withdrawal_attempts WHERE user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 10 MINUTE)");$attempts->execute([$user['id']]);if((int)$attempts->fetchColumn()>=5)json_response(false,'Too many withdrawal requests. Try again later.',null,429);
$masked=payment_account_mask($account);$pdo=db();$pdo->beginTransaction();
try{
    $wallet=$pdo->prepare('SELECT * FROM wallets WHERE user_id=? AND status="ACTIVE" FOR UPDATE');$wallet->execute([$user['id']]);$w=$wallet->fetch();if(!$w||decimal_compare((string)$w['balance'],$amount)<0)throw new RuntimeException('Insufficient available balance.');
    $limitStmt=$pdo->prepare('SELECT * FROM transaction_limits WHERE user_id=? OR user_id IS NULL ORDER BY user_id IS NULL ASC LIMIT 1');$limitStmt->execute([$user['id']]);$limits=$limitStmt->fetch();if($limits&&(decimal_compare($amount,(string)$limits['min_withdrawal'])<0||decimal_compare($amount,(string)$limits['max_withdrawal'])>0))throw new RuntimeException('Amount is outside withdrawal limits.');
    $daily=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id=? AND requested_at>=CURDATE() AND status NOT IN ('REJECTED','CANCELLED')");$daily->execute([$user['id']]);if($limits&&decimal_compare(decimal_add((string)$daily->fetchColumn(),$amount),(string)$limits['daily_withdrawal'])>0)throw new RuntimeException('Daily withdrawal limit exceeded.');
    $duplicate=$pdo->prepare("SELECT COUNT(*) FROM withdrawals WHERE user_id=? AND amount=? AND requested_at>DATE_SUB(NOW(),INTERVAL 2 MINUTE) AND status IN ('PENDING','UNDER_REVIEW','APPROVED','PROCESSING')");$duplicate->execute([$user['id'],$amount]);if((int)$duplicate->fetchColumn()>0)throw new RuntimeException('A matching withdrawal is already pending.');
    $recent=$pdo->prepare("SELECT COUNT(*) FROM withdrawals WHERE user_id=? AND requested_at>DATE_SUB(NOW(),INTERVAL 30 MINUTE)");$recent->execute([$user['id']]);$rapid=(int)$recent->fetchColumn()>=2;$newDevice=$pdo->prepare("SELECT COUNT(*) FROM security_events WHERE user_id=? AND event_type='NEW_DEVICE_LOGIN' AND created_at>DATE_SUB(NOW(),INTERVAL 24 HOUR)");$newDevice->execute([$user['id']]);$risk=(decimal_compare($amount,'25000.00')>=0||$rapid||(int)$newDevice->fetchColumn()>0)?'HIGH':'LOW';$wd=transaction_code('WD');$pdo->prepare('INSERT INTO withdrawals(withdrawal_id,user_id,wallet_id,amount,payment_method,payment_account_masked,status,risk_level) VALUES(?,?,?,?,?,?,"PENDING",?)')->execute([$wd,$user['id'],$w['id'],$amount,$method,$masked,$risk]);$withdrawalPk=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO withdrawal_attempts(withdrawal_id,user_id,idempotency_key,amount,ip_address,device_id,outcome) VALUES(?,?,?,?,?,?,"CREATED")')->execute([$withdrawalPk,$user['id'],$key,$amount,client_ip(),$user['device_id']]);
    log_security((int)$user['id'],'WITHDRAWAL_CREATED',$risk,'Withdrawal requested',['withdrawal_id'=>$wd,'device_id'=>$user['device_id']]);$body=safe_json(['status'=>true,'message'=>'Withdrawal request created','data'=>['withdrawal_id'=>$wd,'status'=>'PENDING']]);
    $pdo->prepare('INSERT INTO api_idempotency(user_id,idempotency_key,endpoint,response_code,response_body) VALUES(?,?,?,?,?)')->execute([$user['id'],$key,'withdrawals.create',201,$body]);$pdo->commit();http_response_code(201);echo $body;
}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    if($prior=idempotent_response((int)$user['id'],'withdrawals.create',$key)){http_response_code((int)$prior['response_code']);echo $prior['response_body'];exit;}
    try{db()->prepare('INSERT INTO withdrawal_attempts(user_id,idempotency_key,amount,ip_address,device_id,outcome,reason) VALUES(?,?,?,?,?,"REJECTED",?)')->execute([$user['id'],$key,preg_match('/^\d{1,16}(\.\d{1,2})?$/',$amount)?$amount:null,client_ip(),$user['device_id'],substr($e instanceof PDOException?'Processing failure':$e->getMessage(),0,500)]);}catch(Throwable $logError){error_log('Withdrawal attempt logging failed: '.$logError->getMessage());}
    json_response(false,$e instanceof PDOException?'Could not create withdrawal.':$e->getMessage(),null,422);
}
