<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/includes/bootstrap.php';
require_vendor();
$vendor = vendor_user();

$kycMap = ['PENDING' => 'pending', 'VERIFIED' => 'approved', 'REJECTED' => 'rejected', 'UNDER_REVIEW' => 'under_review'];
$kycStatus = $kycMap[$vendor['kyc_status'] ?? 'PENDING'] ?? 'pending';

$documents = [
    ['type' => 'Identity Proof', 'path' => $vendor['identity_proof_path'] ?? null, 'status' => 'uploaded'],
    ['type' => 'Address Proof', 'path' => $vendor['address_proof_path'] ?? null, 'status' => 'uploaded'],
    ['type' => 'GST Document', 'path' => $vendor['gst_document_path'] ?? null, 'status' => 'uploaded'],
    ['type' => 'Cancelled Cheque', 'path' => $vendor['cancelled_cheque_path'] ?? null, 'status' => 'uploaded'],
];

$pageTitle = 'Documents';
$pageSubtitle = 'KYC and business document records';
require APP_ROOT.'/includes/vendor_header.php';
?>

<section class="store-panel" aria-labelledby="docs-heading">
    <div class="panel-head">
        <h2 id="docs-heading">Uploaded Documents</h2>
        <span class="store-status status-<?=e($kycStatus)?>">KYC: <?=e(ucfirst(strtolower($vendor['kyc_status'] ?? 'UNKNOWN')))?></span>
    </div>
    <p class="help" style="margin-bottom:18px">Documents were uploaded during onboarding and are read-only. Contact support if you need to replace any file.</p>
    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>File</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><strong><?=e($doc['type'])?></strong></td>
                        <td>
                            <?php if ($doc['path']): ?>
                                <a href="<?=e(app_url($doc['path']))?>" target="_blank" rel="noopener">View file</a>
                            <?php else: ?>
                                <span style="color:var(--muted)">Not uploaded</span>
                            <?php endif ?>
                        </td>
                        <td>
                            <span class="store-status status-<?=e($doc['path'] ? 'approved' : 'pending')?>">
                                <?=e($doc['path'] ? 'Uploaded' : 'Pending')?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require APP_ROOT.'/includes/vendor_footer.php';
