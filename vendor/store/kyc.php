<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$store=db()->prepare('SELECT * FROM vendors WHERE id=?')->execute([$vid]);
$store=db()->query('SELECT * FROM vendors WHERE id='.$vid)->fetch();
$pageTitle='Business & KYC';
$pageSubtitle='KYC documents and verification status';
require APP_ROOT.'/includes/vendor_header.php';
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($store['vendor_code'])?> · Know Your Customer</p>
    <h1>Business &amp; KYC</h1>
</section>

<section class="panel store-panel">
    <div class="panel-head"><h2>Verification Overview</h2></div>
    <div class="grid stats">
        <div class="card">
            <span class="label">KYC Status</span>
            <span class="value"><span class="store-status status-<?=e(strtolower($store['kyc_status'] ?? 'pending'))?>"><?=e($store['kyc_status'] ?? 'Not started')?></span></span>
        </div>
        <div class="card">
            <span class="label">Account Status</span>
            <span class="value"><span class="store-status status-<?=e(strtolower($store['status'] ?? 'pending'))?>"><?=e($store['status'] ?? '—')?></span></span>
        </div>
        <div class="card">
            <span class="label">Mobile OTP Verified</span>
            <span class="value"><?=$store['mobile_otp_verified'] ? '<span class="store-status status-approved">Verified</span>' : '<span class="store-status status-pending">Pending</span>'?></span>
        </div>
        <div class="card">
            <span class="label">Bank Verification</span>
            <span class="value"><span class="store-status status-<?=e(strtolower($store['bank_verification_status'] ?? 'pending'))?>"><?=e($store['bank_verification_status'] ?? 'Not started')?></span></span>
        </div>
    </div>
</section>

<section class="panel store-panel">
    <div class="panel-head"><h2>Uploaded Documents</h2></div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>Document Type</th><th>File</th><th>Status</th></tr></thead><tbody>
    <?php
    $docs = [
        ['Identity Proof', 'identity_proof_path'],
        ['Address Proof', 'address_proof_path'],
        ['GST Document', 'gst_document_path'],
        ['Cancelled Cheque', 'cancelled_cheque_path'],
    ];
    foreach($docs as [$label,$col]):
        $path=trim((string)($store[$col] ?? ''));
        $status=$path ? '<span class="store-status status-approved">Uploaded</span>' : '<span class="store-status status-pending">Not uploaded</span>';
        echo '<tr><td><strong>'.e($label).'</strong></td><td>'.($path ? e($path) : '—').'</td><td>'.$status.'</td></tr>';
    endforeach;
    ?>
    </tbody></table>
</div>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
