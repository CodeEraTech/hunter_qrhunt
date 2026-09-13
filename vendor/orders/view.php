<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];

$checkoutId = trim((string) ($_GET['checkout_id'] ?? ''));
if ($checkoutId === '') {
    flash('error', 'Order not specified.');
    redirect('/vendor/orders/index.php');
}

// Load order, verify merchant_id matches vendor_id from session only
$stmt = db()->prepare("SELECT c.*, u.name AS customer_name, u.email AS customer_email, u.mobile AS customer_mobile
    FROM wallet_checkout_transactions c
    LEFT JOIN users u ON u.id = c.user_id
    WHERE c.checkout_id = ? AND c.merchant_id = ?
    LIMIT 1");
$stmt->execute([$checkoutId, $vendorId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found or not owned by your store.');
    redirect('/vendor/orders/index.php');
}

// Qoin/bonus used from wallet_transactions (reference = checkout_id)
$txnStmt = db()->prepare("SELECT type, bucket, amount, status, created_at, description
    FROM wallet_transactions
    WHERE user_id = ? AND `reference` = ?
    ORDER BY created_at DESC, id DESC");
$txnStmt->execute([(int) $order['user_id'], $checkoutId]);
$walletTxns = $txnStmt->fetchAll();

$bonusUsed = 0.0;
$promoUsed = 0.0;
foreach ($walletTxns as $t) {
    if ($t['bucket'] === 'BONUS') $bonusUsed += (float) $t['amount'];
    if ($t['bucket'] === 'PROMO') $promoUsed += (float) $t['amount'];
}

// Check for verification_status column on checkout transactions
$hasVerificationCol = false;
try {
    $colCheck = db()->prepare("SHOW COLUMNS FROM wallet_checkout_transactions LIKE 'verification_status'");
    $colCheck->execute();
    $hasVerificationCol = (bool) $colCheck->fetch();
} catch (PDOException $e) {
    $hasVerificationCol = false;
}

// Check for verification_notes column
$hasNotesCol = false;
try {
    $colCheck = db()->prepare("SHOW COLUMNS FROM wallet_checkout_transactions LIKE 'verification_notes'");
    $colCheck->execute();
    $hasNotesCol = (bool) $colCheck->fetch();
} catch (PDOException $e) {
    $hasNotesCol = false;
}

$pageTitle = 'Order Details';
require APP_ROOT.'/includes/vendor_header.php';

function order_status_label(string $status): string
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
    <p class="store-eyebrow">Orders & Customers · Order detail</p>
    <h1>Order Details</h1>
    <p>Full breakdown of customer order #<?= e($order['checkout_id']) ?> placed against your store.</p>
</section>

<section class="store-panel">
    <div class="panel-head">
        <h2>Order #<?= e($order['checkout_id']) ?></h2>
        <a class="btn secondary" href="<?= e(app_url('/vendor/orders/index.php')) ?>">Back to Orders</a>
    </div>

    <div class="detail-grid">
        <div class="stack">
            <div class="store-table">
                <table class="store-table">
                    <thead><tr><th>Field</th><th>Value</th></tr></thead>
                    <tbody>
                        <tr><td>Order ID</td><td><strong>#<?= e($order['checkout_id']) ?></strong></td></tr>
                        <tr><td>Customer</td><td><?= e($order['customer_name'] ?? '—') ?></td></tr>
                        <tr><td>Customer Email</td><td><?= e($order['customer_email'] ?? '—') ?></td></tr>
                        <tr><td>Customer Mobile</td><td><?= e($order['customer_mobile'] ?? '—') ?></td></tr>
                        <tr><td>Order Date / Time</td><td><?= e($order['created_at']) ?></td></tr>
                        <tr><td>Customer Payment Method</td><td>Wallet Checkout</td></tr>
                        <tr><td>Assigned Staff</td><td>—</td></tr>
                        <tr><td>Verification Status</td><td>
                            <?php if ($hasVerificationCol && $order['verification_status']): ?>
                                <span class="store-status status-<?= e(strtolower($order['verification_status'])) ?>"><?= e($order['verification_status']) ?></span>
                            <?php else: ?>
                                <span class="store-status">Not set</span>
                            <?php endif; ?>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="stack">
            <div class="store-table">
                <table class="store-table">
                    <thead><tr><th>Amount</th><th>Value</th></tr></thead>
                    <tbody>
                        <tr><td>Gross Bill</td><td class="store-amount"> <?= money($order['bill_amount']) ?></td></tr>
                        <tr><td>Qoin / Bonus Used</td><td class="store-amount"> <?= money($bonusUsed) ?></td></tr>
                        <tr><td>Promo Used</td><td class="store-amount"> <?= money($promoUsed) ?></td></tr>
                        <tr><td>Real Used</td><td class="store-amount"> <?= money($order['real_used'] ?? 0) ?></td></tr>
                        <tr><td>Payment Status</td><td><span class="store-status status-<?= e(strtolower($order['status'])) ?> <?= e(order_status_label($order['status'])) ?></span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="store-panel">
    <div class="panel-head"><h2>Items</h2></div>
    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr><th>SKU</th><th>Quantity</th><th>Unit Price</th><th>Item Total</th></tr>
            </thead>
            <tbody>
                <?php if (!$walletTxns): ?>
                    <tr><td colspan="4" class="empty">No item details recorded for this order.</td></tr>
                <?php else: foreach ($walletTxns as $t): ?>
                    <tr>
                        <td><strong><?= e($t['description'] ?? '—') ?></strong></td>
                        <td>1</td>
                        <td class="store-amount"> <?= money($t['amount']) ?></td>
                        <td class="store-amount"> <?= money($t['amount']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>