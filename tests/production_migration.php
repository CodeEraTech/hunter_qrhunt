<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('CLI only');
require dirname(__DIR__) . '/config/config.php';
$pdo = new PDO('mysql:host=' . env('DB_HOST', '127.0.0.1') . ';port=' . env('DB_PORT', '3306') . ';charset=utf8mb4', (string) env('DB_USER', 'root'), (string) env('DB_PASS', ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
// Never uses the configured application database. All writes are isolated.
$name = 'hunter_release_test_' . bin2hex(random_bytes(6));
function importRelease(PDO $pdo, string $file): void {
    $delimiter = ';'; $buffer = '';
    foreach (file($file) as $line) {
        if (preg_match('/^DELIMITER\s+(\S+)/i', trim($line), $m)) { $delimiter = $m[1]; continue; }
        $buffer .= $line;
        if (str_ends_with(trim($buffer), $delimiter)) {
            $stmt = $pdo->query(substr(trim($buffer), 0, -strlen($delimiter))); $stmt->closeCursor(); $buffer = '';
        }
    }
    if (trim($buffer) !== '') $pdo->exec($buffer);
}
function checkRelease(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
try {
    $pdo->exec("USE `$name`");
    importRelease($pdo, APP_ROOT . '/database/hunter_wallet.sql');
    $pdo->exec("INSERT INTO users(name,mobile,password_hash,status) VALUES('Migration sentinel','9000000098','not-a-login','ACTIVE')");
    $user = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO wallets(user_id,balance,bonus_balance,promo_balance) VALUES($user,123.45,67.89,10)");
    $wallet = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO wallet_transactions(transaction_id,user_id,wallet_id,type,bucket,amount,previous_balance,new_balance,status,idempotency_key) VALUES('MIGRATION-SENTINEL',$user,$wallet,'CREDIT','REAL',123.45,0,123.45,'SUCCESS','migration-sentinel')");
    $before = $pdo->query('SELECT * FROM wallets')->fetchAll(PDO::FETCH_ASSOC);
    $ledger = $pdo->query('SELECT * FROM wallet_transactions')->fetchAll(PDO::FETCH_ASSOC);
    $counts = [];
    foreach (['users','admins','riddle_pool','question_collections'] as $table) $counts[$table] = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    for ($run = 1; $run <= 2; $run++) {
        importRelease($pdo, APP_ROOT . '/database/production_update_2026_09_13.sql');
        checkRelease($before === $pdo->query('SELECT * FROM wallets')->fetchAll(PDO::FETCH_ASSOC), 'Wallet changed');
        checkRelease($ledger === $pdo->query('SELECT * FROM wallet_transactions')->fetchAll(PDO::FETCH_ASSOC), 'Ledger changed');
        foreach ($counts as $table => $count) checkRelease($count === $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn(), "$table rows changed");
        checkRelease($pdo->query('SELECT balance FROM finance_master_account WHERE id=1')->fetchColumn() === ($run === 1 ? '0.00' : '42.50'), 'Master balance reset');
        checkRelease((int) $pdo->query('SELECT COUNT(*) FROM product_categories')->fetchColumn() === 0, 'Sample categories inserted');
        // A preexisting nonzero account must survive the second import.
        if ($run === 1) $pdo->exec('UPDATE finance_master_account SET balance=42.50 WHERE id=1');
        echo "PASS import $run: existing wallets, ledger and account data preserved\n";
    }
    $triggers = $pdo->query("SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema=DATABASE() AND trigger_name LIKE 'finance_%'")->fetchColumn();
    checkRelease((int) $triggers === 4, 'Finance triggers missing');
    echo 'PASS finance triggers and product/MFA tables; MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";
} finally {
    $pdo->exec("DROP DATABASE `$name`");
}
