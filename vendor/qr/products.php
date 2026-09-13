<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$vcode=$vendor['vendor_code'];
$pageTitle='Product QR Codes';
$pageSubtitle='QR codes for approved products';
require APP_ROOT.'/includes/vendor_header.php';
$products=db()->prepare('SELECT product_code,title,price,status FROM vendor_products WHERE vendor_id=? AND status="APPROVED" ORDER BY title')->execute([$vid]);
$products=db()->query('SELECT product_code,title,price,status FROM vendor_products WHERE vendor_id='.$vid.' AND status="APPROVED" ORDER BY title')->fetchAll();
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vcode)?> · Product QR</p>
    <h1>Product QR Codes</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Approved Product QR Codes</h2>
    <a class="btn secondary" href="<?=e(app_url('/vendor/qr/bulk.php'))?>">Bulk generate QR</a>
    </div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>SKU</th><th>Product</th><th>Price</th><th>QR Payload</th><th>Download</th></tr></thead><tbody>
    <?php foreach($products as $p):
        $qr=urlencode('join://vendor?id='.$vcode.'&product='.$p['product_code']);
    ?>
    <tr>
        <td><strong><?=e($p['product_code'])?></strong></td>
        <td><?=e($p['title'])?></td>
        <td class="store-amount"><?=money($p['price'])?></td>
        <td style="font-size:11px;word-break:break-all">join://vendor?id=<?=e($vcode)?>&amp;product=<?=e($p['product_code'])?></td>
        <td><a class="btn small secondary" href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?=$qr?>" target="_blank" rel="noopener">Download</a></td>
    </tr>
    <?php endforeach ?>
    <?php if(!$products): ?><tr><td colspan="5" class="empty">No approved products yet.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
