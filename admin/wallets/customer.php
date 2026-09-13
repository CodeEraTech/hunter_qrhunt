<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_permission('wallet.view');
require_once APP_ROOT.'/includes/wallet_console.php';
require_once APP_ROOT.'/includes/finance_directory.php';

$id = filter_var(input('id'), FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Customer not found'); }

$s = db()->prepare('SELECT id,name,email,mobile,is_verified,status,risk_level,created_at FROM users WHERE id=?');
$s->execute([$id]);
$user = $s->fetch();
if (!$user) { http_response_code(404); exit('Customer not found'); }

$s = db()->prepare('SELECT * FROM wallets WHERE user_id=?');
$s->execute([$id]);
$wallet = $s->fetch();
if (!$wallet) { http_response_code(404); exit('Wallet not found'); }

$s = db()->prepare('SELECT COUNT(*) FROM user_devices WHERE user_id=? AND status="ACTIVE"');
$s->execute([$id]);
$activeDevices = (int) $s->fetchColumn();

$s = db()->prepare('SELECT * FROM user_devices WHERE user_id=? AND status="ACTIVE" ORDER BY last_active_at DESC');
$s->execute([$id]);
$devices = $s->fetchAll();

// Active coupons
$s = db()->prepare("SELECT c.code_reference, c.title, c.discount_amount, c.min_cart_amount, c.expires_at, c.status FROM coupons c WHERE c.user_id=? AND c.status IN ('ACTIVE','USED') ORDER BY c.created_at DESC");
$s->execute([$id]);
$coupons = $s->fetchAll();

// Rewards/Q-Coins earned
$s = db()->prepare("SELECT r.reward_id, r.amount, r.status, r.created_at, h.title hunt_title FROM rewards r JOIN hunts h ON h.id=r.hunt_id WHERE r.user_id=? ORDER BY r.created_at DESC LIMIT 20");
$s->execute([$id]);
$rewards = $s->fetchAll();

// Gift redemptions
$s = db()->prepare("SELECT gr.redemption_id, g.name gift_name, gr.redeemed_at FROM gift_redemptions gr JOIN gifts g ON g.id=gr.gift_id WHERE gr.user_id=? ORDER BY gr.redeemed_at DESC LIMIT 10");
$s->execute([$id]);
$giftRedemptions = $s->fetchAll();

// Security events
$s = db()->prepare("SELECT * FROM security_events WHERE user_id=? ORDER BY id DESC LIMIT 10");
$s->execute([$id]);
$securityEvents = $s->fetchAll();

// Filters for passbook
$q = trim((string) input('q'));
$pocket = (string) input('pocket');
$from = (string) input('from');
$to = (string) input('to');
$where = ['t.user_id=?'];
$params = [$id];

if ($q !== '') {
    $where[] = '(t.transaction_id LIKE ? OR t.reference LIKE ? OR t.description LIKE ?)';
    array_push($params, ...array_fill(0, 3, '%'.$q.'%'));
}
if ($pocket !== '') {
    if (!in_array($pocket, ['REAL','BONUS','PROMO'], true)) { http_response_code(422); exit('Invalid wallet filter'); }
    $where[] = 't.bucket=?';
    $params[] = $pocket;
}
foreach (['from'=>$from,'to'=>$to] as $key=>$date) if ($date !== '') {
    if (!valid_date($date)) { http_response_code(422); exit('Invalid date'); }
    $where[] = 't.created_at'.($key==='from'?'>=?':'<DATE_ADD(?,INTERVAL 1 DAY)');
    $params[] = $date;
}
if ($from!=='' && $to!=='' && $from>$to) { http_response_code(422); exit('Invalid date range'); }

$base = ' FROM wallet_transactions t LEFT JOIN admins a ON a.id=t.created_by WHERE '.implode(' AND ', $where);
$filters = compact('id','q','pocket','from','to');

// Export
if (input('export') !== '') {
    require_permission('reports.export');
    require_once APP_ROOT.'/includes/finance_export.php';
    $s = db()->prepare('SELECT t.*,a.name admin_name'.$base.' ORDER BY t.id DESC');
    $s->execute($params);
    $rows = (static function() use ($s) { while ($r=$s->fetch()) yield [$r['transaction_id'],$r['created_at'],$r['bucket'],wallet_direction($r),$r['amount'],$r['previous_balance'],$r['new_balance'],$r['status'],$r['reference'],$r['description'],$r['admin_name']??'System/API']; })();
    finance_export('customer-'.$id.'-passbook',['Transaction ID','Date','Wallet','Credit/Debit','Amount','Balance Before','Balance After','Status','Reference','Reason','Recorded By'],$rows,(string)input('export'));
}

// Pagination for passbook
$s = db()->prepare('SELECT COUNT(*)'.$base);
$s->execute($params);
$total = (int) $s->fetchColumn();
$page = max(1, min(max(1, (int) ceil($total/20)), page_int()));

$s = db()->prepare('SELECT t.*,a.name admin_name'.$base.' ORDER BY t.id DESC LIMIT 20 OFFSET '.(($page-1)*20));
$s->execute($params);
$txRows = $s->fetchAll();

$pageTitle = 'Customer Wallet · USR_'.$id;
$pageSubtitle = 'Lifetime real/bonus/promo balances, coupons, Q-Coins, security & complete transaction history';
require APP_ROOT.'/includes/header.php';
?>
<?php foreach (take_flashes() as $f): ?>
<div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div>
<?php endforeach; ?>

<!-- Customer Profile Header -->
<div class="customer-profile-grid" style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px">
    <section class="panel">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
            <div>
                <h2><?=e($user['name'])?> · USR_<?=e($id)?></h2>
                <p style="margin:4px 0"><?=e($user['email']??'')?> · <?=e($user['mobile'])?></p>
                <p style="margin:0;font-size:12px;color:var(--muted)">Joined: <?=e($user['created_at'])?></p>
            </div>
            <div style="display:flex;gap:8px">
                <?php if (can('wallet.credit') || can('wallet.debit')): ?>
                <a class="btn secondary" href="<?=e(app_url('/admin/wallets/adjust.php?customer=USR_'.$id))?>">Adjust Wallet</a>
                <?php endif; ?>
                <a class="btn secondary" href="<?=e(app_url('/admin/wallets/index.php'))?>">All Transactions</a>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px">
            <div class="kv"><span>Account Verification</span><strong><?=$user['is_verified']?'<span class="badge success">Verified</span>':'<span class="badge warning">Pending KYC</span>'?></strong></div>
            <div class="kv"><span>Account Status</span><strong><?=e($user['status'])?></strong></div>
            <div class="kv"><span>Wallet Status</span><strong><?=e($wallet['status'])?></strong></div>
            <div class="kv"><span>User Risk Level</span><strong class="risk <?=strtolower($user['risk_level'])?>"><?=e($user['risk_level'])?></strong></div>
        </div>

        <div class="kv"><span>Active Devices</span><strong><?=e($activeDevices)?></strong></div>

        <!-- Security Events -->
        <?php if ($securityEvents): ?>
        <div style="margin-top:16px">
            <h3 style="margin-bottom:8px">Recent Security Events</h3>
            <?php foreach ($securityEvents as $ev): ?>
            <div class="kv"><span class="risk <?=strtolower($ev['risk_level'])?>"><?=e($ev['risk_level'])?></span><strong><?=e($ev['event_type'])?> · <?=e($ev['created_at'])?></strong></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Wallet Balance Cards -->
    <section class="panel finance-pools" style="display:flex;flex-direction:column;gap:16px">
        <h2>Wallet Balances</h2>
        <article class="finance-pool pool-real">
            <h2>Real Cash</h2>
            <strong><?=money($wallet['balance'])?></strong>
            <small>Withdrawable balance</small>
        </article>
        <article class="finance-pool pool-bonus">
            <h2>Bonus Balance</h2>
            <strong><?=money($wallet['bonus_balance'])?></strong>
            <small>Includes Q-Coins & promotional credits</small>
        </article>
        <article class="finance-pool pool-vendor">
            <h2>Promo Balance</h2>
            <strong><?=money($wallet['promo_balance'])?></strong>
            <small>Campaign-specific credits</small>
        </article>
    </section>
</div>

<!-- Coupons & Q-Coins Section -->
<div class="grid" style="grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
    <!-- Active Coupons -->
    <section class="panel">
        <h2>Active Coupons (<?=e(count($coupons))?>)</h2>
        <?php if ($coupons): ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Code</th><th>Title</th><th>Discount</th><th>Min Cart</th><th>Expires</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($coupons as $c): ?>
            <tr>
                <td><code><?=e($c['code_reference'])?></code></td>
                <td><?=e($c['title'])?></td>
                <td><?=money($c['discount_amount'])?></td>
                <td><?=money($c['min_cart_amount'])?></td>
                <td><?=e($c['expires_at'] ?? '—')?></td>
                <td><span class="badge <?=strtolower($c['status'])?>"><?=e($c['status'])?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php else: ?><p class="empty">No active coupons.</p><?php endif; ?>
    </section>

    <!-- Q-Coins / Rewards -->
    <section class="panel">
        <h2>Q-Coins / Rewards Earned</h2>
        <?php if ($rewards): ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Reward ID</th><th>Hunt</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rewards as $r): ?>
            <tr>
                <td><code><?=e($r['reward_id'])?></code></td>
                <td><?=e($r['hunt_title'])?></td>
                <td><?=money($r['amount'])?></td>
                <td><span class="badge <?=strtolower($r['status'])?>"><?=e($r['status'])?></span></td>
                <td><?=e($r['created_at'])?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php else: ?><p class="empty">No Q-Coins earned yet.</p><?php endif; ?>
    </section>
</div>

<!-- Gift Redemptions -->
<?php if ($giftRedemptions): ?>
<div class="panel" style="margin-bottom:24px">
    <h2>Gift Redemptions (<?=e(count($giftRedemptions))?>)</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>Redemption ID</th><th>Gift</th><th>Redeemed At</th></tr></thead>
        <tbody>
        <?php foreach ($giftRedemptions as $gr): ?>
        <tr><td><code><?=e($gr['redemption_id'])?></code></td><td><?=e($gr['gift_name'])?></td><td><?=e($gr['redeemed_at'])?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

<!-- Device Status -->
<div class="panel" style="margin-bottom:24px">
    <h2>Registered Devices (<?=e(count($devices))?>)</h2>
    <?php if ($devices): ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Device</th><th>Platform</th><th>OS</th><th>IP Address</th><th>Last Active</th></tr></thead>
        <tbody>
        <?php foreach ($devices as $d): ?>
        <tr>
            <td><?=e($d['device_name'] ?? '—')?></td>
            <td><?=e($d['platform'] ?? '—')?></td>
            <td><?=e($d['os_version'] ?? '—')?></td>
            <td><?=e($d['ip_address'] ?? '—')?></td>
            <td><?=e($d['last_active_at'] ?? '—')?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php else: ?><p class="empty">No active devices.</p><?php endif; ?>
</div>

<!-- Customer Transaction History (Passbook) -->
<section class="panel">
    <div class="panel-head">
        <h2>Customer Transaction History (Passbook)</h2>
        <div class="actions">
            <a class="btn secondary" href="<?=e(app_url('/admin/wallets/index.php'))?>">Global Ledger</a>
            <?php if (can('reports.export')): ?>
            <a class="btn secondary" href="?<?=e(http_build_query($filters+['export'=>'csv']))?>">Export CSV</a>
            <a class="btn secondary" href="?<?=e(http_build_query($filters+['export'=>'xlsx']))?>">Export Excel</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="filters" method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <input type="hidden" name="id" value="<?=e($id)?>">
        <input name="q" value="<?=e($q)?>" placeholder="Transaction ID, reference or reason" aria-label="Search passbook" style="min-width:280px">
        <select name="pocket" aria-label="Wallet">
            <option value="">All Wallets</option>
            <?php foreach (['REAL','BONUS','PROMO'] as $b): ?><option <?=selected($pocket,$b)?>><?=$b?></option><?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?=e($from)?>" aria-label="From date">
        <input type="date" name="to" value="<?=e($to)?>" aria-label="To date">
        <button class="btn">Filter</button>
        <?php if ($q||$pocket||$from||$to): ?><a class="btn secondary" href="?id=<?=$id?>">Clear</a><?php endif; ?>
    </form>

    <div class="table-wrap"><table class="customer-ledger">
        <thead>
            <tr>
                <th>Transaction / Date</th>
                <th>Wallet</th>
                <th>Credit / Debit</th>
                <th>Amount</th>
                <th>Before → After</th>
                <th>Reason / Reference</th>
                <th>Status</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($txRows as $r):
            $direction = wallet_direction($r);
        ?>
            <tr>
                <td>
                    <a href="<?=e(app_url('/admin/wallets/detail.php?source=WALLET&id='.$r['id']))?>"><?=e($r['transaction_id'])?></a><br>
                    <small><?=e($r['created_at'])?></small>
                </td>
                <td><?=e($r['bucket'])?></td>
                <td><span class="type-pill <?=strtolower($direction)?>"><?=$direction?></span></td>
                <td class="<?=$direction==='DEBIT'?'ledger-negative':'ledger-positive'?>"><?=$direction==='DEBIT'?'-':'+'?><?=money($r['amount'])?></td>
                <td><?=money($r['previous_balance'])?> → <?=money($r['new_balance'])?></td>
                <td><?=e($r['description'])?><br><small><?=e($r['reference'])?></small></td>
                <td><?=e($r['status'])?></td>
                <td><?=e($r['admin_name']??'System/API')?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$txRows): ?><tr><td colspan="8" class="empty">No matching transactions.</td></tr><?php endif; ?>
        </tbody>
    </table></div>

    <?php
    require_once APP_ROOT.'/includes/finance_directory.php';
    finance_page_links($page,$total,$filters);
    ?>
</section>

<?php require APP_ROOT.'/includes/footer.php'; ?>