<?php
declare(strict_types=1);

function normalized_answer(string $value): string { return mb_strtolower(trim(preg_replace('/\s+/u',' ',$value)??''),'UTF-8'); }
function answer_hash(string $value): string { return hash('sha256',normalized_answer($value)); }
function haversine_distance(float $lat1,float $lon1,float $lat2,float $lon2): float
{
    $earth=6371000.0;$latDelta=deg2rad($lat2-$lat1);$lonDelta=deg2rad($lon2-$lon1);$a=sin($latDelta/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($lonDelta/2)**2;
    return $earth*2*atan2(sqrt($a),sqrt(1-$a));
}
function setting_int(string $key,int $default): int { $s=db()->prepare('SELECT `value` FROM system_settings WHERE `key`=?');$s->execute([$key]);$v=$s->fetchColumn();return $v===false?$default:(int)$v; }
function log_scan_attempt(?int $userId,?int $huntId,?int $locationId,?int $qrId,?string $deviceId,string $result,string $reason,string $risk,array $meta=[],?float $lat=null,?float $lon=null,?float $distance=null): void
{
    db()->prepare('INSERT INTO hunt_scan_attempts(user_id,hunt_id,location_id,qr_code_id,device_id,ip_address,latitude,longitude,distance_meters,result,reason,risk_level,metadata) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$userId,$huntId,$locationId,$qrId,$deviceId,client_ip(),$lat,$lon,$distance,$result,substr($reason,0,500),$risk,safe_json($meta)]);
    if(in_array($risk,['HIGH','CRITICAL'],true))log_security($userId,'HUNT_SCAN_'.$result,$risk,$reason,$meta+['device_id'=>$deviceId]);
}
function activate_hunt_for_user(int $userId,int $huntId,string $type,?int $paymentId,?int $adminId): array
{
    if(!in_array($type,['ONLINE','COUNTER','ADMIN'],true))throw new InvalidArgumentException('Invalid activation type.');$pdo=db();$pdo->beginTransaction();
    try{$h=$pdo->prepare("SELECT * FROM hunts WHERE id=? AND status='ACTIVE' AND NOW() BETWEEN start_at AND end_at FOR UPDATE");$h->execute([$huntId]);$hunt=$h->fetch();if(!$hunt)throw new RuntimeException('Hunt is not currently active.');
        $u=$pdo->prepare("SELECT status FROM users WHERE id=? FOR UPDATE");$u->execute([$userId]);if($u->fetchColumn()!=='ACTIVE')throw new RuntimeException('User is not active.');
        if($type==='ONLINE'){$p=$pdo->prepare("SELECT id FROM payment_transactions WHERE id=? AND user_id=? AND hunt_id=? AND status='VERIFIED' AND amount=? FOR UPDATE");$p->execute([$paymentId,$userId,$huntId,$hunt['activation_fee']]);if(!$p->fetch())throw new RuntimeException('A matching server-verified payment is required.');}
        $existing=$pdo->prepare('SELECT * FROM hunt_user_activations WHERE user_id=? AND hunt_id=? FOR UPDATE');$existing->execute([$userId,$huntId]);if($row=$existing->fetch()){if($row['status']==='ACTIVE'){$pdo->commit();return $row+['created'=>false];}throw new RuntimeException('This hunt activation is not eligible for reactivation.');}
        $pdo->prepare('INSERT INTO hunt_user_activations(user_id,hunt_id,activation_type,payment_transaction_id,activated_by,status,expires_at) VALUES(?,?,?,?,?,"ACTIVE",?)')->execute([$userId,$huntId,$type,$paymentId,$adminId,$hunt['end_at']]);$id=(int)$pdo->lastInsertId();
        audit_log($adminId,'HUNT_ACTIVATED','hunt_activation',(string)$id,null,['user_id'=>$userId,'hunt_id'=>$huntId,'type'=>$type,'payment_id'=>$paymentId]);$pdo->commit();return ['id'=>$id,'status'=>'ACTIVE','created'=>true];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function validate_hunt_scan(array $user,string $token,float $latitude,float $longitude,?float $accuracy,array $signals=[]): array
{
    if($latitude < -90||$latitude>90||$longitude < -180||$longitude>180)throw new InvalidArgumentException('Invalid GPS coordinates.');
    $pdo=db();$pdo->beginTransaction();
    try{$hash=hash('sha256',$token);$q=$pdo->prepare('SELECT q.*,l.name location_name,l.latitude location_latitude,l.longitude location_longitude,l.radius_meters,l.status location_status,h.status hunt_status,h.start_at,h.end_at,a.id activation_id,a.status activation_status,a.expires_at activation_expires FROM hunt_qr_codes q JOIN hunt_locations l ON l.id=q.location_id JOIN hunts h ON h.id=q.hunt_id LEFT JOIN hunt_user_activations a ON a.user_id=? AND a.hunt_id=q.hunt_id WHERE q.token_hash=? FOR UPDATE');$q->execute([$user['id'],$hash]);$row=$q->fetch();
        if(!$row){$pdo->rollBack();log_scan_attempt((int)$user['id'],null,null,null,$user['device_id'],'REJECTED','Unknown QR token','HIGH',['token_hash_prefix'=>substr($hash,0,12)]);throw new RuntimeException('QR code is invalid.');}
        $distance=haversine_distance($latitude,$longitude,(float)$row['location_latitude'],(float)$row['location_longitude']);$risk='LOW';$reason='Validated';
        $maxAccuracy=setting_int('gps_max_accuracy_meters',150);if($accuracy!==null&&($accuracy<0||$accuracy>$maxAccuracy)){$risk='HIGH';$reason='GPS accuracy is outside the accepted threshold.';}
        if(!empty($signals['mock_location'])){$risk='CRITICAL';$reason='Device reported a mock-location signal.';}
        $shared=$pdo->prepare('SELECT COUNT(DISTINCT user_id) FROM user_devices WHERE device_id=?');$shared->execute([$user['device_id']]);if((int)$shared->fetchColumn()>1){$risk='HIGH';$reason='Device identifier is associated with multiple accounts.';}
        if($row['hunt_status']!=='ACTIVE'||date('Y-m-d H:i:s')<$row['start_at']||date('Y-m-d H:i:s')>$row['end_at'])$reason='Hunt is not active.';
        elseif(!$row['activation_id']||$row['activation_status']!=='ACTIVE'||($row['activation_expires']&&$row['activation_expires']<date('Y-m-d H:i:s')))$reason='Hunt activation is not active.';
        elseif($row['location_status']!=='ACTIVE')$reason='Location is inactive.';
        elseif($row['status']!=='ACTIVE'||($row['expires_at']&&$row['expires_at']<date('Y-m-d H:i:s')))$reason='QR code is inactive or expired.';
        elseif($row['max_scans']!==null&&(int)$row['scan_count']>=(int)$row['max_scans'])$reason='QR code scan limit has been reached.';
        elseif($distance>(float)$row['radius_meters']){$risk='HIGH';$reason='User is outside the allowed GPS radius.';}
        elseif($risk==='CRITICAL')$reason='Scan blocked by a critical risk signal.';
        else $reason='';
        if($reason!==''){$pdo->rollBack();log_scan_attempt((int)$user['id'],(int)$row['hunt_id'],(int)$row['location_id'],(int)$row['id'],$user['device_id'],'REJECTED',$reason,$risk,$signals,$latitude,$longitude,$distance);throw new RuntimeException($reason);}
        $dupe=$pdo->prepare('SELECT id FROM hunt_scans WHERE user_id=? AND qr_code_id=? FOR UPDATE');$dupe->execute([$user['id'],$row['id']]);if($dupe->fetch()){$pdo->rollBack();log_scan_attempt((int)$user['id'],(int)$row['hunt_id'],(int)$row['location_id'],(int)$row['id'],$user['device_id'],'DUPLICATE','QR was already claimed by this user','HIGH',[],$latitude,$longitude,$distance);throw new RuntimeException('This QR code was already claimed.');}
        $pdo->prepare('INSERT INTO hunt_scans(user_id,hunt_id,location_id,qr_code_id,activation_id,device_id,latitude,longitude,gps_accuracy_meters,distance_meters,allowed_radius_meters,ip_address,result,risk_level) VALUES(?,?,?,?,?,?,?,?,?,?,?, ?,"VALIDATED",?)')->execute([$user['id'],$row['hunt_id'],$row['location_id'],$row['id'],$row['activation_id'],$user['device_id'],$latitude,$longitude,$accuracy,number_format($distance,2,'.',''),$row['radius_meters'],client_ip(),$risk]);$scanId=(int)$pdo->lastInsertId();$pdo->prepare('UPDATE hunt_qr_codes SET scan_count=scan_count+1 WHERE id=?')->execute([$row['id']]);$pdo->commit();
        log_scan_attempt((int)$user['id'],(int)$row['hunt_id'],(int)$row['location_id'],(int)$row['id'],$user['device_id'],'VALIDATED','Scan validated',$risk,[],$latitude,$longitude,$distance);return ['scan_id'=>$scanId,'hunt_id'=>(int)$row['hunt_id'],'location_id'=>(int)$row['location_id'],'distance_meters'=>round($distance,2)];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function validate_secret_challenge(array $user,int $scanId,string $code): bool
{
    $pdo=db();$s=$pdo->prepare('SELECT s.* FROM hunt_scans s WHERE s.id=? AND s.user_id=? AND s.result="VALIDATED"');$s->execute([$scanId,$user['id']]);$scan=$s->fetch();if(!$scan)throw new RuntimeException('Validated scan not found.');
    $rate=$pdo->prepare("SELECT COUNT(*) FROM hunt_challenge_attempts WHERE user_id=? AND challenge_type='SECRET' AND attempted_at>DATE_SUB(NOW(),INTERVAL 10 MINUTE)");$rate->execute([$user['id']]);if((int)$rate->fetchColumn()>=10)throw new RuntimeException('Too many secret-code attempts.');
    $codes=$pdo->prepare("SELECT * FROM hunt_secret_codes WHERE hunt_id=? AND (location_id=? OR location_id IS NULL) AND status='ACTIVE' AND NOW() BETWEEN valid_from AND valid_until");$codes->execute([$scan['hunt_id'],$scan['location_id']]);$matched=null;foreach($codes->fetchAll() as $candidate)if(password_verify($code,$candidate['code_hash'])){$matched=$candidate;break;}
    $pdo->prepare('INSERT INTO hunt_challenge_attempts(scan_id,user_id,challenge_type,challenge_id,successful,answer_hash,ip_address,device_id) VALUES(?,?,"SECRET",?,?,?,?,?)')->execute([$scanId,$user['id'],$matched['id']??0,$matched?1:0,hash('sha256',$code),client_ip(),$user['device_id']]);if(!$matched){log_security((int)$user['id'],'HUNT_SECRET_FAILED','MEDIUM','Invalid hunt secret submitted',['device_id'=>$user['device_id'],'scan_id'=>$scanId]);throw new RuntimeException('Secret code is invalid or outside its validity window.');}return true;
}
function validate_question_challenge(array $user,int $scanId,int $questionId,string $answer): bool
{
    $pdo=db();$q=$pdo->prepare('SELECT q.*,s.hunt_id scan_hunt,s.location_id scan_location FROM hunt_questions q JOIN hunt_scans s ON s.id=? AND s.user_id=? AND s.result="VALIDATED" WHERE q.id=? AND q.status="ACTIVE" AND q.hunt_id=s.hunt_id AND (q.location_id=s.location_id OR q.location_id IS NULL)');$q->execute([$scanId,$user['id'],$questionId]);$question=$q->fetch();if(!$question)throw new RuntimeException('Question is not valid for this scan.');
    $rate=$pdo->prepare("SELECT COUNT(*) FROM hunt_challenge_attempts WHERE user_id=? AND challenge_type='QUESTION' AND attempted_at>DATE_SUB(NOW(),INTERVAL 10 MINUTE)");$rate->execute([$user['id']]);if((int)$rate->fetchColumn()>=15)throw new RuntimeException('Too many answer attempts.');$hash=answer_hash($answer);$ok=hash_equals($question['correct_answer_hash'],$hash);
    $pdo->prepare('INSERT INTO hunt_challenge_attempts(scan_id,user_id,challenge_type,challenge_id,successful,answer_hash,ip_address,device_id) VALUES(?,?,"QUESTION",?,?,?,?,?)')->execute([$scanId,$user['id'],$questionId,$ok?1:0,$hash,client_ip(),$user['device_id']]);if(!$ok)throw new RuntimeException('Answer is incorrect.');return true;
}
function create_scratch_reward(array $user,int $scanId): array
{
    $pdo=db();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT s.*,h.reward_min,h.reward_max FROM hunt_scans s JOIN hunts h ON h.id=s.hunt_id WHERE s.id=? AND s.user_id=? AND s.result="VALIDATED" FOR UPDATE');$s->execute([$scanId,$user['id']]);$scan=$s->fetch();if(!$scan)throw new RuntimeException('Validated scan not found or already completed.');
        $required=$pdo->prepare("SELECT (SELECT COUNT(*) FROM hunt_secret_codes WHERE hunt_id=? AND (location_id=? OR location_id IS NULL) AND status='ACTIVE' AND NOW() BETWEEN valid_from AND valid_until) secrets,(SELECT COUNT(*) FROM hunt_questions WHERE hunt_id=? AND (location_id=? OR location_id IS NULL) AND status='ACTIVE') questions");$required->execute([$scan['hunt_id'],$scan['location_id'],$scan['hunt_id'],$scan['location_id']]);$req=$required->fetch();
        foreach(['SECRET'=>(int)$req['secrets'],'QUESTION'=>(int)$req['questions']] as $type=>$count)if($count>0){$v=$pdo->prepare('SELECT COUNT(*) FROM hunt_challenge_attempts WHERE scan_id=? AND challenge_type=? AND successful=1');$v->execute([$scanId,$type]);if((int)$v->fetchColumn()<1)throw new RuntimeException(strtolower($type).' challenge must be completed first.');}
        $existing=$pdo->prepare('SELECT ss.session_id,r.reward_id,r.amount,r.status FROM hunt_scratch_sessions ss JOIN rewards r ON r.scratch_session_id=ss.id WHERE ss.scan_id=?');$existing->execute([$scanId]);if($row=$existing->fetch()){$pdo->commit();return $row+['created'=>false];}
        $sessionCode=transaction_code('SCR');$token=bin2hex(random_bytes(32));$pdo->prepare('INSERT INTO hunt_scratch_sessions(session_id,scan_id,user_id,token_hash,status,expires_at) VALUES(?,?,?, ?,"REVEALED",DATE_ADD(NOW(),INTERVAL 15 MINUTE))')->execute([$sessionCode,$scanId,$user['id'],hash('sha256',$token)]);$scratchId=(int)$pdo->lastInsertId();
        $min=decimal_to_cents((string)$scan['reward_min']);$max=decimal_to_cents((string)$scan['reward_max']);$amount=cents_to_decimal(random_int($min,$max));$rewardCode=transaction_code('RWD');$pdo->prepare('INSERT INTO rewards(reward_id,user_id,hunt_id,scan_id,scratch_session_id,amount,status) VALUES(?,?,?,?,?, ?,"PENDING")')->execute([$rewardCode,$user['id'],$scan['hunt_id'],$scanId,$scratchId,$amount]);$rewardId=(int)$pdo->lastInsertId();$pdo->prepare("UPDATE hunt_scans SET result='COMPLETED' WHERE id=?")->execute([$scanId]);$pdo->prepare("INSERT INTO hunt_completions(user_id,hunt_id,activation_id,reward_id) VALUES(?,?,?,?)")->execute([$user['id'],$scan['hunt_id'],$scan['activation_id'],$rewardId]);$pdo->prepare("UPDATE hunt_user_activations SET status='COMPLETED' WHERE id=?")->execute([$scan['activation_id']]);$pdo->commit();return ['session_id'=>$sessionCode,'reward_id'=>$rewardCode,'amount'=>$amount,'status'=>'PENDING','created'=>true];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function decide_reward(int $rewardId,string $action,?string $reason,int $adminId): array
{
    if(!in_array($action,['APPROVED','REJECTED'],true))throw new InvalidArgumentException('Invalid reward decision.');if($action==='REJECTED'&&trim((string)$reason)==='')throw new InvalidArgumentException('A rejection reason is required.');$pdo=db();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT * FROM rewards WHERE id=? FOR UPDATE');$s->execute([$rewardId]);$reward=$s->fetch();if(!$reward)throw new RuntimeException('Reward not found.');if($reward['status']!=='PENDING')throw new RuntimeException('Reward has already been decided.');$transactionId=null;
        if($action==='APPROVED'){$wallet=wallet_apply_in_transaction($pdo,(int)$reward['user_id'],'CREDIT',(string)$reward['amount'],$reward['reward_id'],'Approved Hunt reward',$adminId,'reward-credit:'.$rewardId);$transactionId=(int)$wallet['id'];$pdo->prepare("UPDATE rewards SET status='APPROVED',wallet_transaction_id=?,decided_at=NOW() WHERE id=?")->execute([$transactionId,$rewardId]);}
        else $pdo->prepare("UPDATE rewards SET status='REJECTED',rejection_reason=?,decided_at=NOW() WHERE id=?")->execute([trim((string)$reason),$rewardId]);
        $pdo->prepare('INSERT INTO reward_approvals(reward_id,admin_id,action,reason) VALUES(?,?,?,?)')->execute([$rewardId,$adminId,$action,$reason]);audit_log($adminId,'REWARD_'.$action,'rewards',(string)$rewardId,['status'=>'PENDING'],['status'=>$action,'amount'=>$reward['amount'],'wallet_transaction_id'=>$transactionId,'reason'=>$reason]);$pdo->commit();return ['status'=>$action,'wallet_transaction_id'=>$transactionId];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
