<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$from=trim((string)($_GET['from']??date('Y-m-01')));
$to=trim((string)($_GET['to']??date('Y-m-d')));
$export=isset($_GET['export'])&&$_GET['export']==='csv';
$pageTitle='Settlement Report';
$pageSubtitle='Merchant settlement and withdrawal history';
require APP_ROOT.'/includes/vendor_header.php';
$pdo=db();
$sql='SELECT s.settlement_id,s.checkout_id,s.merchant_id,s.bill_amount,s.platform_fee,s.merchant_amount,s.status,s.settled_at,c.created_at FROM merchant_settlements s LEFT JOIN wallet_checkout_transactions c ON c.checkout_id=s.checkout_id AND c.merchant_id=s.merchant_id WHERE s.merchant_id=? AND s.created_at>=? AND s.created_at<DATE_ADD(?,INTERVAL 1 DAY) ORDER BY s.id DESC';
$stmt=$pdo->prepare($sql);$stmt->execute([$vid,$from,$to]);$rows=$stmt->fetchAll();
$summary=['total_settled'=>0,'total_pending'=>0,'total_fees'=>0];
foreach($rows as $r){
    if($r['status']==='SETTLED')$summary['total_settled']+=(float)$r['merchant_amount'];
    elseif($r['status']==='PENDING')$summary['total_pending']+=(float)$r['bill_amount'];
    $summary['total_fees']+=(float)$r['platform_fee'];
}
if($export){
    header('Content-Type:text/csv');
    header('Content-Disposition:attachment;filename=settlement_report_'.$from.'_'.$to.'.csv');
    $out=fopen('php://output','w');
    fputcsv($out,['Settlement ID','Checkout ID','Date','Bill Amount','Platform Fee','Net Payable','Status','Settled Date']);
    foreach($rows as $r) fputcsv($out,[$r['settlement_id'],$r['checkout_id'],$r['created_at'],$r['bill_amount'],$r['platform_fee'],$r['merchant_amount'],$r['status'],$r['settled_at']]);
    exit;
}
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vendor['vendor_code'])?> · Reports</p>
    <h1>Settlement Report</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Filters</h2></div>
    <form method="get" class="form-grid vendor-form" style="align-items:end">
        <div class="field"><label>From</label><input type="date" name="from" value="<?=e($from)?>"></div>
        <div class="field"><label>To</label><input type="date" name="to" value="<?=e($to)?>"></div>
        <div class="field" style="display:flex;gap:10px">
            <button class="btn" type="submit">Apply</button>
            <a class="btn secondary" href="?from=<?=e($from)?>&to=<?=e($to)?>&export=csv">Export CSV</a>
        </div>
    </form>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Summary</h2></div>
    <div class="grid stats">
        <div class="card"><span class="label">Total Settled</span><span class="value"><?=money($summary['total_settled'])?></span></div>
        <div class="card"><span class="label">Pending Settlement</span><span class="value"><?=money($summary['total_pending'])?></span></div>
        <div class="card"><span class="label">Total Platform Fees</span><span class="value"><?=money($summary['total_fees'])?></span></div>
        <div class="card"><span class="label">Settlement Records</span><span class="value"><?=e(count($rows))?></span></div>
    </div>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Settlement Records</h2></div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>Settlement ID</th><th>Checkout ID</th><th>Date</th><th>Bill Amount</th><th>Platform Fee</th><th>Net Payable</th><th>Status</th><th>Settled Date</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr><td><strong><?=e($r['settlement_id'])?></strong></td><td><?=e($r['checkout_id'])?></td><td><?=e($r['created_at']??'—')?></td><td class="store-amount"><?=money($r['bill_amount'])?></td><td class="store-amount"><?=money($r['platform_fee'])?></td><td class="store-amount"><?=money($r['merchant_amount'])?></td><td><span class="store-status status-<?=e(strtolower($r['status']))?>"><?=e($r['status'])?></span></td><td><?=e($r['settled_at']??'—')?></td></tr><?php endforeach ?>
    <?php if(!$rows): ?><tr><td colspan="8" class="empty">No settlement records in this range.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
