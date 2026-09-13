<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/includes/bootstrap.php';
require_vendor();
$vendor = vendor_user();

$bankError = null;
$bankSuccess = false;

if (is_post()) {
    verify_csrf();
    if (input('action') === 'request_bank_update') {
        $reason = trim((string) input('reason'));
        if ($reason === '') {
            $bankError = 'Please provide a reason for the bank update request.';
        } else {
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO audit_logs(action,module,record_id,description,ip_address,user_agent,created_at) VALUES(?,?,?,?,?,?,NOW())')->execute([
                    'VENDOR_BANK_UPDATE_REQUESTED', 'vendor_bank', (string) $vendor['id'], $reason, client_ip(), user_agent()
                ]);
                $pdo->commit();
                flash('success', 'Bank update request submitted. Our team will contact you for re-verification.');
                redirect('/vendor/profile/bank.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $bankError = 'Could not submit request. Please try again.';
            }
        }
    }
}

$statusMap = ['PENDING' => 'pending', 'VERIFIED' => 'approved', 'REJECTED' => 'rejected', 'UNDER_REVIEW' => 'under_review'];
$bankStatus = $statusMap[$vendor['bank_verification_status'] ?? 'PENDING'] ?? 'pending';

$pageTitle = 'Bank Details';
$pageSubtitle = 'Settlement and payout information';
require APP_ROOT.'/includes/vendor_header.php';

$notices = take_flashes();
foreach ($notices as $n): ?>
    <div class="alert <?=e($n['type'])?>"><?=e($n['message'])?></div>
<?php endforeach ?>
<?php if ($bankError): ?>
    <div class="alert error"><?=e($bankError)?></div>
<?php endif ?>

<section class="store-panel" aria-labelledby="bank-heading">
    <div class="panel-head">
        <h2 id="bank-heading">Bank &amp; Settlement Details</h2>
    </div>
    <p class="help" style="margin-bottom:18px">Bank details are read-only. Any change requires re-verification by our payments team.</p>
    <div class="form-grid">
        <div class="field">
            <label>Account Holder</label>
            <strong><?=e($vendor['name'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Account Number</label>
            <strong><?=e(payment_account_mask($vendor['bank_account_number'] ?? ''))?></strong>
        </div>
        <div class="field">
            <label>IFSC Code</label>
            <strong><?=e($vendor['bank_ifsc'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>UPI ID</label>
            <strong><?=e($vendor['settlement_upi'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Settlement Cycle</label>
            <strong><?=e(ucfirst(strtolower($vendor['settlement_cycle'] ?? '—')))?></strong>
        </div>
        <div class="field">
            <label>Bank Verification Status</label>
            <span class="store-status status-<?=e($bankStatus)?>"><?=e(ucfirst(strtolower($vendor['bank_verification_status'] ?? 'UNKNOWN')))?></span>
        </div>
    </div>
</section>

<section class="store-panel" aria-labelledby="request-heading">
    <div class="panel-head">
        <h2 id="request-heading">Request Bank Update</h2>
    </div>
    <form method="post" class="form-grid">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="request_bank_update">
        <div class="field full">
            <label for="reason">Reason for Update</label>
            <textarea id="reason" name="reason" placeholder="Describe the changes required..." required></textarea>
        </div>
        <div class="field full">
            <button class="btn" type="submit">Submit Request</button>
        </div>
    </form>
</section>

<?php
require APP_ROOT.'/includes/vendor_footer.php';
