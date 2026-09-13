<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/includes/bootstrap.php';
require_vendor();
$vendor = vendor_user();
$editing = false;
$editError = null;
$editSuccess = false;

if (is_post() && input('action') === 'edit_profile') {
    verify_csrf();
    $email = trim((string) input('email'));
    $shopName = trim((string) input('shop_name'));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $editError = 'Please enter a valid email address.';
    } else {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE vendor_users SET email=?,updated_at=NOW() WHERE id=?')->execute([$email, $vendor['id']]);
            $pdo->prepare('UPDATE vendors SET email=?,shop_name=?,updated_at=NOW() WHERE id=?')->execute([$email, $shopName, $vendor['vendor_id']]);
            $pdo->commit();
            flash('success', 'Profile updated successfully.');
            redirect('/vendor/profile/index.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $editError = 'Could not update profile. Please try again.';
        }
    }
    $editing = true;
}

if (isset($_GET['edit'])) $editing = true;

$statusMap = ['ACTIVE' => 'active', 'INACTIVE' => 'inactive', 'SUSPENDED' => 'blocked', 'PENDING' => 'pending'];
$kycMap = ['PENDING' => 'pending', 'VERIFIED' => 'approved', 'REJECTED' => 'rejected', 'UNDER_REVIEW' => 'under_review'];
$bankMap = ['PENDING' => 'pending', 'VERIFIED' => 'approved', 'REJECTED' => 'rejected', 'UNDER_REVIEW' => 'under_review'];

$storeStatus = $statusMap[$vendor['vendor_status'] ?? 'ACTIVE'] ?? 'pending';
$kycStatus = $kycMap[$vendor['kyc_status'] ?? 'PENDING'] ?? 'pending';
$bankStatus = $bankMap[$vendor['bank_verification_status'] ?? 'PENDING'] ?? 'pending';

$pageTitle = 'My Profile';
$pageSubtitle = 'Vendor identity and store information';
require APP_ROOT.'/includes/vendor_header.php';

$notices = take_flashes();
foreach ($notices as $n): ?>
    <div class="alert <?=e($n['type'])?>"><?=e($n['message'])?></div>
<?php endforeach ?>

<section class="store-panel" aria-labelledby="profile-heading">
    <div class="panel-head">
        <div>
            <h2 id="profile-heading">Vendor Identity Card</h2>
            <p class="help">Your registered vendor account details. Sensitive changes require admin approval.</p>
        </div>
        <?php if (!$editing): ?>
            <a class="btn secondary" href="<?=e(app_url('/vendor/profile/index.php?edit=1'))?>">Edit Profile</a>
        <?php endif ?>
    </div>
    <div class="form-grid">
        <div class="field">
            <label>Vendor ID</label>
            <strong><?=e($vendor['vendor_code'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Business Name</label>
            <strong><?=e($vendor['shop_name'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Owner Name</label>
            <strong><?=e($vendor['name'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Registered Mobile</label>
            <strong><?=e($vendor['mobile'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Email</label>
            <strong><?=e($vendor['email'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Category</label>
            <strong><?=e($vendor['business_category'] ?? '—')?></strong>
        </div>
        <div class="field">
            <label>Account Status</label>
            <span class="store-status status-<?=e($storeStatus)?>"><?=e(ucfirst(strtolower($vendor['vendor_status'] ?? 'UNKNOWN')))?></span>
        </div>
        <div class="field">
            <label>KYC Status</label>
            <span class="store-status status-<?=e($kycStatus)?>"><?=e(ucfirst(strtolower($vendor['kyc_status'] ?? 'UNKNOWN')))?></span>
        </div>
        <div class="field">
            <label>Bank Verification</label>
            <span class="store-status status-<?=e($bankStatus)?>"><?=e(ucfirst(strtolower($vendor['bank_verification_status'] ?? 'UNKNOWN')))?></span>
        </div>
        <div class="field full">
            <label>Member Since</label>
            <strong><?=e($vendor['created_at'] ?? '—')?></strong>
        </div>
    </div>
    <?php if ($editing): ?>
        <section class="store-panel" style="margin-top:22px;padding:20px" aria-labelledby="edit-heading">
            <div class="panel-head" style="border-bottom:1px solid var(--line);padding-bottom:14px;margin-bottom:14px">
                <h2 id="edit-heading">Edit Profile</h2>
            </div>
            <?php if ($editError): ?>
                <div class="alert error"><?=e($editError)?></div>
            <?php endif ?>
            <form method="post">
                <?=csrf_field()?>
                <input type="hidden" name="action" value="edit_profile">
                <div class="form-grid">
                    <div class="field full">
                        <label for="email">Email Address</label>
                        <input id="email" name="email" type="email" value="<?=e($vendor['email'])?>" required>
                    </div>
                    <div class="field full">
                        <label for="shop_name">Business Name</label>
                        <input id="shop_name" name="shop_name" type="text" value="<?=e($vendor['shop_name'])?>" required>
                    </div>
                </div>
                <div class="actions" style="margin-top:16px">
                    <button class="btn" type="submit">Save Changes</button>
                    <a class="btn secondary" href="<?=e(app_url('/vendor/profile/index.php'))?>">Cancel</a>
                </div>
            </form>
        </section>
    <?php endif ?>
</section>

<?php
require APP_ROOT.'/includes/vendor_footer.php';
