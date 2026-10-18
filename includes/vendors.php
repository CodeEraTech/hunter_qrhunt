<?php
declare(strict_types=1);

function vendor_user(): ?array
{
    static $cached=false;
    if($cached!==false)return $cached;
    if(empty($_SESSION['vendor_user_id'])||empty($_SESSION['vendor_session_token']))return $cached=null;
    try {
        $hash=hash('sha256',$_SESSION['vendor_session_token']);
        $s=db()->prepare("SELECT vu.*,v.name vendor_name,v.shop_name,v.vendor_code,v.status vendor_status FROM vendor_sessions vs JOIN vendor_users vu ON vu.id=vs.vendor_user_id JOIN vendors v ON v.id=vu.vendor_id WHERE vs.vendor_user_id=? AND vs.php_session_hash=? AND vs.revoked_at IS NULL AND vs.expires_at>NOW() AND vu.status='ACTIVE' AND v.status='ACTIVE'");
        $s->execute([$_SESSION['vendor_user_id'],$hash]);
        return $cached=$s->fetch()?:null;
    } catch (Throwable $e) {
        error_log('vendor_user() lookup failed: ' . $e->getMessage());
        return $cached=null;
    }
}

function require_vendor(): void
{
    if(!vendor_user()){flash('error','Please sign in as a vendor.');redirect('/vendor/login.php');}
}

/**
 * Begin a persistent vendor session after credentials (and optional MFA) pass.
 * The session token is stored only as a SHA-256 hash inside vendor_sessions.
 */
function begin_vendor_session(array $row): void
{
    session_regenerate_id(true);
    unset($_SESSION['pending_mfa_vendor_user_id'], $_SESSION['pending_mfa_vendor_expires']);
    $_SESSION['vendor_user_id'] = (int) $row['id'];
    $_SESSION['vendor_session_token'] = bin2hex(random_bytes(32));
    $_SESSION['last_activity'] = time();
    db()->prepare('INSERT INTO vendor_sessions(vendor_user_id,php_session_hash,ip_address,expires_at) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL 8 HOUR))')->execute([$row['id'], hash('sha256', $_SESSION['vendor_session_token']), client_ip()]);
    db()->prepare('UPDATE vendor_users SET last_login_at=NOW(),last_login_ip=? WHERE id=?')->execute([client_ip(), $row['id']]);
    audit_log(null, 'VENDOR_LOGIN', 'vendor_auth', (string) $row['id']);
}

/**
 * Attempt vendor login by mobile number or email address + password.
 *
 * Returns one of:
 *   'AUTHENTICATED'  – credentials valid, session created (MFA not enabled)
 *   'MFA_REQUIRED'   – credentials valid, TOTP MFA entry required
 *   'FAILED'         – invalid credentials, disabled account, or rate-limited
 */
function attempt_vendor_login(string $identity, string $password): string
{
    $hash = hash('sha256', strtolower($identity));
    $r = db()->prepare('SELECT COUNT(*) FROM vendor_login_attempts WHERE (identity_hash=? OR ip_address=?) AND successful=0 AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
    $r->execute([$hash, client_ip()]);
    if ((int) $r->fetchColumn() >= 5) return 'FAILED';

    $s = db()->prepare("SELECT vu.*,v.name vendor_name,v.shop_name,v.vendor_code,v.status vendor_status,v.mobile,v.email vendor_email FROM vendor_users vu JOIN vendors v ON v.id=vu.vendor_id WHERE (vu.username=? OR vu.email=? OR v.mobile=?) LIMIT 1");
    $s->execute([$identity, $identity, $identity]);
    $u = $s->fetch();

    $ok = $u && $u['status'] === 'ACTIVE' && $u['vendor_status'] === 'ACTIVE'
        && password_verify($password, $u['password_hash']);

    db()->prepare('INSERT INTO vendor_login_attempts(identity_hash,ip_address,successful) VALUES(?,?,?)')->execute([$hash, client_ip(), $ok ? 1 : 0]);

    if (!$ok) {
        log_security(null, 'VENDOR_LOGIN_FAILED', 'MEDIUM', 'Failed vendor login', ['identity_hash' => $hash]);
        return 'FAILED';
    }

    if ((int) ($u['mfa_enabled'] ?? 0) === 1) {
        if (empty($u['mfa_secret_encrypted'])) {
            log_security(null, 'VENDOR_MFA_CONFIGURATION_ERROR', 'HIGH', 'MFA-enabled vendor has no configured secret', ['vendor_user_id' => $u['id']]);
            return 'FAILED';
        }
        session_regenerate_id(true);
        $_SESSION['pending_mfa_vendor_user_id'] = (int) $u['id'];
        $_SESSION['pending_mfa_vendor_expires'] = time() + 300;
        db()->prepare('INSERT INTO vendor_login_attempts(identity_hash,ip_address,successful) VALUES(?,?,?)')->execute([hash('sha256', 'vendor-mfa-pending:' . $u['id']), client_ip(), 0]);
        return 'MFA_REQUIRED';
    }

    begin_vendor_session($u);
    return 'AUTHENTICATED';
}

/**
 * Complete TOTP MFA for a vendor login that returned 'MFA_REQUIRED'.
 */
function complete_vendor_mfa(string $code): bool
{
    $id = (int) ($_SESSION['pending_mfa_vendor_user_id'] ?? 0);
    $expires = (int) ($_SESSION['pending_mfa_vendor_expires'] ?? 0);
    if (!$id || time() > $expires) {
        unset($_SESSION['pending_mfa_vendor_user_id'], $_SESSION['pending_mfa_vendor_expires']);
        return false;
    }

    $mfaHash = hash('sha256', 'vendor-mfa:' . $id);
    $attempts = db()->prepare('SELECT COUNT(*) FROM vendor_login_attempts WHERE identity_hash=? AND successful=0 AND attempted_at>DATE_SUB(NOW(),INTERVAL 5 MINUTE)');
    $attempts->execute([$mfaHash]);
    if ((int) $attempts->fetchColumn() >= 5) return false;

    $stmt = db()->prepare("SELECT vu.*,v.status vendor_status FROM vendor_users vu JOIN vendors v ON v.id=vu.vendor_id WHERE vu.id=? AND vu.status='ACTIVE' AND vu.mfa_enabled=1 AND v.status='ACTIVE'");
    $stmt->execute([$id]);
    $u = $stmt->fetch();

    $valid = $u && verify_totp(decrypt_secret((string) $u['mfa_secret_encrypted']), $code);
    db()->prepare('INSERT INTO vendor_login_attempts(identity_hash,ip_address,successful) VALUES(?,?,?)')->execute([$mfaHash, client_ip(), $valid ? 1 : 0]);

    if (!$valid) {
        log_security(null, 'VENDOR_MFA_FAILED', 'HIGH', 'Failed vendor MFA verification', ['vendor_user_id' => $id]);
        return false;
    }

    begin_vendor_session($u);
    return true;
}

/**
 * Generate a single-use OTP challenge for password reset (or dev login).
 * In development the plain OTP is returned for display; in production it
 * should be delivered via SMS/WhatsApp/email through a provider adapter.
 */
function create_vendor_otp(int $vendorUserId, string $email): ?string
{
    global $config;
    $otp = (string) random_int(100000, 999999);
    db()->prepare('INSERT INTO vendor_otp_challenges(vendor_user_id,email,otp_hash,purpose,expires_at,ip_address) VALUES(?,?,?,?,DATE_ADD(NOW(),INTERVAL 5 MINUTE),?)')->execute([$vendorUserId, $email, hash('sha256', $otp), 'PASSWORD_RESET', client_ip()]);
    return ($config['env'] ?? 'production') === 'development' ? $otp : null;
}

/**
 * Verify a password-reset OTP and update the password hash in one step.
 */
function verify_vendor_otp_and_reset(string $identity, string $otp, string $newPassword): bool
{
    if (strlen($newPassword) < 8) throw new InvalidArgumentException('Password must be at least 8 characters.');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $s = $pdo->prepare("SELECT vo.*,vu.id vu_id,vu.password_hash FROM vendor_otp_challenges vo JOIN vendor_users vu ON vu.id=vo.vendor_user_id JOIN vendors v ON v.id=vu.vendor_id WHERE (vu.username=? OR vu.email=? OR v.mobile=?) AND vo.purpose='PASSWORD_RESET' AND vo.consumed_at IS NULL ORDER BY vo.id DESC LIMIT 1 FOR UPDATE");
        $s->execute([$identity, $identity, $identity]);
        $row = $s->fetch();
        $valid = $row && hash_equals((string) $row['otp_hash'], hash('sha256', $otp)) && (int) $row['attempts'] < 5;
        if($row) $pdo->prepare('UPDATE vendor_otp_challenges SET attempts=attempts+1,consumed_at=IF(?,NOW(),consumed_at) WHERE id=?')->execute([$valid, $row['id']]);
        if (!$valid) { $pdo->rollBack(); return false; }
        $pdo->prepare('UPDATE vendor_users SET password_hash=?,updated_at=NOW() WHERE id=?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), $row['vu_id']]);
        $pdo->prepare('DELETE FROM vendor_otp_challenges WHERE id=?')->execute([$row['id']]);
        $pdo->commit();
        log_security(null, 'VENDOR_PASSWORD_RESET', 'LOW', 'Vendor password reset completed', ['vendor_user_id' => $row['vu_id']]);
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/**
 * Revoke all sessions for the currently authenticated vendor.
 */
function vendor_user_disable_otp(int $vendorUserId): void
{
    db()->prepare('DELETE FROM vendor_otp_challenges WHERE vendor_user_id=? AND purpose="PASSWORD_RESET"')->execute([$vendorUserId]);
}

function redeem_gift_code(array $vendor, string $plainCode): array
{
    $clean=strtoupper(trim($plainCode));$hash=hash('sha256',$clean);$pdo=db();$pdo->beginTransaction();try{$q=$pdo->prepare('SELECT rc.*,g.name gift_name,g.status gift_status,g.total_quantity,g.redeemed_quantity,g.valid_from,g.valid_until FROM reward_codes rc JOIN gifts g ON g.id=rc.gift_id WHERE rc.code_hash=? FOR UPDATE');$q->execute([$hash]);$code=$q->fetch();$reason=null;if(!$code)$reason='Code not found.';elseif($code['status']!=='ACTIVE')$reason='Code is not active.';elseif($code['expires_at']&&$code['expires_at']<date('Y-m-d H:i:s'))$reason='Code has expired.';elseif($code['gift_status']!=='ACTIVE'||($code['valid_from']&&$code['valid_from']>date('Y-m-d H:i:s'))||($code['valid_until']&&$code['valid_until']<date('Y-m-d H:i:s')))$reason='Gift campaign is not active.';elseif((int)$code['redeemed_quantity']>=(int)$code['total_quantity'])$reason='Gift inventory is exhausted.';
        if($reason){$pdo->rollBack();db()->prepare('INSERT INTO gift_redemption_attempts(vendor_id,vendor_user_id,code_reference,code_hash,ip_address,result,reason) VALUES(?,?,?,?,?,"REJECTED",?)')->execute([$vendor['vendor_id'],$vendor['id'],$code['code_reference']??null,$hash,client_ip(),$reason]);log_security((int)($code['user_id']??0)?:null,'GIFT_REDEMPTION_REJECTED','MEDIUM',$reason,['vendor_id'=>$vendor['vendor_id']]);throw new RuntimeException($reason);}
        $redemption=transaction_code('RED');$pdo->prepare('INSERT INTO gift_redemptions(redemption_id,reward_code_id,gift_id,user_id,vendor_id,vendor_user_id) VALUES(?,?,?,?,?,?)')->execute([$redemption,$code['id'],$code['gift_id'],$code['user_id'],$vendor['vendor_id'],$vendor['id']]);$updatedCode=$pdo->prepare("UPDATE reward_codes SET status='REDEEMED',redeemed_at=NOW() WHERE id=? AND status='ACTIVE'");$updatedCode->execute([$code['id']]);if($updatedCode->rowCount()!==1)throw new RuntimeException('Code was already redeemed.');$inventory=$pdo->prepare('UPDATE gifts SET redeemed_quantity=redeemed_quantity+1 WHERE id=? AND redeemed_quantity<total_quantity');$inventory->execute([$code['gift_id']]);if($inventory->rowCount()!==1)throw new RuntimeException('Could not reserve gift inventory.');$pdo->prepare('INSERT INTO gift_redemption_attempts(vendor_id,vendor_user_id,code_reference,code_hash,ip_address,result,reason) VALUES(?,?,?,?,?,"REDEEMED",NULL)')->execute([$vendor['vendor_id'],$vendor['id'],$code['code_reference'],$hash,client_ip()]);audit_log(null,'GIFT_REDEEMED','vendor_redemptions',$redemption,null,['vendor_id'=>$vendor['vendor_id'],'user_id'=>$code['user_id'],'gift_id'=>$code['gift_id']]);$pdo->commit();return ['redemption_id'=>$redemption,'gift'=>$code['gift_name'],'code_reference'=>$code['code_reference']];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
