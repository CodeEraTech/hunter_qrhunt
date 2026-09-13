<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];

$pageTitle = 'Customer Payments';
require APP_ROOT.'/includes/vendor_header.php';

$perPage = 20;
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$offset = ($page - 1) * $perPage;

$where = ['c.merchant_id = ?'];
$params = [$vendorId];

if ($statusFilter !== '') {
    $where[] = 'c.status = ?';
    $params[] = $statusFilter;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM wallet_checkout_transactions c
    LEFT JOIN users u ON u.id = c.user_id
    LEFT JOIN merchant_settlements s ON s.checkout_id = c.checkout_id AND s.merchant_id = c.merchant_id
    WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$lastPage = max(1, (int) ceil($total / $perPage));

// Summaries
$summaryStmt = db()->prepare("SELECT
    COUNT(*) AS total_count,
    COALESCE(SUM(c.bill_amount), 0) AS total_amount,
    COALESCE(SUM(CASE WHEN c.status = 'SUCCESS' THEN c.bill_amount ELSE 0 END), 0) AS success_amount,
    COALESCE(SUM(CASE WHEN c.status = 'PENDING' THEN c.bill_amount ELSE 0 END), 0) AS pending_amount,
    COALESCE(SUM(CASE WHEN c.status IN ('FAILED','REVERSED') THEN c.bill_amount ELSE 0 END), 0) AS failed_amount
    FROM wallet_checkout_transactions c
    LEFT JOIN merchant_settlements s ON s.checkout_id = c.checkout_id AND s.merchant_id = c.merchant_id
    WHERE $whereSql");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

$rowsStmt = db()->prepare("SELECT c.checkout_id, c.bill_amount, c.status, c.created_at,
    u.name AS customer_name,
    s.settlement_id, s.platform_fee, s.merchant_amount, s.status AS settlement_status, s.settled_at
    FROM wallet_checkout_transactions c
    LEFT JOIN users u ON u.id = c.user_id
    LEFT JOIN merchant_settlements s ON s.checkout_id = c.checkout_id AND s.merchant_id = c.merchant_id
    WHERE $whereSql
    ORDER BY c.created_at DESC, c.id DESC
    LIMIT $perPage OFFSET $offset");
$rowsStmt->execute($params);
$rows = $rowsStmt->fetchAll();

function status_label(string $status): string
{
    return match ($status) {
        'PENDING' => 'Pending',
        'SUCCESS' => 'Success',
        'FAILED' => 'Failed',
        'REVERSED' => 'Reversed',
        'SETTLED' => 'Settled',
        default => ucfirst(strtolower(str_replace('_', ' ', $status))),
    };
}
?>

<section class="store-hero">
    <p class="store-eyebrow">Orders & Customers · Wallet checkout payments</p>
    <h1>Customer Payments</h1>
    <p>All wallet checkout payments for your store with settlement details and platform commission.</p>
</section>

<section class="store-panel">
    <div class="panel-head">
        <h2>Payments</h2>
        <form class="filters" method="get" action="">
            <select name="status" aria-label="Filter by payment status">
                <option value="">All Statuses</option>
                <option value="PENDING" <?= selected($statusFilter, 'PENDING') ?>>Pending</option>
                <option value="SUCCESS" <?= selected($statusFilter, 'SUCCESS') ?>>Success</option>
                <option value="FAILED" <?= selected($statusFilter, 'FAILED') ?>>Failed</option>
                <option value="REVERSED" <?= selected($statusFilter, 'REVERSED') ?>>Reversed</option>
            </select>
            <button type="submit" class="btn">Filter</button>
            <?php if ($statusFilter !== ''): ?>
                <a class="btn secondary" href="<?= e(app_url('/vendor/orders/payments.php')) ?>">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="store-metrics" style="margin-bottom:20px; grid-template-columns:repeat(4,minmax(0,1fr));">
        <article class="card store-metric">
            <h2>Total Payments</h2>
            <strong><?= money($summary['total_amount'] ?? 0) ?></strong>
            <p><?= (int) ($summary['total_count'] ?? 0) ?> transactions</p>
        </article>
        <article class="card store-metric metric-green">
            <h2>Successful</h2>
            <strong><?= money($summary['success_amount'] ?? 0) ?></strong>
            <p>Completed payments</p>
        </article>
        <article class="card store-metric metric-yellow">
            <h2>Pending</h2>
            <strong><?= money($summary['pending_amount'] ?? 0) ?></strong>
            <p>Awaiting completion</p>
        </article>
        <article class="card store-metric metric-red">
            <h2>Failed / Reversed</h2>
            <strong><?= money($summary['failed_amount'] ?? 0) ?></strong>
            <p>Unsuccessful payments</p>
        </article>
    </div>

    <div class="table-wrap">
        <table class="store-table store-payment-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                    <th>Platform Commission</th>
                    <th>Net Payable</th>
                    <th>Settlement ID</th>
                    <th>Settlement Status</th>
                    <th>Settled Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="9" class="empty">No payments found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><strong>#<?= e($r['checkout_id']) ?></strong></td>
                        <td><?= e($r['customer_name'] ?? '—') ?></td>
                        <td class="store-amount"> <?= money($r['bill_amount']) ?></td>
                        <td><span class="store-status status-<?= e(strtolower($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
                        <td class="store-amount"> <?= money($r['platform_fee'] ?? 0) ?></td>
                        <td class="store-amount"> <?= money($r['merchant_amount'] ?? 0) ?></td>
                        <td><?= e($r['settlement_id'] ?? '—') ?></td>
                        <td>
                            <?php if ($r['settlement_status']): ?>
                                <span class="store-status status-<?= e(strtolower($r['settlement_status'])) ?>"><?= e(status_label($r['settlement_status'])) ?></span>
                            <?php else: ?>
                                <span class="store-status">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($r['settled_at'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
        <div class="store-pagination">
            <span>Showing Page <?= $page ?> of <?= $lastPage ?> · <?= $total ?> payments</span>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= e(app_url('/vendor/orders/payments.php?page=' . ($page - 1) . ($statusFilter ? '&status=' . urlencode($statusFilter) : ''))) ?>">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($lastPage, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="badge" style="background:#3288d7;color:#fff;padding:7px 10px;border-radius:7px;margin:0 2px;"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= e(app_url('/vendor/orders/payments.php?page=' . $i . ($statusFilter ? '&status=' . urlencode($statusFilter) : ''))) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $lastPage): ?>
                    <a href="<?= e(app_url('/vendor/orders/payments.php?page=' . ($page + 1) . ($statusFilter ? '&status=' . urlencode($statusFilter) : ''))) ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
