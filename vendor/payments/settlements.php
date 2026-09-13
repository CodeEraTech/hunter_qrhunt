<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$vcode=$vendor['vendor_code'];
$pageTitle='Settlement History';
$pageSubtitle='Merchant settlement records';
require APP_ROOT.'/includes/vendor_header.php';
$pdo=db();
$rows=$pdo->query('SELECT s.settlement_id,s.checkout_id,s.bill_amount,s.platform_fee,s.merchant_amount,s.status,s.settled_at FROM merchant_settlements s WHERE s.merchant_id='.$vid.' ORDER BY s.id DESC')->fetchAll();
$summary=['total'=>0,'settled'=>0,'pending'=>0,'reversed'=>0,'fees'=>0];
foreach($rows as $r){
    $summary['total']+=(float)$r['bill_amount'];
    $summary['fees']+=(float)$r['platform_fee'];
    if($r['status']==='SETTLED')$summary['settled']+=(float)$r['merchant_amount'];
    elseif($r['status']==='PENDING')$summary['pending']+=(float)$r['bill_amount'];
    elseif($r['status']==='REVERSED')$summary['reversed']+=(float)$r['bill_amount'];
}
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vcode)?> · Payments</p>
    <h1>Settlement History</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Summary</h2></div>
    <div class="grid stats">
        <div class="card"><span class="label">Total Settlements</span><span class="value"><?=e(count($rows))?></span></div>
        <div class="card store-metric metric-green"><h2>Total Settled</h2><strong><?=money($summary['settled'])?></strong></div>
        <div class="card store-metric metric-orange"><h2>Pending</h2><strong><?=money($summary['pending'])?></strong></div>
        <div class="card"><span class="label">Reversed</span><span class="value" style="color:#a13442"><?=money($summary['reversed'])?></span></div>
        <div class="card"><span class="label">Platform Fees Earned</span><span class="value"><?=money($summary['fees'])?></span></div>
    </div>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Settlements</h2></div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>Settlement ID</th><th>Checkout ID</th><th>Bill Amount</th><th>Platform Fee</th><th>Net Payable</th><th>Status</th><th>Settled Date</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr>
        <td><strong><?=e($r['settlement_id'])?></strong></td>
        <td><?=e($r['checkout_id'])?></td>
        <td class="store-amount"><?=money($r['bill_amount'])?></td>
        <td class="store-amount"><?=money($r['platform_fee'])?></td>
        <td class="store-amount"><?=money($r['merchant_amount'])?></td>
        <td><span class="store-status status-<?=e(strtolower($r['status']))?>"><?=e($r['status'])?></span></td>
        <td><?=e($r['settled_at'] ?? '—')?></td>
    </tr><?php endforeach ?>
    <?php if(!$rows): ?><tr><td colspan="7" class="empty">No settlement records yet.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
