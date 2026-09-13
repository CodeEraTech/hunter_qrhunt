<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('wallet.view');
require_once APP_ROOT.'/includes/finance_directory.php';

$sql = finance_transactions_sql();
$allowed = ['source'=>['WALLET','MASTER','SETTLEMENT','VENDOR_DEBIT'],
    'status'=>['SUCCESS','REVERSED','PENDING','FAILED','SETTLED']];
try { [$where,$params,$filters] = finance_filters($_GET,$allowed); } catch (InvalidArgumentException $e) { http_response_code(422); exit(e($e->getMessage())); }
$base = ' FROM ('.$sql.') directory'.$where;

// Export
if (input('export') !== '') {
    if (can('reports.export')) require_permission('reports.export');
    if (can('vendor_settlements.view')) require_permission('vendor_settlements.export');
    require_once APP_ROOT.'/includes/finance_export.php';
    $s = db()->prepare('SELECT *'.$base.' ORDER BY created_at DESC, source, id DESC');
    $s->execute($params);
    $exportRows = (static function() use ($s) { while ($r = $s->fetch()) yield [$r['code'],$r['created_at'],$r['party_id'],$r['party'],$r['mobile'],$r['source'],wallet_direction($r),$r['action'],$r['amount'],$r['previous_balance'],$r['new_balance'],$r['status'],$r['reference'],$r['note']]; })();
    finance_export('transactions',['Transaction ID','Date','User/Vendor ID','Name','Mobile','Ledger','Wallet','Credit/Debit','Event','Amount','Balance Before','Balance After','Status','Reference','Reason'],$exportRows,(string)input('export'));
}

// Summary stats for dashboard cards
$pdo = db();
$statStmt = $pdo->prepare('SELECT COUNT(*) total,COALESCE(SUM(CASE WHEN bucket="REAL" THEN amount END),0) real_amount,COALESCE(SUM(CASE WHEN bucket="BONUS" THEN amount END),0) bonus_amount,COALESCE(SUM(CASE WHEN bucket="PROMO" THEN amount END),0) promo_amount,COALESCE(SUM(CASE WHEN status="SUCCESS" THEN amount END),0) success,COALESCE(SUM(CASE WHEN status="PENDING" THEN amount END),0) pending,COALESCE(SUM(CASE WHEN status IN ("REVERSED","FAILED") THEN amount END),0) failed FROM wallet_transactions');
$statStmt->execute();
$stats = $statStmt->fetch();

// Pagination
$s = $pdo->prepare('SELECT COUNT(*)'.$base);
$s->execute($params);
$total = (int) $s->fetchColumn();
$page = max(1, min(max(1, (int) ceil($total/20)), page_int()));

$s = $pdo->prepare('SELECT *'.$base.' ORDER BY created_at DESC, source, id DESC LIMIT 20 OFFSET '.(($page-1)*20));
$s->execute($params);
$rows = $s->fetchAll();

$pageTitle = 'All Transactions';
$pageSubtitle = 'Global transaction directory - customer, vendor, bonus and master wallet activity';
require APP_ROOT.'/includes/header.php';
?>
<?php foreach (take_flashes() as $f): ?>
<div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div>
<?php endforeach; ?>

<!-- Summary Cards -->
<div class="grid stats" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px">
    <div class="card"><span class="label">Total Transactions</span><span class="value"><?=e(number_format($stats['total']))?></span></div>
    <div class="card"><span class="label">Real Cash Volume</span><span class="value" style="color:#256845"><?=money($stats['real_amount'])?></span></div>
    <div class="card"><span class="label">Bonus Volume</span><span class="value" style="color:#825115"><?=money($stats['bonus_amount'])?></span></div>
    <div class="card"><span class="label">Promo Volume</span><span class="value" style="color:#2781f6"><?=money($stats['promo_amount'])?></span></div>
    <div class="card"><span class="label">Successful</span><span class="value" style="color:#256845"><?=money($stats['success'])?></span></div>
    <div class="card"><span class="label">Pending</span><span class="value" style="color:#825115"><?=money($stats['pending'])?></span></div>
    <div class="card"><span class="label">Failed/Reversed</span><span class="value" style="color:#a13442"><?=money($stats['failed'])?></span></div>
</div>

<!-- All Transactions Directory -->
<section class="panel">
    <div class="panel-head">
        <div><h2>All Transactions</h2><p class="help">Global transaction directory with customer details, wallet/bonus amounts, transaction type, event, status, and customer profile access.</p></div>
        <?php if (can('reports.export') || can('vendor_settlements.export')): ?>
        <div class="actions">
            <a class="btn secondary" href="?<?=e(http_build_query($filters+['export'=>'csv']))?>">Export CSV</a>
            <a class="btn secondary" href="?<?=e(http_build_query($filters+['export'=>'xlsx']))?>">Export Excel</a>
        </div>
        <?php endif; ?>
    </div>

    <form class="filters" method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <input name="q" value="<?=e($filters['q'])?>" placeholder="Transaction ID, User/Vendor ID, mobile, reference" aria-label="Search" style="min-width:280px">
        <select name="source" aria-label="Ledger">
            <option value="">All Ledgers</option>
            <?php foreach ($allowed['source'] as $v): ?><option value="<?=$v?>" <?=selected($filters['source'],$v)?>><?=$v?></option><?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
            <option value="">All Statuses</option>
            <?php foreach ($allowed['status'] as $v): ?><option value="<?=$v?>" <?=selected($filters['status'],$v)?>><?=$v?></option><?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?=e($filters['from'])?>" aria-label="From date">
        <input type="date" name="to" value="<?=e($filters['to'])?>" aria-label="To date">
        <button class="btn" type="submit">Filter</button>
        <?php if ($filters['q']||$filters['source']||$filters['status']||$filters['from']||$filters['to']): ?>
        <a class="btn secondary" href="?">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap"><table>
        <thead>
            <tr>
                <th>Transaction / Date</th>
                <th>Customer / Vendor</th>
                <th>Ledger / Wallet</th>
                <th>Type / Event</th>
                <th>Amount</th>
                <th>Reference / Reason</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong><?=e($r['code'])?></strong><br><small><?=e($r['created_at'])?></small></td>
                <td>
                    <?=e($r['party'])?><br>
                    <small><?=e($r['party_id'])?> · <?=e($r['mobile']??'—')?></small>
                </td>
                <td><?=e($r['source'])?><br><?=e($r['pocket']??'—')?></td>
                <td><?=e(wallet_direction($r))?><br><small><?=e($r['action'])?></small></td>
                <td><?=money($r['amount'])?></td>
                <td>
                    <?=e($r['note']??'—')?><br>
                    <small><?=e($r['reference']??'—')?></small>
                </td>
                <td><span class="badge <?=e(strtolower($r['status']))?>"><?=e($r['status'])?></span></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn small secondary" href="<?=e(app_url('/admin/wallets/detail.php?source='.$r['source'].'&id='.$r['id']))?>">View Transaction</a>
                        <?php if ($r['source'] === 'WALLET'): ?>
                            <a class="btn small" href="<?=e(app_url('/admin/wallets/customer.php?id='.substr($r['party_id'],4)))?>">View Customer</a>
                        <?php elseif ($r['source'] === 'VENDOR_DEBIT'): ?>
                            <a class="btn small" href="<?=e(app_url('/admin/vendors/profile.php?id='.substr($r['party_id'],4)))?>">View Vendor</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="empty">No matching transactions.</td></tr><?php endif; ?>
        </tbody>
    </table></div>

    <?php
    require_once APP_ROOT.'/includes/finance_directory.php';
    finance_page_links($page,$total,$filters);
    ?>
</section>

<?php require APP_ROOT.'/includes/footer.php'; ?>