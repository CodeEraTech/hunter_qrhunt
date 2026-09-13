<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
// Creates and drops a uniquely named test database. Never imports into DB_NAME.
$config=require dirname(__DIR__).'/config/config.php';
$host=(string)env('DB_HOST','127.0.0.1');$port=(string)env('DB_PORT','3306');
$server=new PDO("mysql:host=$host;port=$port;charset=utf8mb4",(string)env('DB_USER','root'),(string)env('DB_PASS',''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$name='hunter_finance_test_'.bin2hex(random_bytes(6));
$server->exec('CREATE DATABASE `'.$name.'`');putenv('DB_NAME='.$name);
$config['key']=bin2hex(random_bytes(32));
require APP_ROOT.'/config/database.php';
foreach(['functions','security','audit','wallet','withdrawals','admin_wallet','finance_withdrawals','wallet_console'] as $file)require_once APP_ROOT.'/includes/'.$file.'.php';
$grants=['*'];function can(string $p): bool {global $grants;return in_array('*',$grants,true)||in_array($p,$grants,true);}function require_permission(string $p):void{if(!can($p))throw new RuntimeException('Forbidden');}
require APP_ROOT.'/includes/finance_directory.php';
$_SESSION=[];$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_USER_AGENT']='Finance isolated integration';
function expect(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function reject(callable $call,string $message):void{try{$call();}catch(Throwable $e){return;}throw new RuntimeException($message);}
function import_finance_sql(PDO $pdo,string $file):void{
 $delimiter=';';$buffer='';foreach(file($file) as $line){if(preg_match('/^DELIMITER\s+(\S+)/i',trim($line),$m)){$delimiter=$m[1];continue;} $buffer.=$line;if(str_ends_with(trim($buffer),$delimiter)){$pdo->exec(substr(trim($buffer),0,-strlen($delimiter)));$buffer='';}}
 if(trim($buffer)!=='')$pdo->exec($buffer);
}
function test_totp(string $secret):string{$counter=intdiv(time(),30);$hash=hash_hmac('sha1',pack('N2',0,$counter),base32_decode_secret($secret),true);$offset=ord($hash[19])&15;$number=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;return str_pad((string)($number%1000000),6,'0',STR_PAD_LEFT);}
try{
 $pdo=db();import_finance_sql($pdo,APP_ROOT.'/database/hunter_wallet.sql');import_finance_sql($pdo,APP_ROOT.'/database/production_update.sql');import_finance_sql($pdo,APP_ROOT.'/database/admin_wallet_migration.sql');import_finance_sql($pdo,APP_ROOT.'/database/admin_wallet_migration.sql');
 $secret='JBSWY3DPEHPK3PXP';$s=$pdo->prepare('INSERT INTO admins(role_id,name,username,email,password_hash,status,mfa_enabled,mfa_secret_encrypted) VALUES(1,"Finance Test","finance-test","finance@example.test",?,"ACTIVE",1,?)');$s->execute([password_hash('TestPassword123!',PASSWORD_DEFAULT),encrypt_secret($secret)]);$admin=(int)$pdo->lastInsertId();
 $s=$pdo->prepare('INSERT INTO vendors(vendor_code,name,shop_name,status,escrow_balance,created_by) VALUES("TEST-VENDOR","Test Vendor","Test Shop","ACTIVE",0,?)');$s->execute([$admin]);$vendor=(int)$pdo->lastInsertId();
 $s=$pdo->prepare('INSERT INTO users(name,mobile,email,password_hash,status) VALUES("Finance User","9000000071","finance-user@example.test",?,"ACTIVE")');$s->execute([password_hash('TestPassword123!',PASSWORD_DEFAULT)]);$user=(int)$pdo->lastInsertId();$pdo->prepare('INSERT INTO wallets(user_id,balance) VALUES(?,0)')->execute([$user]);
 finance_set_pin($admin,'TestPassword123!','123456',test_totp($secret));
 $transfer=function(string $op,string $target,int $recipient,string $amount,string $ref='')use($pdo,$admin,$secret):string{
   $pdo->exec('UPDATE finance_admin_security SET last_otp_hash=NULL,last_otp_at=NULL');
   $p=finance_payload(['operation'=>$op,'target'=>$target,'recipient_id'=>$recipient,'amount'=>$amount,'reference'=>$ref,'reason'=>'Integration transfer']);
   finance_verify_pin($admin,'123456');$key=idempotency_key('master');$_SESSION['finance_pending']=['admin_id'=>$admin,'payload'=>$p,'key'=>$key,'expires'=>time()+300];
   return finance_transfer($admin,$p,$key,test_totp($secret));
 };
 reject(fn()=>finance_verify_pin($admin,'000000'),'Wrong PIN accepted');
 reject(fn()=>$transfer('DISTRIBUTE','VENDOR',$vendor,'1.00'),'Unfunded master accepted');
 $transfer('FUND','MASTER',0,'1000.00','BANK-TEST-1');
 $transfer('DISTRIBUTE','VENDOR',$vendor,'200.00');
 $transfer('DISTRIBUTE','REAL',$user,'150.00');
 $transfer('DISTRIBUTE','BONUS',$user,'50.00');
 expect($pdo->query('SELECT balance FROM finance_master_account')->fetchColumn()==='600.00','Master debit incorrect');
 expect($pdo->query('SELECT balance FROM wallets')->fetchColumn()==='150.00','User credit absent');
 expect($pdo->query('SELECT bonus_balance FROM wallets')->fetchColumn()==='50.00','Bonus credit absent');
 $pending=$_SESSION['finance_pending'];$count=$pdo->query('SELECT COUNT(*) FROM finance_master_entries')->fetchColumn();finance_transfer($admin,$pending['payload'],$pending['key'],test_totp($secret));expect($pdo->query('SELECT COUNT(*) FROM finance_master_entries')->fetchColumn()===$count,'Duplicate transfer posted');
 $tampered=$pending['payload'];$tampered['amount']='99.00';reject(fn()=>finance_transfer($admin,$tampered,$pending['key'],test_totp($secret)),'Tampered transfer accepted');
 reject(fn()=>$transfer('DISTRIBUTE','REAL',$user,'9999.00'),'Overspend accepted');
 reject(fn()=>$transfer('RECALL','BONUS',$user,'999.00'),'Overdrawn recipient accepted');
 reject(fn()=>$transfer('FUND','MASTER',0,'10.00','BANK-TEST-1'),'Duplicate bank funding accepted');
 $transfer('RECALL','REAL',$user,'25.00');expect($pdo->query('SELECT balance FROM finance_master_account')->fetchColumn()==='625.00','Recall incorrect');
 reject(fn()=>$pdo->exec("UPDATE finance_master_entries SET amount=1 WHERE id=1"),'Immutable master update accepted');
 reject(fn()=>$pdo->exec('DELETE FROM finance_master_entries WHERE id=1'),'Immutable master delete accepted');
 $s=$pdo->prepare('INSERT INTO vendor_payout_requests(vendor_id,amount,status) VALUES(?,50,"PENDING")');$s->execute([$vendor]);$request=(int)$pdo->lastInsertId();
 finance_vendor_withdrawal($request,'APPROVED','Test approve','',$admin);expect($pdo->query('SELECT escrow_balance FROM vendors')->fetchColumn()==='150.00','Vendor debit absent');
 reject(fn()=>finance_vendor_withdrawal($request,'APPROVED','Duplicate','',$admin),'Duplicate payout debit accepted');
 finance_vendor_withdrawal($request,'PROCESSING','Test processing','',$admin);
 reject(fn()=>finance_vendor_withdrawal($request,'PAID','Test paid','',$admin),'Missing UTR accepted');
 finance_vendor_withdrawal($request,'PAID','Test paid','BANK-PAYOUT-1',$admin);
 expect($pdo->query('SELECT COUNT(*) FROM finance_payout_records')->fetchColumn()==1,'Payout reference absent');
 $sql=finance_transactions_sql();$rows=$pdo->query('SELECT * FROM ('.$sql.') d')->fetchAll();expect(count($rows)>=8,'Combined transaction directory failed');
 $sql=finance_withdrawals_sql('withdrawals');[$where,$params]=finance_filters(['q'=>'TEST-VENDOR','status'=>'PAID'],['status'=>['PAID']]);$s=$pdo->prepare('SELECT * FROM ('.$sql.') d'.$where);$s->execute($params);expect(count($s->fetchAll())===1,'Combined search/status binding failed');
 $grants=['wallet.view'];$rows=$pdo->query('SELECT * FROM ('.finance_transactions_sql().') d')->fetchAll();expect(count(array_filter($rows,fn($r)=>$r['source']!=='WALLET'))===0,'Restricted role received vendor/master data');
 reject(fn()=>$transfer('FUND','MASTER',0,'10.00','BANK-TEST-2'),'Unauthorized master funding accepted');
 $grants=['*'];
 expect(decimal_to_cents('-0.50')===-50 && decimal_to_cents('-1.25')===-125,'Signed decimal arithmetic failed');
 $before=$pdo->query('SELECT balance FROM finance_master_account')->fetchColumn();
 reject(fn()=>$transfer('DISTRIBUTE','REAL',999999,'1.00'),'Missing recipient accepted');
 expect($pdo->query('SELECT balance FROM finance_master_account')->fetchColumn()===$before,'Failed transfer changed master balance');
 $p=finance_payload(['operation'=>'RECALL','target'=>'REAL','recipient_id'=>$user,'amount'=>'1.00','reason'=>'OTP checks','reference'=>'']);
 finance_verify_pin($admin,'123456');$key=idempotency_key('master');$_SESSION['finance_pending']=['admin_id'=>$admin,'payload'=>$p,'key'=>$key,'expires'=>time()+300];
 $pdo->exec('UPDATE finance_admin_security SET last_otp_hash=NULL,last_otp_at=NULL');
 reject(fn()=>finance_transfer($admin,$p,$key,'not-otp'),'Invalid OTP accepted');
 expect((int)$pdo->query('SELECT failures FROM finance_admin_security')->fetchColumn()===1,'OTP failure was not persisted');
 finance_transfer($admin,$p,$key,test_totp($secret));
 $key=idempotency_key('master');$_SESSION['finance_pending']['key']=$key;
 reject(fn()=>finance_transfer($admin,$p,$key,test_totp($secret)),'Reused OTP accepted');
 $pdo->exec('UPDATE finance_admin_security SET failures=0,locked_until=NULL');
 for($i=0;$i<5;$i++) reject(fn()=>finance_verify_pin($admin,'000000'),'Wrong PIN accepted');
 reject(fn()=>finance_verify_pin($admin,'123456'),'Locked PIN account accepted');
 // Reference saving must be transactional with the customer payout state.
 $pdo->prepare('INSERT INTO withdrawals(withdrawal_id,user_id,wallet_id,amount,payment_method,payment_account_masked,status) VALUES("WD-TEST",?,1,10,"BANK","XXXX 9012","PROCESSING")')->execute([$user]);
 $customerRequest=(int)$pdo->lastInsertId();
 reject(fn()=>transition_withdrawal($customerRequest,'PAID','Paid',$admin,''),'Customer Paid without UTR accepted');
 expect($pdo->query('SELECT status FROM withdrawals WHERE id='.$customerRequest)->fetchColumn()==='PROCESSING','Failed customer payout changed status');
 transition_withdrawal($customerRequest,'PAID','Paid',$admin,'BANK-CUSTOMER-1');
 expect($pdo->query("SELECT reference FROM finance_payout_records WHERE kind='CUSTOMER'")->fetchColumn()==='BANK-CUSTOMER-1','Customer reference absent');
 // Customer console: mandatory reason, separate buckets, previews and safe reversals.
 $realBefore=$pdo->query('SELECT balance FROM wallets')->fetchColumn();
 $bonusBefore=$pdo->query('SELECT bonus_balance FROM wallets')->fetchColumn();
 reject(fn()=>wallet_console_adjust($user,'REAL','CREDIT','5.00','','',idempotency_key('console'),$admin),'Blank adjustment reason accepted');
 $consoleKey=idempotency_key('console');$r=wallet_console_adjust($user,'BONUS','DEBIT','5.00','Bonus correction','',$consoleKey,$admin,$bonusBefore);
 expect($pdo->query('SELECT balance FROM wallets')->fetchColumn()===$realBefore,'Bonus adjustment changed real cash');
 expect($pdo->query('SELECT bonus_balance FROM wallets')->fetchColumn()===decimal_subtract($bonusBefore,'5.00'),'Bonus debit failed');
 $again=wallet_console_adjust($user,'BONUS','DEBIT','5.00','Bonus correction','',$consoleKey,$admin,$bonusBefore);expect(!$again['created'],'Duplicate console debit applied');
 reject(fn()=>wallet_console_adjust($user,'BONUS','DEBIT','5.00','Bonus correction','',idempotency_key('console'),$admin,$bonusBefore),'Stale preview accepted');
 wallet_reverse((int)$r['id'],'Undo bonus correction',$admin);expect($pdo->query('SELECT bonus_balance FROM wallets')->fetchColumn()===$bonusBefore,'Reversal used the wrong bucket');
 expect($pdo->query('SELECT balance FROM wallets')->fetchColumn()===$realBefore,'Bonus reversal changed real cash');
 reject(fn()=>wallet_reverse((int)$r['id'],'Duplicate reversal',$admin),'Duplicate reversal accepted');
 expect(wallet_direction(['type'=>'ADJUSTMENT','previous_balance'=>'10.00','new_balance'=>'3.00'])==='DEBIT','Checkout adjustment misclassified');
 expect((int)wallet_console_customer('USR_'.$user)['id']===$user && (int)wallet_console_customer('9000000071')['id']===$user,'Customer ID/mobile lookup failed');
 $grants=['wallet.credit'];reject(fn()=>wallet_console_adjust($user,'BONUS','CREDIT','1.00','Forbidden bonus','',idempotency_key('console'),$admin),'Bonus permission bypass');$grants=['*'];
 reject(function()use($pdo,$user){$pdo->beginTransaction();try{wallet_bucket_apply_in_transaction($pdo,$user,'BONUS','1.00','x','x',null,'console-cross-user-test','INVALID');}finally{if($pdo->inTransaction())$pdo->rollBack();}},'Invalid bucket operation accepted');
 $pdo->prepare('INSERT INTO users(name,mobile,password_hash,status) VALUES("Other Wallet","9000000082",?,"ACTIVE")')->execute([password_hash('TestPassword123!',PASSWORD_DEFAULT)]);$other=(int)$pdo->lastInsertId();$pdo->prepare('INSERT INTO wallets(user_id,balance) VALUES(?,10)')->execute([$other]);
 reject(function()use($pdo,$other,$consoleKey,$admin){$pdo->beginTransaction();try{wallet_bucket_apply_in_transaction($pdo,$other,'BONUS','5.00','x','x',$admin,$consoleKey,'DEBIT');}finally{if($pdo->inTransaction())$pdo->rollBack();}},'Cross-user idempotency reuse accepted');
 reject(fn()=>$pdo->exec('UPDATE audit_logs SET action="ALTERED" WHERE id=1'),'Audit log mutation accepted');
 // Two independent connections compete for the same available funds.
 $pdo->prepare('INSERT INTO users(name,mobile,password_hash,status) VALUES("Concurrent User","9000000081",?,"ACTIVE")')->execute([password_hash('TestPassword123!',PASSWORD_DEFAULT)]);$concurrent=(int)$pdo->lastInsertId();$pdo->prepare('INSERT INTO wallets(user_id,balance) VALUES(?,10)')->execute([$concurrent]);
 $workers=[];$workerEnv=getenv();$workerEnv['DB_NAME']=$name;
 for($i=0;$i<2;$i++){$proc=proc_open([PHP_BINARY,APP_ROOT.'/tests/wallet_concurrency_worker.php',(string)$concurrent,idempotency_key('console'),(string)$admin],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,APP_ROOT,$workerEnv);$workers[]=[$proc,$pipes];}
 $results=[];foreach($workers as [$proc,$pipes]){$results[]=stream_get_contents($pipes[1]);fclose($pipes[1]);fclose($pipes[2]);proc_close($proc);}
 sort($results);expect($results===['APPLIED','REJECTED'],'Concurrent debit guard failed');
 expect($pdo->query('SELECT balance FROM wallets WHERE user_id='.$concurrent)->fetchColumn()==='3.00','Concurrent balance incorrect');
 // Render the actual admin routes through the router and authenticated MFA login.
 $port=random_int(21000,29000);$base='http://127.0.0.1:'.$port;
 $serverLog=tempnam(sys_get_temp_dir(),'finance-http-');$cookie=tempnam(sys_get_temp_dir(),'finance-cookie-');
 $env=getenv();$env['DB_NAME']=$name;$env['APP_KEY']=$config['key'];$env['APP_URL']=$base;$env['COOKIE_SECURE']='0';$env['APP_ENV']='production';
 $process=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'router.php'],[0=>['file','/dev/null','r'],1=>['file',$serverLog,'a'],2=>['file',$serverLog,'a']],$pipes,APP_ROOT,$env);
 expect(is_resource($process),'HTTP test server failed');
 try {
  usleep(350000);
  $httpRequest=function(string $path,?array $body=null)use($base,$cookie):array {
   $h=curl_init($base.$path);curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_TIMEOUT=>10]);
   if($body!==null)curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($body)]);
   $html=curl_exec($h);$code=curl_getinfo($h,CURLINFO_RESPONSE_CODE);unset($h);return [$code,$html];
  };
  [$code]=$httpRequest('/admin/wallets/admin.php');expect($code===302,'Unauthenticated master page accessible');
  [$code,$html]=$httpRequest('/admin/login.php');expect($code===200 && preg_match('/name="csrf_token" value="([^"]+)"/',$html,$m)===1,'Login page failed');$csrf=$m[1];
  [$code]=$httpRequest('/admin/login.php',['csrf_token'=>$csrf,'identity'=>'finance-test','password'=>'TestPassword123!']);expect($code===302,'Password login failed');
  [$code,$html]=$httpRequest('/admin/mfa.php');expect($code===200 && preg_match('/name="csrf_token" value="([^"]+)"/',$html,$m)===1,'MFA form failed');
  [$code]=$httpRequest('/admin/mfa.php',['csrf_token'=>$m[1],'code'=>test_totp($secret)]);expect($code===302,'MFA login failed');
  foreach(['/admin/wallets/admin.php','/admin/wallets/index.php','/admin/wallets/adjust.php?customer=USR_'.$user,'/admin/wallets/customer.php?id='.$user,'/admin/wallets/detail.php?source=MASTER&id=1','/admin/withdrawals/index.php','/admin/withdrawals/index.php?tab=money','/admin/withdrawals/index.php?tab=settlements','/admin/withdrawals/view.php?id='.$customerRequest,'/admin/withdrawals/vendor.php?source=VENDOR&id='.$request] as $path) {
    [$code,$html]=$httpRequest($path);expect($code===200 && !str_contains($html,'Something went wrong'),'Page render failed: '.$path.' HTTP '.$code);
  }
  [$code,$html]=$httpRequest('/admin/wallets/adjust.php?customer=USR_'.$user);preg_match('/name="csrf_token" value="([^"]+)"/',$html,$m);$csrf=$m[1];
  $beforeHttp=$pdo->query('SELECT bonus_balance FROM wallets WHERE user_id='.$user)->fetchColumn();
  [$code]=$httpRequest('/admin/wallets/adjust.php',['csrf_token'=>$csrf,'action'=>'preview','customer'=>'USR_'.$user,'bucket'=>'BONUS','type'=>'CREDIT','amount'=>'2.00','reason'=>'HTTP adjustment test','reference'=>'']);expect($code===302,'Adjustment preview failed');
  [$code,$html]=$httpRequest('/admin/wallets/adjust.php?customer=USR_'.$user);expect(str_contains($html,'Confirm adjustment'),'Preview not displayed');
  [$code]=$httpRequest('/admin/wallets/adjust.php',['csrf_token'=>$csrf,'action'=>'confirm','user_id'=>$other,'amount'=>'999.00']);expect($code===302,'Adjustment confirm failed');
  expect($pdo->query('SELECT bonus_balance FROM wallets WHERE user_id='.$user)->fetchColumn()===decimal_add($beforeHttp,'2.00'),'Server confirmation did not use exact preview');
  expect($pdo->query('SELECT bonus_balance FROM wallets WHERE user_id='.$other)->fetchColumn()==='0.00','Tampered recipient accepted by confirmation');
  foreach(['/admin/wallets/index.php?export=xlsx','/admin/wallets/customer.php?id='.$user.'&export=xlsx','/admin/withdrawals/index.php?export=xlsx'] as $path){
   [$code,$bytes]=$httpRequest($path);expect($code===200 && str_starts_with($bytes,'PK'),'XLSX download failed');
   $file=tempnam(sys_get_temp_dir(),'finance-xlsx-');file_put_contents($file,$bytes);$zip=new ZipArchive();expect($zip->open($file)===true,'Invalid XLSX archive');$xml=$zip->getFromName('xl/worksheets/sheet1.xml');expect($xml!==false && simplexml_load_string($xml)!==false,'Invalid worksheet XML');expect(!str_contains($xml,'<f>'),'Export contains formula cells');$zip->close();unlink($file);
  }
  [$code,$html]=$httpRequest('/admin/wallets/customer.php?id='.$user.'&pocket=BONUS&export=csv');expect($code===200 && str_contains($html,'Bonus correction'),'Passbook CSV failed');
  [$code,$html]=$httpRequest('/admin/withdrawals/index.php?q=TEST-VENDOR&status=PAID&export=1');expect($code===200 && str_contains($html,'BANK-PAYOUT-1'),'Filtered export failed');
 }finally{proc_terminate($process);proc_close($process);unlink($serverLog);unlink($cookie);}
 echo "PASS: repeatable migrations, PIN, master/recipient conservation, idempotency, tampering, insufficient funds, immutable ledger, vendor debit, UTR, console previews, concurrent debits, bucket reversals, immutable audits, authenticated routes and CSV/XLSX exports.\n";
}finally{$server->exec('DROP DATABASE `'.$name.'`');}
