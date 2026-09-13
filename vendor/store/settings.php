<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$pdo=db();
$store=$pdo->query('SELECT * FROM vendors WHERE id='.$vid)->fetch();
$isDev=($GLOBALS['config']['env']??'production')==='development';
$pageTitle='Store Settings';
$pageSubtitle='Operational and notification preferences';
require APP_ROOT.'/includes/vendor_header.php';
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($store['vendor_code'])?> · Configuration</p>
    <h1>Store Settings</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Operational Preferences</h2></div>
    <form method="post" class="form-grid vendor-form">
        <?=csrf_field()?>
        <div class="field">
            <label>Proximity Radius (m)</label>
            <input name="proximity_radius_m" type="number" min="1" value="<?=e($store['proximity_radius_m'] ?? 300)?>" <?= $isDev ? '' : 'readonly' ?>>
        </div>
        <div class="field">
            <label>Settlement Cycle</label>
            <input value="<?=e($store['settlement_cycle'] ?? 'DAILY')?>" readonly tabindex="-1">
        </div>
        <div class="field">
            <label>Commission Type</label>
            <input value="<?=e($store['commission_type'] ?? 'PERCENTAGE')?>" readonly tabindex="-1">
        </div>
        <div class="field">
            <label>Commission Rate (%)</label>
            <input value="<?=e(number_format($store['commission_rate'] ?? 0, 2))?>" readonly tabindex="-1">
        </div>
        <?php if($isDev): ?>
        <div class="field full"><button class="btn" type="submit">Save preferences</button></div>
        <?php endif ?>
    </form>
    <?php
    if(is_post() && $isDev){
        verify_csrf();
        $prox=max(1,(int)input('proximity_radius_m',300));
        db()->prepare('UPDATE vendors SET proximity_radius_m=?,updated_at=NOW() WHERE id=?')->execute([$prox,$vid]);
        flash('success','Settings saved.');
        redirect('/vendor/store/settings.php');
    }
    ?>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
