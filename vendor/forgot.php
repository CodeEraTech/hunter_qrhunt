<?php declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
if(vendor_user()) redirect('/vendor/dashboard.php');

$brandName = setting_value('brand_name', 'Hunter Wallet');
$step = 'request';
$error = null;
$debug = null;
$identity = '';

if(is_post()){
    verify_csrf();

    if(input('action') === 'request_otp'){
        $identity = trim((string) input('identity'));
        if($identity === ''){
            $error = 'Please enter your mobile number or email.';
        }else{
            $hash = hash('sha256', strtolower($identity));
            if(!rate_limit('vendor-otp', 5, 900)){
                $error = 'Too many OTP attempts. Please try again later.';
            }else{
                $s = db()->prepare("SELECT vu.*,v.status vendor_status FROM vendor_users vu JOIN vendors v ON v.id=vu.vendor_id WHERE (vu.username=? OR vu.email=? OR v.mobile=?) LIMIT 1");
                $s->execute([$identity, $identity, $identity]);
                $u = $s->fetch();
                if(!$u || $u['status'] !== 'ACTIVE' || $u['vendor_status'] !== 'ACTIVE'){
                    $error = 'No active vendor account found for that email or mobile.';
                }else{
                    $email = (string) ($u['email'] ?? $u['username']);
                    $debug = create_vendor_otp((int) $u['id'], $email);
                    $_SESSION['_vendor_reset_identity'] = $identity;
                    $step = 'verify';
                }
            }
        }
    }elseif(input('action') === 'reset_password'){
        $identity = trim((string) $_SESSION['_vendor_reset_identity'] ?? '');
        if($identity === ''){
            redirect('/vendor/forgot.php');
        }
        $otp = trim((string) input('otp'));
        $newPassword = (string) input('password');
        try{
            if(verify_vendor_otp_and_reset($identity, $otp, $newPassword)){
                unset($_SESSION['_vendor_reset_identity'], $_SESSION['_vendor_reset_attempts']);
                flash('success', 'Password reset successful. Please sign in.');
                redirect('/vendor/login.php');
            }else{
                $_SESSION['_vendor_reset_attempts'] = (int) ($_SESSION['_vendor_reset_attempts'] ?? 0) + 1;
                $error = 'Invalid or expired OTP code.';
                $step = 'verify';
            }
        }catch(InvalidArgumentException $e){
            $error = $e->getMessage();
            $step = 'verify';
        }catch(Throwable $e){
            $error = 'An unexpected error occurred. Please try again.';
            $step = 'verify';
        }
    }
}
$notices = take_flashes();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset password · <?=e($brandName)?></title>
    <link rel="stylesheet" href="<?=e(app_url('/assets/css/style.css'))?>">
</head>
<body class="login-page">
<div class="login-glow login-glow-one"></div>
<div class="login-glow login-glow-two"></div>
<main class="login-card">
    <div class="login-brand">
        <span class="login-mark">H</span>
        <div>
            <strong><?=e($brandName)?> • Store Portal</strong>
            <small>Password reset</small>
        </div>
    </div>
    <?php foreach($notices as $n): ?>
        <div class="alert <?=e($n['type'])?>"><?=e($n['message'])?></div>
    <?php endforeach ?>
    <?php if($error): ?>
        <div class="alert error"><?=e($error)?></div>
    <?php endif ?>

    <?php if($step === 'request'): ?>
    <div class="login-heading">
        <h1>Forgot password?</h1>
        <p>Enter your mobile number or email to receive a one-time reset code.</p>
    </div>
    <form method="post" autocomplete="off">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="request_otp">
        <div class="field">
            <label for="identity">Mobile number or email</label>
            <input id="identity" name="identity" type="text" autocomplete="username" placeholder="9000000001 or vendor@example.com" required autofocus>
        </div>
        <button class="btn login-submit" type="submit">Send reset code <span aria-hidden="true">→</span></button>
    </form>
    <?php else: ?>
    <div class="login-heading">
        <h1>Enter reset code</h1>
        <p>A 6-digit code was sent. Enter it below along with your new password.</p>
    </div>
    <?php if($debug): ?>
        <div class="alert">Development OTP: <?=e($debug)?> — discard in production.</div>
    <?php endif ?>
    <form method="post" autocomplete="off">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="reset_password">
        <div class="field">
            <label for="otp">One-time code</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required autofocus>
        </div>
        <div class="field">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Minimum 8 characters" required>
        </div>
        <button class="btn login-submit" type="submit">Reset password <span aria-hidden="true">→</span></button>
    </form>
    <?php endif ?>

    <div class="login-divider"><span>remember?</span></div>
    <a class="otp-link" href="<?=e(app_url('/vendor/login.php'))?>">
        <span class="otp-icon">→</span>
        <span><strong>Back to sign in</strong><small>Return to the vendor login page</small></span>
    </a>
    <p class="login-security">
        <span aria-hidden="true">⌁</span>
        Reset codes expire in 5 minutes and can only be used once.
    </p>
</main>
</body>
</html>
