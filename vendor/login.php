<?php declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
if(vendor_user()) redirect('/vendor/dashboard.php');

$brandName = setting_value('brand_name', 'Hunter Wallet');
$tagline = setting_value('brand_tagline', 'Secure vendor portal');
$error = null;

if(is_post()){
    verify_csrf();
    if(!rate_limit('vendor-login', 5, 900)){
        flash('error', 'Too many login attempts. Please try again later.');
    }else{
        $identity = trim((string) input('identity'));
        $password = (string) input('password');
        $result = attempt_vendor_login($identity, $password);
        if($result === 'AUTHENTICATED') redirect('/vendor/dashboard.php');
        if($result === 'MFA_REQUIRED') redirect('/vendor/mfa.php');
        $error = 'Invalid credentials, account not active, or too many attempts.';
    }
}
$notices = take_flashes();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Vendor sign in · <?=e($brandName)?></title>
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
            <small><?=e($tagline)?></small>
        </div>
    </div>
    <div class="login-heading">
        <h1>Vendor / Store sign in</h1>
        <p>Access your store finances, products, withdrawals and settlements. <a class="muted-link" href="<?=e(app_url('/vendor/forgot.php'))?>">Forgot password?</a></p>
    </div>
    <?php foreach($notices as $n): ?>
        <div class="alert <?=e($n['type'])?>"><?=e($n['message'])?></div>
    <?php endforeach ?>
    <?php if($error): ?>
        <div class="alert error"><?=e($error)?></div>
    <?php endif ?>
    <form method="post" autocomplete="off">
        <?=csrf_field()?>
        <div class="field">
            <label for="identity">Mobile number or email</label>
            <input id="identity" name="identity" type="text" autocomplete="username" placeholder="9000000001 or vendor@example.com" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
        </div>
        <button class="btn login-submit" type="submit">Sign in securely <span aria-hidden="true">→</span></button>
    </form>
    <div class="login-divider"><span>admin instead?</span></div>
    <a class="otp-link" href="<?=e(app_url('/admin/login.php'))?>">
        <span class="otp-icon">A</span>
        <span><strong>Admin console</strong><small>Switch to the administrator sign-in</small></span>
        <span class="otp-arrow" aria-hidden="true">→</span>
    </a>
    <p class="login-security">
        <span aria-hidden="true">⌁</span>
        Protected with encrypted sessions, audit logging and brute-force throttling.
    </p>
</main>
</body>
</html>
