<?php declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
if(vendor_user()) redirect('/vendor/dashboard.php');

$brandName = setting_value('brand_name', 'Hunter Wallet');
$error = null;

if(!isset($_SESSION['pending_mfa_vendor_user_id'])){
    redirect('/vendor/login.php');
}

if(is_post()){
    verify_csrf();
    if(input('action') === 'back'){
        unset($_SESSION['pending_mfa_vendor_user_id'], $_SESSION['pending_mfa_vendor_expires']);
        redirect('/vendor/login.php');
    }
    $code = trim((string) input('code'));
    if(complete_vendor_mfa($code)){
        redirect('/vendor/dashboard.php');
    }
    $error = 'Invalid or expired verification code.';
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Two-factor authentication · <?=e($brandName)?></title>
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
            <small>Two-factor authentication</small>
        </div>
    </div>
    <div class="login-heading">
        <h1>Authentication code required</h1>
        <p>Enter the 6-digit code from your authenticator app to complete sign-in.</p>
    </div>
    <?php if($error): ?>
        <div class="alert error"><?=e($error)?></div>
    <?php endif ?>
    <form method="post" autocomplete="one-time-code">
        <?=csrf_field()?>
        <div class="field">
            <label for="code">Authentication code</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required autofocus>
        </div>
        <button class="btn login-submit" type="submit">Verify and sign in <span aria-hidden="true">→</span></button>
    </form>
    <form method="post" class="stack">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="back">
        <button class="btn secondary" type="submit">Back to password</button>
    </form>
    <p class="login-security">
        <span aria-hidden="true">⌁</span>
        Time-based codes expire every 30 seconds. You have 5 attempts.
    </p>
</main>
</body>
</html>
