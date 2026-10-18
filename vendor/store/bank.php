<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
$vendor = vendor_user();
$vid = (int) $vendor['vendor_id'];

if (is_post()) {
    verify_csrf();
    if (input('action') === 'request_bank_update') {
        $reason = trim((string) input('reason'));
        if ($reason === '') {
            flash('error', 'Please provide a reason for the bank update request.');
        } else {
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO audit_logs(action,module,record_id,description,ip_address,user_agent,created_at) VALUES(?,?,?,?,?,?,NOW())')->execute([
                    'VENDOR_BANK_UPDATE_REQUESTED', 'vendor_bank', (string) $vid, $reason, client_ip(), user_agent()
                ]);
                $pdo->commit();
                flash('success', 'Bank update request submitted. Our payments team will contact you for re-verification.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('error', 'Could not submit request. Please try again.');
            }
        }
        redirect('/vendor/store/bank.php');
    }
}

$stmt = db()->prepare('SELECT * FROM vendors WHERE id = ?');
$stmt->execute([$vid]);
$store = $stmt->fetch();
if (!$store) {
    flash('error', 'Store not found.');
    redirect('/vendor/dashboard.php');
}

$pageTitle = 'Bank & Settlement';
$pageSubtitle = 'Bank account, payout and settlement details';
require APP_ROOT.'/includes/vendor_header.php';
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($store['vendor_code'])?> · Settlement</p>
    <h1>Bank &amp; Settlement</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Bank Account on File</h2></div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>
    <tr><td>Account Holder</td><td><strong><?=e($store['bank_account_holder'] ?? '—')?></strong></td></tr>
    <tr><td>Account Number</td><td><?= $store['bank_account_number'] ? e(payment_account_mask($store['bank_account_number'])) : '—' ?></td></tr>
    <tr><td>IFSC Code</td><td><strong><?=e($store['bank_ifsc'] ?? '—')?></strong></td></tr>
    <tr><td>Bank Branch</td><td><?=e($store['bank_branch'] ?? '—')?></td></tr>
    <tr><td>UPI ID</td><td><strong><?=e($store['settlement_upi'] ?? '—')?></strong></td></tr>
    <tr><td>Settlement Cycle</td><td><strong><?=e($store['settlement_cycle'] ?? 'DAILY')?></strong></td></tr>
    <tr><td>Bank Verification</td><td><span class="store-status status-<?=e(strtolower($store['bank_verification_status'] ?? 'pending'))?>"><?=e($store['bank_verification_status'] ?? 'Not verified')?></span></td></tr>
    </tbody></table></div>
    <p class="help" style="margin-top:16px">Bank details are verified during onboarding. To update any field, submit a request below or contact administrator support.</p>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Settlement Summary</h2></div>
    <div class="grid stats">
        <div class="card">
            <span class="label">Escrow Balance</span>
            <span class="value"><?=money($store['escrow_balance'] ?? 0)?></span>
        </div>
        <div class="card">
            <span class="label">Commission Rate</span>
            <span class="value"><?=e(number_format((float) ($store['commission_rate'] ?? 0), 2))?>%</span>
        </div>
        <div class="card">
            <span class="label">Settlement Cycle</span>
            <span class="value"><?=e($store['settlement_cycle'] ?? 'DAILY')?></span>
        </div>
        <div class="card">
            <span class="label">Bank Verified</span>
            <span class="value"><?= $store['bank_verification_status']==='VERIFIED' ? '✅ Yes' : '❌ No' ?></span>
        </div>
    </div>
</section>

<section class="panel store-panel" aria-labelledby="request-heading">
    <div class="panel-head">
        <h2 id="request-heading">Request Bank Update</h2>
    </div>
    <form method="post" class="form-grid">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="request_bank_update">
        <div class="field full">
            <label for="reason">Reason for Update</label>
            <textarea id="reason" name="reason" placeholder="Describe the bank details or changes required..." required></textarea>
        </div>
        <div class="field full">
            <button class="btn" type="submit">Submit Request</button>
        </div>
    </form>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
