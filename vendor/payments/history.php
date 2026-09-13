<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$vcode=$vendor['vendor_code'];
$pageTitle='Withdrawal History';
$pageSubtitle='All payout requests and their status';
require APP_ROOT.'/includes/vendor_header.php';
$pdo=db();
$rows=$pdo->query('SELECT id,amount,status,requested_at,paid_at FROM vendor_payout_requests WHERE vendor_id='.$vid.' ORDER BY requested_at DESC,id DESC')->fetchAll();
$summary=['total'=>0,'pending'=>0,'approved'=>0,'paid'=>0,'rejected'=>0];
foreach($rows as $r){
    $summary['total']+=(float)$r['amount'];
    $status=strtoupper((string)$r['status']);
    $key=in_array($status,['PENDING','UNDER_REVIEW'])?'pending':(in_array($status,['APPROVED','PROCESSING'])?'approved':($status==='PAID'?'paid':'rejected'));
    $summary[$key]+=(float)$r['amount'];
}
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vcode)?> · Payments</p>
    <h1>Withdrawal History</h1>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Summary</h2></div>
    <div class="grid stats">
        <div class="card"><span class="label">Total Requests</span><span class="value"><?=e(count($rows))?></span></div>
        <div class="card store-metric metric-orange"><h2>Pending</h2><strong><?=money($summary['pending'])?></strong></div>
        <div class="card store-metric metric-blue"><h2>Approved</h2><strong><?=money($summary['approved'])?></strong></div>
        <div class="card store-metric metric-green"><h2>Paid</h2><strong><?=money($summary['paid'])?></strong></div>
        <div class="card"><span class="label">Rejected</span><span class="value" style="color:#a13442"><?=money($summary['rejected'])?></span></div>
    </div>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Requests</h2></div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>Request ID</th><th>Date</th><th>Amount</th><th>Status</th><th>Processed</th><th>UTR / Reference</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr>
        <td><strong>#WD-<?=e($r['id'])?></strong></td>
        <td><?=e($r['requested_at'])?></td>
        <td class="store-amount"><?=money($r['amount'])?></td>
        <td><span class="store-status status-<?=e(strtolower(str_replace('UNDER_REVIEW','pending_review',str_replace('PROCESSING','processing',str_replace('PAID','paid',$r['status'])))))?>"><?=e($r['status'])?></span></td>
        <td><?=e($r['paid_at'] ?? '—')?></td>
        <td>—</td>
    </tr><?php endforeach ?>
    <?php if(!$rows): ?><tr><td colspan="6" class="empty">No withdrawal requests yet.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
