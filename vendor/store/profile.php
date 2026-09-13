<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$pdo=db();
$record=$pdo->prepare('SELECT * FROM vendors WHERE id=?')->execute([$vid]);
$store=$pdo->query('SELECT * FROM vendors WHERE id='.$vid)->fetch();
$isDev=($GLOBALS['config']['env']??'production')==='development';
$pageTitle='Store Profile';
$pageSubtitle='View and update your store information';
require APP_ROOT.'/includes/vendor_header.php';
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($store['vendor_code'])?> · <?=e($store['business_category'] ?? 'Store')?></p>
    <h1>Store Profile <span>•</span> <?=e($store['shop_name'])?></h1>
</section>

<section class="panel store-panel" aria-labelledby="profile-heading">
    <div class="panel-head"><h2 id="profile-heading">Store Information</h2>
    <?php if($isDev): ?><span class="help" style="background:#fff0d3;padding:6px 12px;border-radius:8px">Development mode — fields are editable below.</span><?php endif ?>
    </div>
    <form method="post" class="form-grid vendor-form">
        <?=csrf_field()?>
        <h3 class="form-section-title">General information</h3>
        <div class="field">
            <label>Vendor ID</label>
            <input value="<?=e($store['vendor_code'])?>" readonly tabindex="-1">
        </div>
        <div class="field">
            <label>Store name</label>
            <input name="shop_name" value="<?=e($store['shop_name'])?>" <?= $isDev ? '' : 'readonly' ?> required>
        </div>
        <div class="field">
            <label>Owner name</label>
            <input name="owner_name" value="<?=e($store['owner_name'] ?? '')?>" <?= $isDev ? '' : 'readonly' ?>>
        </div>
        <div class="field">
            <label>Registered mobile</label>
            <input value="<?=e($store['mobile'] ?? '')?>" readonly tabindex="-1">
        </div>
        <div class="field">
            <label>Official email</label>
            <input name="email" value="<?=e($store['email'] ?? '')?>" <?= $isDev ? '' : 'readonly' ?>>
        </div>
        <div class="field">
            <label>Business category</label>
            <input value="<?=e($store['business_category'] ?? '—')?>" readonly tabindex="-1">
        </div>
        <div class="field full">
            <label>Store address</label>
            <textarea name="address" readonly tabindex="-1"><?=e($store['address'] ?? '')?></textarea>
        </div>
        <div class="field">
            <label>GPS latitude</label>
            <input value="<?=e($store['latitude'] ?? '0.0000000')?>" readonly tabindex="-1">
        </div>
        <div class="field">
            <label>GPS longitude</label>
            <input value="<?=e($store['longitude'] ?? '0.0000000')?>" readonly tabindex="-1">
        </div>
        <div class="field full">
            <label>Store description / notes</label>
            <textarea name="onboarding_notes" readonly tabindex="-1"><?=e($store['onboarding_notes'] ?? '')?></textarea>
        </div>
        <div class="field">
            <label>YouTube / promotional link</label>
            <input value="<?=e($store['youtube_promo_url'] ?? '')?>" readonly tabindex="-1">
        </div>
        <?php if($isDev): ?>
        <div class="field full"><button class="btn" type="submit">Save changes</button></div>
        <?php endif ?>
    </form>
</section>
<?php
if(is_post() && $isDev){
    verify_csrf();
    $name=trim((string)input('shop_name'));
    $owner=trim((string)input('owner_name'));
    $email=filter_var(input('email'),FILTER_VALIDATE_EMAIL)?:$store['email'];
    $addr=trim((string)input('address'));
    $yt=trim((string)input('youtube_promo_url'));
    if($name!==''){
        db()->prepare('UPDATE vendors SET name=?,shop_name=?,owner_name=?,email=?,address=?,youtube_promo_url=?,updated_at=NOW() WHERE id=?')->execute([$name,$name,$owner?:$name,$email,$addr,$yt,$vid]);
        flash('success','Profile updated.');
    }else{ flash('error','Store name is required.'); }
    redirect('/vendor/store/profile.php');
}
require APP_ROOT.'/includes/vendor_footer.php';
?>
