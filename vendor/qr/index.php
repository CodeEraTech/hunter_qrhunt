<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$vcode=$vendor['vendor_code'];
$store=db()->query('SELECT * FROM vendors WHERE id='.$vid)->fetch();
$qrPayload=$store['vendor_qr_payload'] ?? '';
$pageTitle='Store QR';
$pageSubtitle='Your store QR code for customers';
require APP_ROOT.'/includes/vendor_header.php';
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vcode)?> · QR</p>
    <h1>Store QR</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Your Store QR Code</h2></div>
    <div class="grid stats">
        <div class="card">
            <span class="label">QR Payload</span>
            <span class="value" style="font-size:14px;word-break:break-all"><?= $qrPayload ? e($qrPayload) : '<span class="store-status status-pending">Not generated — contact admin</span>' ?></span>
        </div>
    </div>
    <?php if($qrPayload): ?>
    <div class="table-wrap" style="margin-top:24px">
        <table class="store-table"><thead><tr><th>Action</th><th>Description</th></tr></thead><tbody>
        <tr><td><a class="btn secondary" href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?=urlencode($qrPayload)?>" target="_blank" rel="noopener">Download QR Image</a></td><td>Download as PNG image (300×300)</td></tr>
        <tr><td><a class="btn secondary" href="<?=e($qrPayload)?>">Open Join Link</a></td><td>Test the QR payload in a new tab</td></tr>
        </tbody></table>
    </div>
    <?php endif ?>
    <p class="help" style="margin-top:16px">Your store QR is generated during onboarding. Customers scan it to join your store program.</p>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
