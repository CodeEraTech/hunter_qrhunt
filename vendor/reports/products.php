<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$export=isset($_GET['export'])&&$_GET['export']==='csv';
$pageTitle='Product Report';
$pageSubtitle='Product catalog performance and status';
require APP_ROOT.'/includes/vendor_header.php';
$pdo=db();
$rows=$pdo->query('SELECT product_code,title,price,metadata,status,admin_note,submitted_at FROM vendor_products WHERE vendor_id='.$vid.' ORDER BY submitted_at DESC,id DESC')->fetchAll();
if($export){
    header('Content-Type:text/csv');
    header('Content-Disposition:attachment;filename=product_report.csv');
    $out=fopen('php://output','w');
    fputcsv($out,['SKU','Product','Category','Price','Status','Remarks','Date']);
    foreach($rows as $r){
        $m=json_decode($r['metadata']??'{}',true);
        $cat=is_array($m)&&is_string($m['category']??null)?$m['category']:'—';
        fputcsv($out,[$r['product_code'],$r['title'],$cat,$r['price'],$r['status'],$r['admin_note']??'',$r['submitted_at']]);
    }
    exit;
}
$counts=[];foreach($rows as $r)$counts[$r['status']]=($counts[$r['status']]??0)+1;
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vendor['vendor_code'])?> · Reports</p>
    <h1>Product Report</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Catalog Overview</h2>
    <a class="btn secondary" href="?export=csv">Export CSV</a>
    </div>
    <div class="grid stats">
        <div class="card"><span class="label">Total Products</span><span class="value"><?=e(count($rows))?></span></div>
        <div class="card"><span class="label">Approved</span><span class="value"><?=e($counts['APPROVED']??0)?></span></div>
        <div class="card"><span class="label">Pending Review</span><span class="value"><?=e(($counts['PENDING_REVIEW']??0)+($counts['RESUBMITTED']??0))?></span></div>
        <div class="card"><span class="label">Rejected / Disabled</span><span class="value"><?=e(($counts['REJECTED']??0)+($counts['DISABLED']??0))?></span></div>
    </div>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>All Products</h2></div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Price</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach($rows as $r):
        $m=json_decode($r['metadata']??'{}',true);$cat=is_array($m)&&is_string($m['category']??null)?$m['category']:'—';
        $st=strtolower($r['status']);$cls=in_array($st,['pending_review','resubmitted'])?'pending':($r['status']==='APPROVED'?'approved':($r['status']==='DISABLED'?'inactive':'rejected'));
    ?>
    <tr><td><strong><?=e($r['product_code'])?></strong></td><td><?=e($r['title'])?></td><td><?=e($cat)?></td><td class="store-amount"><?=money($r['price'])?></td><td><span class="store-status status-<?=e($cls)?>"><?=e($r['status'])?></span></td><td><?=e($r['submitted_at'])?></td></tr>
    <?php endforeach ?>
    <?php if(!$rows): ?><tr><td colspan="6" class="empty">No products submitted yet.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
