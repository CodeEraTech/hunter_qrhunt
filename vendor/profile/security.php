<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/includes/bootstrap.php';
require_vendor();
$vendor = vendor_user();

$error = null;
$success = null;
$mfaStep = 'status';
$mfaSecretPlain = '';
$mfaError = null;

if (is_post()) {
    verify_csrf();
    if (input('action') === 'change_password') {
        $current = (string) input('current_password');
        $new = (string) input('new_password');
        $confirm = (string) input('confirm_password');
        if (!password_verify($current, $vendor['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            db()->prepare('UPDATE vendor_users SET password_hash=?,updated_at=NOW() WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $vendor['id']]);
            audit_log(null, 'VENDOR_PASSWORD_CHANGE', 'vendor_security', (string) $vendor['id']);
            flash('success', 'Password changed successfully.');
            redirect('/vendor/profile/security.php');
        }
    } elseif (input('action') === 'enable_mfa_start') {
        $raw = random_bytes(20);
        $mfaSecretPlain = rtrim(strtr(base64_encode($raw), '+/', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'), '=');
        $mfaStep = 'verify';
    } elseif (input('action') === 'enable_mfa_verify') {
        $code = trim((string) input('mfa_code'));
        $secret = trim((string) input('mfa_secret'));
        if (!preg_match('/^\d{6}$/', $code) || !verify_totp($secret, $code)) {
            $mfaError = 'Invalid or expired verification code.';
            $mfaSecretPlain = $secret;
            $mfaStep = 'verify';
        } else {
            $encrypted = encrypt_secret($secret);
            db()->prepare('UPDATE vendor_users SET mfa_enabled=1,mfa_secret_encrypted=?,updated_at=NOW() WHERE id=?')->execute([$encrypted, $vendor['id']]);
            audit_log(null, 'VENDOR_MFA_ENABLED', 'vendor_security', (string) $vendor['id']);
            flash('success', 'Two-factor authentication enabled.');
            redirect('/vendor/profile/security.php');
        }
    } elseif (input('action') === 'disable_mfa') {
        db()->prepare('UPDATE vendor_users SET mfa_enabled=0,mfa_secret_encrypted=NULL,updated_at=NOW() WHERE id=?')->execute([$vendor['id']]);
        audit_log(null, 'VENDOR_MFA_DISABLED', 'vendor_security', (string) $vendor['id']);
        flash('success', 'Two-factor authentication disabled.');
        redirect('/vendor/profile/security.php');
    }
}

$pageTitle = 'Login & Security';
$pageSubtitle = 'Password, MFA and session security';
require APP_ROOT.'/includes/vendor_header.php';

$notices = take_flashes();
foreach ($notices as $n): ?>
    <div class="alert <?=e($n['type'])?>"><?=e($n['message'])?></div>
<?php endforeach ?>
<?php if ($error): ?>
    <div class="alert error"><?=e($error)?></div>
<?php endif ?>

<section class="store-panel" aria-labelledby="security-heading">
    <h2 id="security-heading">Security Overview</h2>
    <div class="form-grid" style="margin-top:16px">
        <div class="field">
            <label>Multi-Factor Authentication</label>
            <span class="store-status status-<?=e((int)$vendor['mfa_enabled'] === 1 ? 'approved' : 'pending')?>">
                <?=e((int)$vendor['mfa_enabled'] === 1 ? 'Enabled' : 'Disabled')?>
            </span>
        </div>
        <div class="field">
            <label>Last Login</label>
            <strong><?=e($vendor['last_login_at'] ?? 'Never')?></strong>
        </div>
        <div class="field">
            <label>Last Login IP</label>
            <strong><?=e($vendor['last_login_ip'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Vendor User ID</label>
            <strong><?=e($vendor['id'])?></strong>
        </div>
    </div>
</section>

<section class="store-panel" aria-labelledby="pwd-heading">
    <div class="panel-head">
        <h2 id="pwd-heading">Change Password</h2>
    </div>
    <form method="post" class="form-grid">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="change_password">
        <div class="field">
            <label for="current_password">Current Password</label>
            <input id="current_password" name="current_password" type="password" required autofocus>
        </div>
        <div class="field">
            <label for="new_password">New Password</label>
            <input id="new_password" name="new_password" type="password" minlength="8" required>
        </div>
        <div class="field">
            <label for="confirm_password">Confirm New Password</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>
        </div>
        <div class="field full">
            <button class="btn" type="submit">Update Password</button>
        </div>
    </form>
</section>

<section class="store-panel" aria-labelledby="mfa-heading">
    <div class="panel-head">
        <h2 id="mfa-heading">Two-Factor Authentication</h2>
    </div>
    <?php if ((int)$vendor['mfa_enabled'] === 1): ?>
        <p class="help" style="margin-bottom:14px">Authenticator app is active. You can disable it below.</p>
        <form method="post" class="stack" style="max-width:400px">
            <?=csrf_field()?>
            <input type="hidden" name="action" value="disable_mfa">
            <button class="btn danger" type="submit" onclick="return confirm('Disable two-factor authentication?')">Disable MFA</button>
        </form>
    <?php else: ?>
        <?php if ($mfaStep === 'status'): ?>
            <p class="help" style="margin-bottom:14px">Add an extra layer of security by enabling TOTP-based authentication.</p>
            <form method="post" class="stack" style="max-width:400px">
                <?=csrf_field()?>
                <input type="hidden" name="action" value="enable_mfa_start">
                <button class="btn" type="submit">Enable MFA</button>
            </form>
        <?php else: ?>
            <p class="help" style="margin-bottom:14px">Scan the QR code or enter this secret into your authenticator app, then enter the 6-digit code to verify.</p>
            <?php if ($mfaError): ?>
                <div class="alert error"><?=e($mfaError)?></div>
            <?php endif ?>
            <div class="one-time" style="padding:14px;border-radius:10px;margin-bottom:14px;word-break:break-all;font-family:ui-monospace,monospace">
                <?=e($mfaSecretPlain)?>
            </div>
            <p style="margin:0 0 10px;font-size:12px;color:var(--muted)">
                Or scan: <code>otpauth://totp/HunterWallet:<?=e(urlencode($vendor['email'] ?? 'vendor'))?>?secret=<?=e($mfaSecretPlain)?>&issuer=HunterWallet</code>
            </p>
            <form method="post" class="form-grid">
                <?=csrf_field()?>
                <input type="hidden" name="action" value="enable_mfa_verify">
                <input type="hidden" name="mfa_secret" value="<?=e($mfaSecretPlain)?>">
                <div class="field">
                    <label for="mfa_code">Verification Code</label>
                    <input id="mfa_code" name="mfa_code" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required autofocus>
                </div>
                <div class="field">
                    <button class="btn" type="submit">Verify & Enable</button>
                </div>
            </form>
        <?php endif ?>
    <?php endif ?>
</section>

<section class="store-panel" aria-labelledby="events-heading">
    <div class="panel-head">
        <h2 id="events-heading">Recent Security Events</h2>
    </div>
    <?php
    $stmt = db()->prepare('SELECT action,module,record_id,ip_address,created_at FROM audit_logs WHERE record_id=? ORDER BY created_at DESC LIMIT 20');
    $stmt->execute([(string)$vendor['id']]);
    $events = $stmt->fetchAll();
    if ($events): ?>
        <div class="table-wrap">
            <table class="store-table">
                <thead><tr><th>Event</th><th>Module</th><th>Record ID</th><th>IP Address</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td><strong><?=e($ev['action'])?></strong></td>
                            <td><?=e($ev['module'])?></td>
                            <td><?=e($ev['record_id'] ?? '—')?></td>
                            <td><?=e($ev['ip_address'] ?? '—')?></td>
                            <td><?=e($ev['created_at'])?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No security events recorded yet.</p>
    <?php endif ?>
</section>

<?php
require APP_ROOT.'/includes/vendor_footer.php';
