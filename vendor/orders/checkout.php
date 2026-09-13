<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];

$pageTitle = 'Checkout / Staff Verification';
require APP_ROOT.'/includes/vendor_header.php';

// Lookup order if checkout_id provided
$order = null;
$items = [];
$verificationAction = '';
$verificationNotes = '';
$successMsg = '';
$errorMsg = '';

$lookupId = trim((string) ($_GET['checkout_id'] ?? ($_POST['checkout_id'] ?? '')));

if ($lookupId !== '') {
    $stmt = db()->prepare("SELECT c.*, u.name AS customer_name, u.email AS customer_email, u.mobile AS customer_mobile
        FROM wallet_checkout_transactions c
        LEFT JOIN users u ON u.id = c.user_id
        WHERE c.checkout_id = ? AND c.merchant_id = ?
        LIMIT 1");
    $stmt->execute([$lookupId, $vendorId]);
    $order = $stmt->fetch();

    if ($order) {
        // Load wallet transactions as items proxy
        $txnStmt = db()->prepare("SELECT type, bucket, amount, status, created_at, description, reference
            FROM wallet_transactions
            WHERE user_id = ? AND `reference` = ?
            ORDER BY created_at DESC, id DESC");
        $txnStmt->execute([(int) $order['user_id'], $lookupId]);
        $items = $txnStmt->fetchAll();
    } else {
        $errorMsg = 'Order not found or not owned by your store.';
    }
}

// Handle verification form POST
if (is_post() && $order) {
    require_post();
    // CSRF handled by vendor_header.php in form
    $verificationAction = trim((string) ($_POST['verification_action'] ?? ''));
    $verificationNotes = trim((string) ($_POST['verification_notes'] ?? ''));

    $validActions = ['VERIFIED', 'REJECTED', 'PARTIAL'];
    if (!in_array($verificationAction, $validActions, true)) {
        $errorMsg = 'Invalid verification action.';
    } else {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            // Try to update verification_status if column exists
            $hasVerificationCol = false;
            try {
                $colCheck = $pdo->prepare("SHOW COLUMNS FROM wallet_checkout_transactions LIKE 'verification_status'");
                $colCheck->execute();
                $hasVerificationCol = (bool) $colCheck->fetch();
            } catch (PDOException $e) {}

            $hasNotesCol = false;
            try {
                $colCheck = $pdo->prepare("SHOW COLUMNS FROM wallet_checkout_transactions LIKE 'verification_notes'");
                $colCheck->execute();
                $hasNotesCol = (bool) $colCheck->fetch();
            } catch (PDOException $e) {}

            if ($hasVerificationCol) {
                $fields = ['verification_status = ?'];
                $values = [$verificationAction];
                if ($hasNotesCol) {
                    $fields[] = 'verification_notes = ?';
                    $values[] = $verificationNotes;
                }
                $update = $pdo->prepare("UPDATE wallet_checkout_transactions SET " . implode(', ', $fields) . " WHERE checkout_id = ? AND merchant_id = ?");
                $values[] = $lookupId;
                $values[] = $vendorId;
                $update->execute($values);
            }

            // Log verification action
            $verificationId = transaction_code('VER');
            $pdo->prepare("INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, old_values, new_values, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([
                $vendor['id'],
                'ORDER_VERIFICATION',
                'wallet_checkout_transactions',
                $lookupId,
                safe_json(['action' => $verificationAction, 'notes' => $verificationNotes]),
                safe_json(['status' => 'recorded']),
                client_ip()
            ]);

            $pdo->commit();
            $successMsg = 'Verification recorded: ' . $verificationAction;
            // Refresh order
            $stmt = $pdo->prepare("SELECT c.*, u.name AS customer_name FROM wallet_checkout_transactions c LEFT JOIN users u ON u.id = c.user_id WHERE c.checkout_id = ? AND c.merchant_id = ? LIMIT 1");
            $stmt->execute([$lookupId, $vendorId]);
            $order = $stmt->fetch();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errorMsg = 'Failed to record verification: ' . $e->getMessage();
        }
    }
}
?>

<section class="store-hero">
    <p class="store-eyebrow">Orders & Customers · Staff verification</p>
    <h1>Checkout / Staff Verification</h1>
    <p>Verify customer orders at checkout. Scan or enter Order ID to load details and record verification.</p>
</section>

<?php if ($errorMsg): ?>
    <div class="alert error" style="margin:0 0 20px 0;"><?= e($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
    <div class="alert success" style="margin:0 0 20px 0;"><?= e($successMsg) ?></div>
<?php endif; ?>

<section class="store-panel">
    <div class="panel-head"><h2>Order Lookup</h2></div>
    <form class="filters" method="get" action="" style="gap:10px; max-width:500px;">
        <input type="text" name="checkout_id" value="<?= e($lookupId) ?>" placeholder="Enter or scan Order ID (checkout_id)" aria-label="Order ID" style="flex:1;" required>
        <button type="submit" class="btn">Load Order</button>
    </form>
    <div style="margin-top:12px; font-size:12px; color:var(--muted);">
        <strong>QR Scanner:</strong> Focus the input above and scan the order QR code. The checkout_id will be populated automatically.
    </div>
</section>

<?php if ($order): ?>
    <section class="store-panel">
        <div class="panel-head"><h2>Order #<?= e($order['checkout_id']) ?></h2></div>

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
                            <tr><td>Gross Bill</td><td class="store-amount"> <?= money($order['bill_amount']) ?></td></tr>
                            <tr><td>Payment Status</td><td><span class="store-status status-<?= e(strtolower($order['status'])) ?>"><?= e(ucfirst(strtolower($order['status']))) ?></span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="stack">
                <div class="store-table">
                    <table class="store-table">
                        <thead><tr><th>Amount Breakdown</th><th>Value</th></tr></thead>
                        <tbody>
                            <tr><td>Gross Bill</td><td class="store-amount"> <?= money($order['bill_amount']) ?></td></tr>
                            <tr><td>Qoin / Bonus Used</td><td class="store-amount"> <?= money($order['bonus_used'] ?? 0) ?></td></tr>
                            <tr><td>Promo Used</td><td class="store-amount"> <?= money($order['promo_used'] ?? 0) ?></td></tr>
                            <tr><td>Real Used</td><td class="store-amount"> <?= money($order['real_used'] ?? 0) ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="store-panel">
        <div class="panel-head"><h2>Items / Verification Checklist</h2></div>
        <div class="table-wrap">
            <table class="store-table">
                <thead>
                    <tr>
                        <th>SKU / Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Item Total</th>
                        <th>Verified</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$items): ?>
                        <tr><td colspan="5" class="empty">No item details recorded for this order.</td></tr>
                    <?php else: foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= e($item['description'] ?? '—') ?></strong></td>
                            <td>1</td>
                            <td class="store-amount"> <?= money($item['amount']) ?></td>
                            <td class="store-amount"> <?= money($item['amount']) ?></td>
                            <td><input type="checkbox" name="item_verified[]" value="<?= e($item['reference'] ?? '') ?>"></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="store-panel">
        <div class="panel-head"><h2>Verification Action</h2></div>
        <form method="post" action="" class="form-grid">
            <input type="hidden" name="checkout_id" value="<?= e($lookupId) ?>">
            <?= csrf_field() ?>
            <div class="field">
                <label>Verification Status</label>
                <select name="verification_action" required>
                    <option value="VERIFIED" <?= selected($verificationAction, 'VERIFIED') ?>>Verified (Complete)</option>
                    <option value="PARTIAL" <?= selected($verificationAction, 'PARTIAL') ?>>Partially Verified</option>
                    <option value="REJECTED" <?= selected($verificationAction, 'REJECTED') ?>>Rejected</option>
                </select>
            </div>
            <div class="field full">
                <label>Verification Notes</label>
                <textarea name="verification_notes" placeholder="Add notes about the verification..."><?= e($verificationNotes) ?></textarea>
            </div>
            <div class="field full actions">
                <button type="submit" class="btn">Record Verification</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php require APP_ROOT.'/includes/vendor_footer.php'; ?>
