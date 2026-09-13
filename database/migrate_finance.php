<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
$config=require dirname(__DIR__).'/config/config.php';require APP_ROOT.'/config/database.php';
$pdo=db();
// Check required existing modules before executing any schema changes.
foreach(['admins','admin_permissions','admin_role_permissions','admin_roles','wallets','wallet_transactions','vendors','vendor_payout_requests','vendor_money_requests','merchant_settlements'] as $table)$pdo->query('SELECT 1 FROM '.$table.' LIMIT 0');
$pdo->query('SELECT escrow_balance,bank_account_number FROM vendors LIMIT 0');
$delimiter=';';$buffer='';
foreach(file(__DIR__.'/admin_wallet_migration.sql') as $line){
    if(preg_match('/^DELIMITER\s+(\S+)/i',trim($line),$m)){$delimiter=$m[1];continue;}
    $buffer.=$line;
    if(str_ends_with(trim($buffer),$delimiter)){$pdo->exec(substr(trim($buffer),0,-strlen($delimiter)));$buffer='';}
}
if(trim($buffer)!=='')$pdo->exec($buffer);
echo "Financial schema ready. Existing balances preserved; new master account starts at zero.\n";
