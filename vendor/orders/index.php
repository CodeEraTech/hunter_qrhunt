<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];

$pageTitle = 'Orders';
require APP_ROOT.'/includes/vendor_header.php';

$perPage = 20;
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$search = trim((string) ($_GET['q'] ?? ''));
$offset = ($page - 1) * $perPage;

$like = "%{$search}%";
$countStmt = db()->prepare("SELECT COUNT(*) FROM wallet_checkout_transactions c
    LEFT JOIN users u ON u.id = c.user_id
    WHERE c.merchant_id = ? AND (c.checkout_id LIKE ? OR u.name LIKE ?)");
$countStmt->execute([$vendorId, $like, $like]);
$total = (int) $countStmt->fetchColumn();
$lastPage = max(1, (int) ceil($total / $perPage));

$rowsStmt = db()->prepare("SELECT c.checkout_id, c.bill_amount, c.status, c.created_at,
    u.name AS customer_name, c.bonus_used, c.promo_used
    FROM wallet_checkout_transactions c
    LEFT JOIN users u ON u.id = c.user_id
    WHERE c.merchant_id = ? AND (c.checkout_id LIKE ? OR u.name LIKE ?)
    ORDER BY c.created_at DESC, c.id DESC
    LIMIT $perPage OFFSET $offset");
$rowsStmt->execute([$vendorId, $like, $like]);
$rows = $rowsStmt->fetchAll();

function status_label(string $status): string
{
    return match ($status) {
        'PENDING' => 'Pending',
        'SUCCESS' => 'Success',
        'FAILED' => 'Failed',
        'REVERSED' => 'Reversed',
        default => ucfirst(strtolower(str_replace('_', ' ', $status))),
    };
}
?>

<section class="store-hero">
    <p class="store-eyebrow">Orders & Customers · Customer checkout ledger</p>
    <h1>Orders</h1>
    <p>All customer orders placed against your store, with payment status and settlement reference.</p>
</section>

<section class="store-panel">
    <div class="panel-head">
        <h2>Customer Orders</h2>
        <form class="filters" method="get" action="">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search order ID or customer name" aria-label="Search orders">
            <button type="submit" class="btn">Search</button>
        </form>
    </div>

    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Gross Bill</th>
                    <th>Payment Status</th>
                    <th>QR / Settlement Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="empty">No orders found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><a href="<?= e(app_url('/vendor/orders/view.php?checkout_id=' . urlencode($r['checkout_id']))) ?>">#<?= e($r['checkout_id']) ?></a></strong></td>
                        <td><?= e($r['customer_name'] ?? '—') ?></td>
                        <td><?= e($r['created_at']) ?></td>
                        <td class="store-amount"> <?= money($r['bill_amount']) ?></td>
                        <td><span class="store-status status-<?= e(strtolower($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
                        <td><small>QR: <?= e($r['checkout_id']) ?></small></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
        <div class="store-pagination">
            <span>Showing Page <?= $page ?> of <?= $lastPage ?> · <?= $total ?> orders</span>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= e(app_url('/vendor/orders/index.php?page=' . ($page - 1) . ($search ? '&q=' . urlencode($search) : ''))) ?>">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($lastPage, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="badge" style="background:#3288d7;color:#fff; padding:7px 10px; border-radius:7px; margin:0 2px; "><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?= e(app_url('/vendor/orders/index.php?page=' . $i . ($search ? '&q=' . urlencode($search) : ''))) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $lastPage): ?>
                    <a href="<?= e(app_url('/vendor/orders/index.php?page=' . ($page + 1) . ($search ? '&q=' . urlencode($search) : ''))) ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>