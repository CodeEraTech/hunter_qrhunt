<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
if (PHP_SAPI !== 'cli') exit("CLI only\n");

$identity = $argv[1] ?? '';
if ($identity === '') exit("Usage: php database/enable_vendor_mfa.php username-or-email\n");

$mfaCol = db()->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='vendor_users' AND column_name='mfa_enabled'");
if (!$mfaCol->fetchColumn()) exit("MFA columns not found. Run database/vendor_mfa_migration.sql first.\n");

$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
$secret = '';
for ($i = 0; $i < 32; $i++) $secret .= $alphabet[random_int(0, 31)];

$stmt = db()->prepare("UPDATE vendor_users vu JOIN vendors v ON v.id=vu.vendor_id SET vu.mfa_enabled=1, vu.mfa_secret_encrypted=? WHERE vu.username=? OR vu.email=? OR v.mobile=?");
$stmt->execute([encrypt_secret($secret), $identity, $identity, $identity]);

if ($stmt->rowCount() === 0) exit("Vendor user not found.\n");

echo "TOTP MFA enabled for vendor user '{$identity}'.\n";
echo "Add this secret to the vendor's authenticator app, then securely discard it:\n";
echo "{$secret}\n";
echo "\nThe vendor will be prompted for a 6-digit TOTP code on next login.\n";
