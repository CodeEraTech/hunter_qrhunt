<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];
$pageTitle = 'Qoin / Reward Offers';
$pageSubtitle = 'Active reward campaigns and gift inventory for your store';
require APP_ROOT.'/includes/vendor_header.php';

$perPage = 20;
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$offset = ($page - 1) * $perPage;

$totalStmt = db()->prepare("SELECT COUNT(*) FROM gifts WHERE status = 'ACTIVE' AND (valid_until IS NULL OR valid_until >= NOW())");
$totalStmt->execute();
$totalGifts = (int) $totalStmt->fetchColumn();

$totalStmt2 = db()->prepare("SELECT COUNT(*) FROM reward_codes rc JOIN gifts g ON g.id = rc.gift_id WHERE g.status = 'ACTIVE' AND rc.status = 'ACTIVE' AND (g.valid_until IS NULL OR g.valid_until >= NOW())");
$totalStmt2->execute();
$totalActiveCodes = (int) $totalStmt2->fetchColumn();

$giftsStmt = db()->prepare("SELECT g.id, g.name, g.description, g.image_path, g.campaign, g.total_quantity, g.redeemed_quantity,
    g.valid_from, g.valid_until, g.status, g.created_at,
    (g.total_quantity - g.redeemed_quantity) AS remaining_qty
    FROM gifts g
    WHERE g.status = 'ACTIVE' AND (g.valid_until IS NULL OR g.valid_until >= NOW())
    ORDER BY g.created_at DESC
    LIMIT $perPage OFFSET $offset");
$giftsStmt->execute();
$gifts = $giftsStmt->fetchAll();

$codesStmt = db()->prepare("SELECT rc.id, rc.code_reference, rc.status, rc.expires_at, rc.created_at,
    g.name AS gift_name, g.campaign, g.total_quantity, g.redeemed_quantity
    FROM reward_codes rc
    JOIN gifts g ON g.id = rc.gift_id
    WHERE g.status = 'ACTIVE' AND rc.status = 'ACTIVE' AND (g.valid_until IS NULL OR g.valid_until >= NOW())
    ORDER BY rc.created_at DESC
    LIMIT $perPage OFFSET $offset");
$codesStmt->execute();
$codes = $codesStmt->fetchAll();

$recentRedemptionsStmt = db()->prepare("SELECT gr.redemption_id, gr.redeemed_at, g.name AS gift_name, gr.user_id, u.name AS customer_name
    FROM gift_redemptions gr
    JOIN gifts g ON g.id = gr.gift_id
    LEFT JOIN users u ON u.id = gr.user_id
    WHERE gr.vendor_id = ?
    ORDER BY gr.redeemed_at DESC
    LIMIT 10");
$recentRedemptionsStmt->execute([$vendorId]);
$recentRedemptions = $recentRedemptionsStmt->fetchAll();

$lastPage = max(1, (int) ceil(max($totalGifts, $totalActiveCodes) / $perPage));
?>

<section class="store-hero">
    <p class="store-eyebrow">Rewards & Promotions · Active campaigns and inventory</p>
    <h1>Qoin / Reward Offers</h1>
    <p>View active gift campaigns, available reward codes, and recent redemption activity for your store.</p>
</section>

<section class="store-metrics" aria-label="Reward offers summary">
    <article class="card store-metric metric-green">
        <h2>Active Gift Campaigns</h2>
        <strong><?= $totalGifts ?></strong>
        <p>Gift campaigns currently active and valid</p>
    </article>
    <article class="card store-metric metric-blue">
        <h2>Available Reward Codes</h2>
        <strong><?= $totalActiveCodes ?></strong>
        <p>Active reward codes across all campaigns</p>
    </article>
    <article class="card store-metric metric-teal">
        <h2>Total Inventory</h2>
        <strong><?= array_sum(array_column($gifts, 'total_quantity')) ?></strong>
        <p>Total gift quantity across active campaigns</p>
    </article>
    <article class="card store-metric metric-yellow">
        <h2>Remaining Inventory</h2>
        <strong><?= array_sum(array_column($gifts, 'remaining_qty')) ?></strong>
        <p>Available gift codes ready for redemption</p>
    </article>
</section>

<section class="panel store-panel" id="gift-campaigns" aria-labelledby="gc-heading">
    <div class="panel-head">
        <h2 id="gc-heading">Active Gift Campaigns</h2>
        <?php if ($lastPage > 1): ?>
        <a class="btn secondary" href="<?= e(app_url('/vendor/rewards/index.php?page=' . ($page + 1))) ?>">Next page</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Gift Name</th>
                    <th>Total Qty</th>
                    <th>Redeemed</th>
                    <th>Remaining</th>
                    <th>Validity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$gifts): ?>
                    <tr><td colspan="7" class="empty">No active gift campaigns found.</td></tr>
                <?php else: foreach ($gifts as $gift): ?>
                    <tr>
                        <td><?= e($gift['campaign'] ?? '—') ?></td>
                        <td><strong><?= e($gift['name']) ?></strong><?php if ($gift['description']): ?><br><small><?= e($gift['description']) ?></small><?php endif; ?></td>
                        <td><?= (int) $gift['total_quantity'] ?></td>
                        <td><?= (int) $gift['redeemed_quantity'] ?></td>
                        <td class="store-amount"><?= (int) $gift['remaining_qty'] ?></td>
                        <td>
                            <small>
                                <?= $gift['valid_from'] ? e(date('M j, Y', strtotime($gift['valid_from']))) : 'No start' ?> -
                                <?= $gift['valid_until'] ? e(date('M j, Y', strtotime($gift['valid_until']))) : 'No end' ?>
                            </small>
                        </td>
                        <td>
                            <span class="store-status status-<?= e(strtolower($gift['status'])) ?>">
                                <?= e(ucfirst(strtolower($gift['status']))) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel store-panel" id="reward-codes" aria-labelledby="rc-heading">
    <div class="panel-head">
        <h2 id="rc-heading">Available Reward Codes</h2>
        <?php if ($lastPage > 1): ?>
        <a class="btn secondary" href="<?= e(app_url('/vendor/rewards/index.php?page=' . ($page + 1))) ?>">Next page</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>Code Reference</th>
                    <th>Gift Campaign</th>
                    <th>Status</th>
                    <th>Expires</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$codes): ?>
                    <tr><td colspan="5" class="empty">No active reward codes found.</td></tr>
                <?php else: foreach ($codes as $code): ?>
                    <tr>
                        <td><code><?= e($code['code_reference']) ?></code></td>
                        <td><?= e($code['gift_name']) ?><?php if ($code['campaign']): ?> (<?= e($code['campaign']) ?>)<?php endif; ?></td>
                        <td><span class="store-status status-<?= e(strtolower($code['status'])) ?>"><?= e(ucfirst(strtolower($code['status']))) ?></span></td>
                        <td><?= $code['expires_at'] ? e(date('M j, Y H:i', strtotime($code['expires_at']))) : '—' ?></td>
                        <td><?= e(date('M j, Y H:i', strtotime($code['created_at']))) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($recentRedemptions): ?>
<section class="panel store-panel" id="recent-redemptions" aria-labelledby="rr-heading">
    <div class="panel-head">
        <h2 id="rr-heading">Recent Redemptions at Your Store</h2>
    </div>
    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>Redemption ID</th>
                    <th>Gift</th>
                    <th>Customer</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentRedemptions as $r): ?>
                    <tr>
                        <td><strong>#<?= e($r['redemption_id']) ?></strong></td>
                        <td><?= e($r['gift_name']) ?></td>
                        <td><?= e($r['customer_name'] ?? 'User #' . $r['user_id']) ?></td>
                        <td><?= e($r['redeemed_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>