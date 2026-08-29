<?php
declare(strict_types=1);

function payment_webhook_secret(string $provider): string
{
    global $config;
    $key=$provider==='RAZORPAY'?'razorpay_webhook_secret':'phonepe_webhook_secret';
    $secret=(string)($config[$key]??'');
    if ($secret === '') $secret=(string)(setting_value($key, '', true) ?? '');
    if(strlen($secret)<16)throw new RuntimeException($provider.' webhook secret is not configured.');
    return $secret;
}

function verify_payment_webhook_signature(string $provider,string $payload,string $signature): bool
{
    if(!in_array($provider,['RAZORPAY','PHONEPE'],true)||!preg_match('/^[a-f0-9]{64}$/i',$signature))return false;
    return hash_equals(hash_hmac('sha256',$payload,payment_webhook_secret($provider)),$signature);
}

function create_payment_transaction(array $user,int $huntId,string $provider,string $key): array
{
    if(!in_array($provider,['RAZORPAY','PHONEPE'],true)||!preg_match('/^[A-Za-z0-9._:-]{16,100}$/',$key))throw new InvalidArgumentException('Invalid payment request.');
    $pdo=db();$pdo->beginTransaction();
    try{
        $old=$pdo->prepare('SELECT payment_id,amount,status FROM payment_transactions WHERE user_id=? AND idempotency_key=? FOR UPDATE');$old->execute([$user['id'],$key]);
        if($row=$old->fetch()){$pdo->commit();return $row+['created'=>false];}
        $h=$pdo->prepare("SELECT activation_fee FROM hunts WHERE id=? AND status='ACTIVE' AND NOW() BETWEEN start_at AND end_at");$h->execute([$huntId]);$amount=$h->fetchColumn();
        if($amount===false)throw new RuntimeException('Hunt is not active.');
        $paymentCode=transaction_code('PAY');$providerOrder='HUNTER-'.$paymentCode;
        $pdo->prepare('INSERT INTO payment_transactions(payment_id,user_id,hunt_id,provider,provider_order_id,amount,status,idempotency_key) VALUES(?,?,?,?,?, ?,"PENDING",?)')->execute([$paymentCode,$user['id'],$huntId,$provider,$providerOrder,$amount,$key]);
        $pdo->commit();return ['payment_id'=>$paymentCode,'provider_order_id'=>$providerOrder,'amount'=>$amount,'status'=>'PENDING','created'=>true];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

function process_payment_webhook(string $provider,string $eventId,string $paymentCode,string $providerPaymentId,string $status,string $rawPayload,string $signature): array
{
    if(!verify_payment_webhook_signature($provider,$rawPayload,$signature))throw new RuntimeException('Invalid webhook signature.');
    if($eventId===''||strlen($eventId)>190)throw new InvalidArgumentException('Invalid event ID.');
    $payload=json_decode($rawPayload,true);
    if(!is_array($payload)||($payload['event_id']??null)!==$eventId||($payload['payment_id']??null)!==$paymentCode||($payload['provider_payment_id']??null)!==$providerPaymentId)throw new RuntimeException('Webhook identifiers do not match the signed payload.');
    $pdo=db();$pdo->beginTransaction();
    try{
        $seen=$pdo->prepare('SELECT status FROM payment_webhook_events WHERE provider=? AND event_id=? FOR UPDATE');$seen->execute([$provider,$eventId]);
        if($row=$seen->fetch()){$pdo->commit();return ['status'=>$row['status'],'duplicate'=>true];}
        $p=$pdo->prepare('SELECT * FROM payment_transactions WHERE payment_id=? AND provider=? FOR UPDATE');$p->execute([$paymentCode,$provider]);$payment=$p->fetch();
        if(!$payment)throw new RuntimeException('Payment transaction not found.');
        if(!isset($payload['amount'])||!is_string($payload['amount'])||decimal_compare($payload['amount'],(string)$payment['amount'])!==0||strtoupper((string)($payload['currency']??''))!==$payment['currency'])throw new RuntimeException('Webhook amount or currency does not match the server order.');
        $verified=strtoupper($status)==='SUCCESS';$next=$verified?'VERIFIED':'FAILED';
        $pdo->prepare('UPDATE payment_transactions SET provider_payment_id=?,status=?,signature_verified_at=IF(?="VERIFIED",NOW(),NULL),failure_reason=IF(?="FAILED","Provider reported failure",NULL) WHERE id=?')->execute([$providerPaymentId,$next,$next,$next,$payment['id']]);
        $pdo->prepare('INSERT INTO payment_webhook_events(provider,event_id,signature_hash,payload_hash,payment_transaction_id,status) VALUES(?,?,?,?,?,"ACCEPTED")')->execute([$provider,$eventId,hash('sha256',$signature),hash('sha256',$rawPayload),$payment['id']]);
        if($verified){
            $hunt=$pdo->prepare("SELECT end_at FROM hunts WHERE id=? AND status='ACTIVE' AND NOW() BETWEEN start_at AND end_at FOR UPDATE");$hunt->execute([$payment['hunt_id']]);$end=$hunt->fetchColumn();
            if($end===false)throw new RuntimeException('Paid hunt is no longer active.');
            $activation=$pdo->prepare('SELECT status FROM hunt_user_activations WHERE user_id=? AND hunt_id=? FOR UPDATE');$activation->execute([$payment['user_id'],$payment['hunt_id']]);$existing=$activation->fetch();
            if(!$existing)$pdo->prepare('INSERT INTO hunt_user_activations(user_id,hunt_id,activation_type,payment_transaction_id,status,expires_at) VALUES(?,?,"ONLINE",?,"ACTIVE",?)')->execute([$payment['user_id'],$payment['hunt_id'],$payment['id'],$end]);
            elseif($existing['status']!=='ACTIVE')throw new RuntimeException('Existing Hunt activation cannot be replaced.');
            audit_log(null,'HUNT_ACTIVATED','hunt_activation',null,null,['user_id'=>$payment['user_id'],'hunt_id'=>$payment['hunt_id'],'type'=>'ONLINE','payment_id'=>$payment['id']]);
        }
        audit_log(null,'PAYMENT_'.$next,'payments',(string)$payment['id'],['status'=>$payment['status']],['status'=>$next,'provider'=>$provider]);
        $pdo->commit();return ['status'=>$next,'duplicate'=>false];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
