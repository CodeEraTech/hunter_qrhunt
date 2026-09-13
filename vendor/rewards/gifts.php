<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];
$pageTitle = 'Gift & Reward Codes';
$pageSubtitle = 'Gift redemptions processed at your store and inventory quota';
require APP_ROOT.'/includes/vendor_header.php';

$perPage = 20;
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$search = trim((string) ($_GET['q'] ?? ''));
$offset = ($page - 1) * $perPage;

$where = "WHERE gr.vendor_id = ?";
$params = [$vendorId];

if ($search !== '') {
    $where .= " AND (gr.redemption_id LIKE ? OR g.name LIKE ? OR u.name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM gift_redemptions gr
    JOIN gifts g ON g.id = gr.gift_id
    LEFT JOIN users u ON u.id = gr.user_id
    {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$lastPage = max(1, (int) ceil($total / $perPage));

$rowsStmt = db()->prepare("SELECT gr.redemption_id, gr.redeemed_at, gr.user_id, gr.gift_id,
    g.name AS gift_name, g.campaign, g.total_quantity, g.redeemed_quantity,
    (g.total_quantity - g.redeemed_quantity) AS remaining_qty,
    u.name AS customer_name
    FROM gift_redemptions gr
    JOIN gifts g ON g.id = gr.gift_id
    LEFT JOIN users u ON u.id = gr.user_id
    {$where}
    ORDER BY gr.redeemed_at DESC
    LIMIT $perPage OFFSET $offset");
$rowsStmt->execute($params);
$rows = $rowsStmt->fetchAll();

$totalStmt = db()->prepare("SELECT g.id, g.name, g.campaign, g.total_quantity, g.redeemed_quantity,
    (g.total_quantity - g.redeemed_quantity) AS remaining_qty,
    g.valid_from, g.valid_until, g.status
    FROM gifts g
    JOIN gift_redemptions gr ON gr.gift_id = g.id
    WHERE gr.vendor_id = ?
    GROUP BY g.id
    ORDER BY g.created_at DESC");
$totalStmt->execute([$vendorId]);
$giftQuotas = $totalStmt->fetchAll();

$attemptsStmt = db()->prepare("SELECT COUNT(*) FROM gift_redemption_attempts WHERE vendor_id = ?");
$attemptsStmt->execute([$vendorId]);
$totalAttempts = (int) $attemptsStmt->fetchColumn();

$failedStmt = db()->prepare("SELECT COUNT(*) FROM gift_redemption_attempts WHERE vendor_id = ? AND result = 'REJECTED'");
$failedStmt->execute([$vendorId]);
$failedAttempts = (int) $failedStmt->fetchColumn();
?>

<section class="store-hero">
    <p class="store-eyebrow">Rewards & Promotions · Gift codes redeemed at your store</p>
    <h1>Gift & Reward Codes</h1>
    <p>View all gift code redemptions processed by your store, with campaign inventory and quota tracking.</p>
</section>

<section class="store-metrics" aria-label="Gift redemptions summary">
    <article class="card store-metric metric-green">
        <h2>Total Redemptions</h2>
        <strong><?= $total ?></strong>
        <p>Gift codes successfully redeemed at your store</p>
    </article>
    <article class="card store-metric metric-blue">
        <h2>Redemption Attempts</h2>
        <strong><?= $totalAttempts ?></strong>
        <p>Total codes scanned/entered (successful + failed)</p>
    </article>
    <article class="card store-metric metric-red">
        <h2>Failed Attempts</h2>
        <strong><?= $failedAttempts ?></strong>
        <p>Invalid, expired, or exhausted code attempts</p>
    </article>
    <article class="card store-metric metric-teal">
        <h2>Active Campaigns</h2>
        <strong><?= count($giftQuotas) ?></strong>
        <p>Gift campaigns with redemptions at your store</p>
    </article>
</section>

<section class="panel store-panel" id="campaign-quotas" aria-labelledby="cq-heading">
    <div class="panel-head">
        <h2 id="cq-heading">Campaign Inventory & Quota</h2>
    </div>
    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Gift Name</th>
                    <th>Total Quantity</th>
                    <th>Redeemed (Global)</th>
                    <th>Remaining (Global)</th>
                    <th>Validity Window</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$giftQuotas): ?>
                    <tr><td colspan="7" class="empty">No gift campaigns with redemptions at your store yet.</td></tr>
                <?php else: foreach ($giftQuotas as $gq): ?>
                    <tr>
                        <td><?= e($gq['campaign'] ?? '—') ?></td>
                        <td><strong><?= e($gq['name']) ?></strong></td>
                        <td><?= (int) $gq['total_quantity'] ?></td>
                        <td><?= (int) $gq['redeemed_quantity'] ?></td>
                        <td class="store-amount"><?= (int) $gq['remaining_qty'] ?></td>
                        <td>
                            <small>
                                <?= $gq['valid_from'] ? e(date('M j, Y', strtotime($gq['valid_from']))) : 'No start' ?> -
                                <?= $gq['valid_until'] ? e(date('M j, Y', strtotime($gq['valid_until']))) : 'No end' ?>
                            </small>
                        </td>
                        <td>
                            <span class="store-status status-<?= e(strtolower($gq['status'])) ?>">
                                <?= e(ucfirst(strtolower($gq['status']))) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel store-panel" id="redemption-history" aria-labelledby="rh-heading">
    <div class="panel-head">
        <h2 id="rh-heading">Redemption History</h2>
        <form class="filters" method="get" action="">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search redemption ID, gift name, or customer" aria-label="Search redemptions">
            <button type="submit" class="btn">Search</button>
            <?php if ($search): ?>
                <a href="<?= e(app_url('/vendor/rewards/gifts.php')) ?>" class="btn secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>Redemption ID</th>
                    <th>Gift / Campaign</th>
                    <th>Customer</th>
                    <th>Date & Time</th>
                    <th>Remaining Inventory (at redemption)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="5" class="empty">No gift redemptions found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><strong>#<?= e($r['redemption_id']) ?></strong></td>
                        <td><?= e($r['gift_name']) ?><?php if ($r['campaign']): ?> (<?= e($r['campaign']) ?>)<?php endif; ?></td>
                        <td><?= e($r['customer_name'] ?? 'User #' . $r['user_id']) ?></td>
                        <td><?= e($r['redeemed_at']) ?></td>
                        <td class="store-amount"><?= (int) $r['remaining_qty'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
    <div class="store-pagination">
        <span>Showing Page <?= $page ?> of <?= $lastPage ?> · <?= $total ?> redemptions</span>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= e(app_url('/vendor/rewards/gifts.php?page=' . ($page - 1) . ($search ? '&q=' . urlencode($search) : ''))) ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($lastPage, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="badge" style="background:#3288d7;color:#fff;padding:7px 10px;border-radius:7px;margin:0 2px;"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e(app_url('/vendor/rewards/gifts.php?page=' . $i . ($search ? '&q=' . urlencode($search) : ''))) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $lastPage): ?>
                <a href="<?= e(app_url('/vendor/rewards/gifts.php?page=' . ($page + 1) . ($search ? '&q=' . urlencode($search) : ''))) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>