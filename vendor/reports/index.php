<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
$vendor=vendor_user();
$vid=(int)$vendor['vendor_id'];
$from=trim((string)($_GET['from']??date('Y-m-01')));
$to=trim((string)($_GET['to']??date('Y-m-d')));
$export=isset($_GET['export'])&&$_GET['export']==='csv';
$pageTitle='Sales Report';
$pageSubtitle='Transaction sales summary with date filters';
require APP_ROOT.'/includes/vendor_header.php';
$pdo=db();
$sql='SELECT COUNT(*) total,COALESCE(SUM(bill_amount),0) amount,COALESCE(SUM(CASE WHEN status="SUCCESS" THEN bill_amount ELSE 0 END),0) success,COALESCE(SUM(CASE WHEN status="PENDING" THEN bill_amount ELSE 0 END),0) pending,COALESCE(SUM(CASE WHEN status="FAILED" THEN bill_amount ELSE 0 END),0) failed FROM wallet_checkout_transactions c WHERE c.merchant_id=? AND c.created_at>=? AND c.created_at<DATE_ADD(?,INTERVAL 1 DAY)';
$stmt=$pdo->prepare($sql);$stmt->execute([$vid,$from,$to]);$summary=$stmt->fetch();
$sql='SELECT c.checkout_id,c.bill_amount,c.status,c.created_at,u.name customer_name FROM wallet_checkout_transactions c LEFT JOIN users u ON u.id=c.user_id WHERE c.merchant_id=? AND c.created_at>=? AND c.created_at<DATE_ADD(?,INTERVAL 1 DAY) ORDER BY c.created_at DESC,c.id DESC LIMIT 100';
$stmt=$pdo->prepare($sql);$stmt->execute([$vid,$from,$to]);$rows=$stmt->fetchAll();
if($export){
    header('Content-Type:text/csv');
    header('Content-Disposition:attachment;filename=sales_report_'.$from.'_'.$to.'.csv');
    $out=fopen('php://output','w');
    fputcsv($out,['Transaction ID','Date','Customer','Amount','Status']);
    foreach($rows as $r) fputcsv($out,[$r['checkout_id'],$r['created_at'],$r['customer_name']??'—',$r['bill_amount'],$r['status']]);
    exit;
}
?>
<section class="store-hero">
    <p class="store-eyebrow"><?=e($vendor['vendor_code'])?> · Reports</p>
    <h1>Sales Report</h1>
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
        <div class="card"><span class="label">Total Transactions</span><span class="value"><?=e($summary['total'])?></span></div>
        <div class="card"><span class="label">Gross Sales</span><span class="value"><?=money($summary['amount'])?></span></div>
        <div class="card"><span class="label">Successful</span><span class="value" style="color:#256845"><?=money($summary['success'])?></span></div>
        <div class="card"><span class="label">Pending</span><span class="value" style="color:#825115"><?=money($summary['pending'])?></span></div>
        <div class="card"><span class="label">Failed</span><span class="value" style="color:#a13442"><?=money($summary['failed'])?></span></div>
    </div>
</section>
<section class="panel store-panel">
    <div class="panel-head"><h2>Transactions</h2></div>
    <div class="table-wrap"><table class="store-table"><thead><tr><th>ID</th><th>Date</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr><td><strong><?=e($r['checkout_id'])?></strong></td><td><?=e($r['created_at'])?></td><td><?=e($r['customer_name']??'—')?></td><td class="store-amount"><?=money($r['bill_amount'])?></td><td><?php $map=['SUCCESS'=>'approved','PENDING'=>'pending','FAILED'=>'rejected']; ?><span class="store-status status-<?=e($map[$r['status']]??strtolower($r['status']))?>"><?=e($r['status'])?></span></td></tr><?php endforeach ?>
    <?php if(!$rows): ?><tr><td colspan="5" class="empty">No transactions in this range.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
