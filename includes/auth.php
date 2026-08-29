<?php
declare(strict_types=1);

function admin(): ?array
{
    static $cached = false;
    if ($cached !== false) return $cached;
    if (empty($_SESSION['admin_id'])) return $cached = null;
    $stmt = db()->prepare('SELECT a.*, r.name role_name FROM admins a JOIN admin_roles r ON r.id=a.role_id WHERE a.id=? AND a.status="ACTIVE"');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    return $cached = ($stmt->fetch() ?: null);
}
function require_admin(): void { if (!admin()) { flash('error', 'Please sign in.'); redirect('/admin/login.php'); } }
function enforce_session_timeout(int $seconds): void
{
    if (!empty($_SESSION['admin_id']) && isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > $seconds) {
        session_unset(); session_destroy(); session_start(); flash('error', 'Your session expired.'); redirect('/admin/login.php');
    }
    if (!empty($_SESSION['admin_id'])) $_SESSION['last_activity'] = time();
}
function begin_admin_session(array $row): void
{
    session_regenerate_id(true); unset($_SESSION['pending_mfa_admin_id'], $_SESSION['pending_mfa_expires']);
    $_SESSION['admin_id'] = (int) $row['id']; $_SESSION['last_activity'] = time();
    db()->prepare('UPDATE admins SET last_login_at=NOW(), last_login_ip=? WHERE id=?')->execute([client_ip(), $row['id']]);
    audit_log((int) $row['id'], 'LOGIN', 'auth', (string) $row['id'], null, ['ip' => client_ip()]);
}
function attempt_login(string $identity, string $password): string
{
    $identityHash = hash('sha256', strtolower($identity));
    $limit = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE (ip_address=? OR identity_hash=?) AND successful=0 AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
    $limit->execute([client_ip(), $identityHash]);
    if ((int) $limit->fetchColumn() >= 5) return 'FAILED';
    $stmt = db()->prepare('SELECT * FROM admins WHERE (email=? OR username=?) LIMIT 1');
    $stmt->execute([$identity, $identity]); $row = $stmt->fetch();
    $ok = $row && $row['status'] === 'ACTIVE' && password_verify($password, $row['password_hash']);
    db()->prepare('INSERT INTO login_attempts(identity_hash,ip_address,successful) VALUES(?,?,?)')->execute([$identityHash,client_ip(),$ok?1:0]);
    if (!$ok) {
        log_security(null, 'ADMIN_LOGIN_FAILED', 'MEDIUM', 'Failed admin login', ['identity_hash' => $identityHash]); return 'FAILED';
    }
    if ((int) $row['mfa_enabled'] === 1) {
        if (empty($row['mfa_secret_encrypted'])) { log_security(null, 'ADMIN_MFA_CONFIGURATION_ERROR', 'HIGH', 'MFA-enabled admin has no configured secret'); return 'FAILED'; }
        session_regenerate_id(true); $_SESSION['pending_mfa_admin_id'] = (int) $row['id']; $_SESSION['pending_mfa_expires'] = time() + 300; return 'MFA_REQUIRED';
    }
    begin_admin_session($row); return 'AUTHENTICATED';
}
function complete_mfa_login(string $code): bool
{
    $id = (int) ($_SESSION['pending_mfa_admin_id'] ?? 0); $expires = (int) ($_SESSION['pending_mfa_expires'] ?? 0);
    if (!$id || time() > $expires) { unset($_SESSION['pending_mfa_admin_id'], $_SESSION['pending_mfa_expires']); return false; }
    $mfaHash=hash('sha256','admin-mfa:'.$id);$attempts=db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE identity_hash=? AND successful=0 AND attempted_at>DATE_SUB(NOW(),INTERVAL 5 MINUTE)');$attempts->execute([$mfaHash]);if((int)$attempts->fetchColumn()>=5)return false;
    $stmt = db()->prepare('SELECT * FROM admins WHERE id=? AND status="ACTIVE" AND mfa_enabled=1'); $stmt->execute([$id]); $row = $stmt->fetch();
    $valid=$row&&verify_totp(decrypt_secret((string)$row['mfa_secret_encrypted']),$code);db()->prepare('INSERT INTO login_attempts(identity_hash,ip_address,successful) VALUES(?,?,?)')->execute([$mfaHash,client_ip(),$valid?1:0]);if(!$valid){log_security(null,'ADMIN_MFA_FAILED','HIGH','Failed admin MFA verification',['admin_id'=>$id]);return false;}
    begin_admin_session($row); return true;
}
function logout_admin(): void { if (admin()) audit_log((int) admin()['id'], 'LOGOUT', 'auth', (string) admin()['id']); session_unset(); session_destroy(); }

function create_admin_otp(string $mobile): ?string
{
    global $config;$s=db()->prepare('SELECT a.id FROM admins a JOIN staff_profiles p ON p.admin_id=a.id WHERE p.mobile=? AND p.kyc_status="APPROVED" AND a.status="ACTIVE"');$s->execute([$mobile]);$adminId=$s->fetchColumn();if(!$adminId)throw new RuntimeException('Only approved staff can use passwordless OTP login.');$otp=(string)random_int(100000,999999);db()->prepare('INSERT INTO admin_otp_challenges(mobile,admin_id,otp_hash,purpose,expires_at,ip_address) VALUES(?,?,?,"LOGIN",DATE_ADD(NOW(),INTERVAL 5 MINUTE),?)')->execute([$mobile,$adminId,hash('sha256',$otp),client_ip()]);$_SESSION['_otp_admin_id']=(int)$adminId;$_SESSION['_otp_mobile']=$mobile;return (($config['env']??'production')==='development')?$otp:null;
}
function verify_admin_otp(string $mobile,string $otp): bool
{
    $pdo=db();$pdo->beginTransaction();$s=$pdo->prepare('SELECT * FROM admin_otp_challenges WHERE mobile=? AND purpose="LOGIN" AND consumed_at IS NULL AND expires_at>NOW() ORDER BY id DESC LIMIT 1 FOR UPDATE');$s->execute([$mobile]);$row=$s->fetch();$valid=$row&&hash_equals((string)$row['otp_hash'],hash('sha256',$otp))&&(int)$row['attempts']<5;if($row)$pdo->prepare('UPDATE admin_otp_challenges SET attempts=attempts+1,consumed_at=IF(?,NOW(),consumed_at) WHERE id=?')->execute([$valid,$row['id']]);$pdo->commit();if(!$valid)return false;$a=$pdo->prepare('SELECT * FROM admins WHERE id=? AND status="ACTIVE"');$a->execute([$row['admin_id']]);$adminRow=$a->fetch();if(!$adminRow)return false;begin_admin_session($adminRow);return true;
}
