<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
if (PHP_SAPI !== 'cli') exit("CLI only\n");

$admin = (int) db()->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn();
if (!$admin) exit("Create an admin first: php database/create_admin.php ...\n");

$pdo = db();
$pdo->beginTransaction();
try {
    $vendorCode = 'VND-TEST-001';

    $existing = $pdo->prepare('SELECT id FROM vendors WHERE vendor_code=?');
    $existing->execute([$vendorCode]);
    $vendorId = (int) $existing->fetchColumn();

    if (!$vendorId) {
        $vendorCodeCol = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='vendors' AND column_name='vendor_code'");
        $hasVendorCode = (bool) $vendorCodeCol->fetchColumn();

        if ($hasVendorCode) {
            $pdo->prepare('INSERT INTO vendors(vendor_code,name,shop_name,email,mobile,address,business_category,kyc_status,status,commission_rate,commission_type,settlement_cycle,bank_verification_status,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
                $vendorCode, 'Demo Retail Counter', 'Demo Retail Counter',
                'vendor.test@hunterwallet.test', '9000000001',
                'Demo retail counter for testing', 'Retail',
                'APPROVED', 'ACTIVE', 5.00, 'PERCENTAGE', 'DAILY', 'VERIFIED', $admin
            ]);
        } else {
            $pdo->prepare('INSERT INTO vendors(vendor_code,name,shop_name,email,mobile,address,business_category,kyc_status,status,commission_rate,commission_type,settlement_cycle,created_by) VALUES(?,?,?,?,?,?,?,?,"ACTIVE",?,?,?,?,?)')->execute([
                $vendorCode, 'Demo Retail Counter', 'Demo Retail Counter',
                'vendor.test@hunterwallet.test', '9000000001',
                'Demo retail counter for testing', 'Retail',
                'APPROVED', 'ACTIVE', 5.00, 'PERCENTAGE', 'DAILY', $admin
            ]);
        }
        $vendorId = (int) $pdo->lastInsertId();
        audit_log(null, 'VENDOR_CREATED', 'vendors', $vendorCode, null, ['vendor_code' => $vendorCode, 'created_by' => $admin]);
    } else {
        $pdo->prepare('UPDATE vendors SET name=?,shop_name=?,email=?,mobile=?,business_category=?,kyc_status=?,status=?,commission_rate=?,commission_type=?,settlement_cycle=? WHERE id=?')->execute([
            'Demo Retail Counter', 'Demo Retail Counter',
            'vendor.test@hunterwallet.test', '9000000001',
            'Retail', 'APPROVED', 'ACTIVE', 5.00, 'PERCENTAGE', 'DAILY', $vendorId
        ]);
    }

    $existingUser = $pdo->prepare('SELECT id FROM vendor_users WHERE vendor_id=? AND (email=? OR username=?)');
    $existingUser->execute([$vendorId, 'vendor.test@hunterwallet.test', 'vendor.test']);
    $userId = (int) $existingUser->fetchColumn();

    $passwordHash = password_hash('Demo@12345', PASSWORD_DEFAULT);

    if (!$userId) {
        $pdo->prepare('INSERT INTO vendor_users(vendor_id,name,username,email,password_hash,status) VALUES(?,?,?,?,?,?)')->execute([
            $vendorId, 'Demo Vendor User', 'vendor.test',
            'vendor.test@hunterwallet.test', $passwordHash, 'ACTIVE'
        ]);
        $userId = (int) $pdo->lastInsertId();
    } else {
        $pdo->prepare('UPDATE vendor_users SET name=?,username=?,email=?,password_hash=?,status=? WHERE id=?')->execute([
            'Demo Vendor User', 'vendor.test',
            'vendor.test@hunterwallet.test', $passwordHash, 'ACTIVE', $userId
        ]);
    }

    $mfaCol = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='vendor_users' AND column_name='mfa_enabled'");
    $hasMfa = (bool) $mfaCol->fetchColumn();
    if ($hasMfa) {
        $pdo->prepare('UPDATE vendor_users SET mfa_enabled=0,mfa_secret_encrypted=NULL WHERE id=?')->execute([$userId]);
    }

    $pdo->commit();
    echo "Test vendor created successfully.\n";
    echo "  Vendor ID:   VND-TEST-001 (internal id {$vendorId})\n";
    echo "  Business:     Demo Retail Counter\n";
    echo "  Mobile:       9000000001\n";
    echo "  Email:        vendor.test@hunterwallet.test\n";
    echo "  Username:     vendor.test\n";
    echo "  Password:     Demo@12345\n";
    echo "  Status:       ACTIVE\n";
    echo "  Commission:   5%\n";
    echo "  MFA:          Disabled (password login)\n";
    echo "\nLogin at: http://127.0.0.1:8080/vendor/login.php\n";
    if ($hasMfa) {
        echo "\nTo enable TOTP MFA for this vendor:\n";
        echo "  php database/enable_vendor_mfa.php vendor.test\n";
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Failed: " . $e->getMessage() . "\n");
    exit(1);
}
