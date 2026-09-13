<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];
$pageTitle = 'Coupons';
$pageSubtitle = 'Store coupons and voucher codes assigned to your store';
require APP_ROOT.'/includes/vendor_header.php';

$perPage = 20;
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = $_GET['status'] ?? '';
$offset = ($page - 1) * $perPage;

$where = "WHERE c.merchant_id = ?";
$params = [$vendorId];

if ($search !== '') {
    $where .= " AND (c.code_reference LIKE ? OR c.title LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($statusFilter !== '') {
    $where .= " AND c.status = ?";
    $params[] = $statusFilter;
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM coupons c {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$lastPage = max(1, (int) ceil($total / $perPage));

$rowsStmt = db()->prepare("SELECT c.id, c.code_reference, c.title, c.discount_amount, c.min_cart_amount,
    c.ttl_minutes, c.expires_at, c.status, c.user_id, c.used_at, c.created_at,
    u.name AS customer_name
    FROM coupons c
    LEFT JOIN users u ON u.id = c.user_id
    {$where}
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset");
$rowsStmt->execute($params);
$rows = $rowsStmt->fetchAll();

$statusOptions = ['ACTIVE', 'USED', 'EXPIRED', 'CANCELLED'];
?>

<section class="store-hero">
    <p class="store-eyebrow">Rewards & Promotions · Store coupons and vouchers</p>
    <h1>Coupons</h1>
    <p>View and track all coupon codes assigned to your store, including usage status and customer details.</p>
</section>

<section class="store-panel">
    <div class="panel-head">
        <h2>Store Coupons</h2>
        <form class="filters" method="get" action="">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search code reference or title" aria-label="Search coupons">
            <select name="status" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <?php foreach ($statusOptions as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= selected($statusFilter, $opt) ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Filter</button>
            <?php if ($search || $statusFilter): ?>
                <a href="<?= e(app_url('/vendor/rewards/coupons.php')) ?>" class="btn secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>Code Reference</th>
                    <th>Title</th>
                    <th>Discount</th>
                    <th>Min Cart</th>
                    <th>Status</th>
                    <th>Customer</th>
                    <th>Used At</th>
                    <th>Expires</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="9" class="empty">No coupons found for your store.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><code><?= e($r['code_reference']) ?></code></td>
                        <td><?= e($r['title']) ?></td>
                        <td class="store-amount"><?= money($r['discount_amount']) ?></td>
                        <td class="store-amount"><?= money($r['min_cart_amount']) ?></td>
                        <td><span class="store-status status-<?= e(strtolower($r['status'])) ?>"><?= e($r['status']) ?></span></td>
                        <td><?= e($r['customer_name'] ?? '—') ?><?php if ($r['user_id']): ?> (ID: <?= (int)$r['user_id'] ?>)<?php endif; ?></td>
                        <td><?= $r['used_at'] ? e($r['used_at']) : '—' ?></td>
                        <td><?= $r['expires_at'] ? e(date('M j, Y H:i', strtotime($r['expires_at']))) : ($r['ttl_minutes'] ? 'TTL: ' . (int)$r['ttl_minutes'] . ' min' : '—') ?></td>
                        <td><?= e(date('M j, Y H:i', strtotime($r['created_at']))) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
    <div class="store-pagination">
        <span>Showing Page <?= $page ?> of <?= $lastPage ?> · <?= $total ?> coupons</span>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= e(app_url('/vendor/rewards/coupons.php?page=' . ($page - 1) . ($search ? '&q=' . urlencode($search) : '') . ($statusFilter ? '&status=' . urlencode($statusFilter) : ''))) ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($lastPage, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="badge" style="background:#3288d7;color:#fff;padding:7px 10px;border-radius:7px;margin:0 2px;"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e(app_url('/vendor/rewards/coupons.php?page=' . $i . ($search ? '&q=' . urlencode($search) : '') . ($statusFilter ? '&status=' . urlencode($statusFilter) : ''))) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $lastPage): ?>
                <a href="<?= e(app_url('/vendor/rewards/coupons.php?page=' . ($page + 1) . ($search ? '&q=' . urlencode($search) : '') . ($statusFilter ? '&status=' . urlencode($statusFilter) : ''))) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>