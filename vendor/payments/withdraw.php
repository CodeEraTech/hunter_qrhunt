<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$vcode=$vendor['vendor_code'];
$store=db()->query('SELECT * FROM vendors WHERE id='.$vid)->fetch();
$pageTitle='Withdraw Money';
$pageSubtitle='Request payout to your bank or UPI';
require APP_ROOT.'/includes/vendor_header.php';
$error=null;
if(is_post()){
    verify_csrf();
    $action=trim((string)input('action'));
    if($action==='request'){
        $amount=(float)input('amount',0);
        if($amount<1){$error='Minimum withdrawal amount is ₹1.00';}
        else{
            $bank=trim((string)input('bank_account'));
            $ifsc=strtoupper(trim((string)input('ifsc')));
            $upi=trim((string)input('upi'));
            if($bank===''&&$upi===''){$error='Provide bank account number or UPI ID.';}
            else{
                $otp=trim((string)input('otp'));
                if($otp===''){$error='OTP is required for security.';}
                else{
                    $s=db()->prepare('SELECT id FROM vendor_otp_challenges WHERE vendor_user_id=? AND purpose="PASSWORD_RESET" AND otp_hash=? AND consumed_at IS NULL AND expires_at>NOW() ORDER BY id DESC LIMIT 1');
                    $s->execute([$vid,hash('sha256',$otp)]);
                    if(!$s->fetch()){$error='Invalid or expired OTP. Request a new one.';}
                    else{
                        db()->prepare('INSERT INTO vendor_payout_requests(vendor_id,amount,status) VALUES(?,?,"PENDING")')->execute([$vid,$amount]);
                        db()->prepare('UPDATE vendor_otp_challenges SET consumed_at=NOW() WHERE vendor_user_id=? AND otp_hash=?')->execute([$vid,hash('sha256',$otp)]);
                        flash('success','Withdrawal request of '.money($amount).' submitted. Pending admin review.');
                        redirect('/vendor/payments/withdraw.php');
                    }
                }
            }
        }
    }elseif($action==='otp'){
        $debug=create_vendor_otp($vid,$vendor['email'] ?? $vcode);
        $error='Enter the OTP sent to your registered email.';
        if($debug) $error.=' <strong class="help">Dev OTP: '.e($debug).'</strong>';
    }
}
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vcode)?> · Payments</p>
    <h1>Withdraw Money</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Withdrawal Request</h2></div>
    <?php foreach(take_flashes() as $n): ?><div class="alert <?=e($n['type'])?>"><?=e($n['message'])?></div><?php endforeach ?>
    <?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif ?>
    <div class="grid stats" style="margin-bottom:24px">
        <div class="card store-metric metric-green"><h2>Available Balance</h2><strong><?=money($store['escrow_balance'] ?? 0)?></strong></div>
        <div class="card"><span class="label">Bank Account</span><span class="value"><?= $store['bank_account_number'] ? e(payment_account_mask($store['bank_account_number'])) : 'Not set' ?></span></div>
        <div class="card"><span class="label">UPI ID</span><span class="value"><?=e($store['settlement_upi'] ?? '—')?></span></div>
    </div>
    <form method="post" class="form-grid vendor-form">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="request">
        <div class="field">
            <label>Amount (₹)</label>
            <input name="amount" type="number" step="0.01" min="1" max="<?=e($store['escrow_balance'] ?? 0)?>" placeholder="Enter amount" required>
        </div>
        <div class="field">
            <label>Bank Account Number</label>
            <input name="bank_account" value="<?=e($store['bank_account_number'] ?? '')?>" placeholder="Account number">
        </div>
        <div class="field">
            <label>IFSC Code</label>
            <input name="ifsc" value="<?=e($store['bank_ifsc'] ?? '')?>" placeholder="IFSC">
        </div>
        <div class="field">
            <label>UPI ID</label>
            <input name="upi" value="<?=e($store['settlement_upi'] ?? '')?>" placeholder="UPI ID">
        </div>
        <div class="field full" style="display:flex;align-items:center;gap:12px">
            <button type="button" class="btn secondary" id="request-otp-btn">Request OTP</button>
            <span class="help">Click first, then enter OTP below.</span>
        </div>
        <div class="field full">
            <label>OTP Verification</label>
            <input name="otp" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required>
            <input type="hidden" name="action" value="request">
        </div>
        <div class="field full"><button class="btn" type="submit">Submit Withdrawal Request</button></div>
    </form>
</section>
<script>
document.getElementById('request-otp-btn')?.addEventListener('click',function(){
    const form=document.querySelector('form[method="post"]');
    const action=form.querySelector('input[name="action"]');
    action.value='otp';
    form.submit();
});
</script>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
