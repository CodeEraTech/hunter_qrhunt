<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$vcode=$vendor['vendor_code'];
$pageTitle='Bulk QR Generation';
$pageSubtitle='Generate QR codes for multiple products';
require APP_ROOT.'/includes/vendor_header.php';
$products=db()->query('SELECT product_code,title,price FROM vendor_products WHERE vendor_id='.$vid.' AND status="APPROVED" ORDER BY title')->fetchAll();
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vcode)?> · Bulk QR</p>
    <h1>Bulk QR Generation</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Generate QR Batch</h2></div>
    <form method="post" class="form-grid vendor-form">
        <?=csrf_field()?>
        <p>Select all approved products to include in this batch. QR payloads are built from your vendor code and each product SKU.</p>
        <div class="field full">
            <label>Select products</label>
            <?php foreach($products as $p): ?>
            <label style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--line)">
                <input type="checkbox" name="skus[]" value="<?=e($p['product_code'])?>" checked>
                <span><?=e($p['product_code'])?> — <?=e($p['title'])?> — <?=money($p['price'])?></span>
            </label>
            <?php endforeach ?>
            <?php if(!$products): ?><p class="help">No approved products available.</p><?php endif ?>
        </div>
        <div class="field full"><button class="btn" type="submit">Generate QR batch sheet</button></div>
    </form>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Generated Batch Sheet</h2></div>
    <p class="help">Below is the plain-text sheet for the selected products. Download or print from your browser.</p>
    <textarea style="width:100%;height:220px;font-family:ui-monospace,monospace;border:1px solid var(--line);border-radius:10px;padding:12px" readonly>VENDOR: <?=e($vcode)?>
BATCH: <?=e(date('Y-m-d H:i'))?>
<?php foreach($products as $p): ?>
QR: join://vendor?id=<?=e($vcode)?>&product=<?=e($p['product_code'])?> | SKU: <?=e($p['product_code'])?> | <?=e($p['title'])?> | <?=money($p['price'])?>
<?php endforeach ?>
<?php if(!$products): ?>No approved products<?php endif ?></textarea>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
