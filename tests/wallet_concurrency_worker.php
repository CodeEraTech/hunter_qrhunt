<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli' || !preg_match('/^hunter_finance_test_[a-f0-9]{12}$/',getenv('DB_NAME')?:''))exit(2);
$config=require dirname(__DIR__).'/config/config.php';require APP_ROOT.'/config/database.php';
foreach(['functions','audit','wallet','wallet_console'] as $file)require APP_ROOT.'/includes/'.$file.'.php';
function require_permission(string $permission):void {}
try{wallet_console_adjust((int)$argv[1],'REAL','DEBIT','7.00','Concurrent debit test','',$argv[2],(int)$argv[3]);echo 'APPLIED';}
catch(Throwable $e){echo 'REJECTED';}
