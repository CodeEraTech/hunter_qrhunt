<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_permission('rewards.view');

$pdo = db();

if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hunter-targeted-offers-template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'target_type',
        'target_id',
        'bonus_type',
        'bonus_value',
        'shop_id',
        'min_cart',
        'max_limit',
        'start_date',
        'end_date',
    ]);
    fputcsv($out, [
        'PRODUCT',
        'PROD-1024',
        'PERCENTAGE',
        '5.00',
        'ALL',
        '500.00',
        '5000.00',
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s', strtotime('+30 days')),
    ]);
    fputcsv($out, [
        'CATEGORY',
        'CAT-ELECTRONICS',
        'FIXED',
        '50.00',
        'ALL',
        '1000.00',
        '50.00',
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s', strtotime('+15 days')),
    ]);
    fputcsv($out, [
        'USER',
        '5821',
        'PERCENTAGE',
        '10.00',
        'ALL',
        '250.00',
        '200.00',
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s', strtotime('+7 days')),
    ]);
    fclose($out);
    exit;
}

if (is_post()) {
    verify_csrf();
    try {
        $action = (string)input('action');

        if ($action === 'create') {
            create_targeted_offer([
                'offer_title' => (string)input('offer_title'),
                'target_type' => (string)input('target_type'),
                'target_id' => (string)input('target_id'),
                'target_name' => (string)input('target_name'),
                'bonus_type' => (string)input('bonus_type'),
                'bonus_value' => (float)input('bonus_value'),
                'shop_id' => (string)input('shop_id'),
                'min_cart' => (float)input('min_cart', 0),
                'max_limit' => (float)input('max_limit', 0),
                'start_date' => (string)input('start_date'),
                'end_date' => (string)input('end_date'),
                'status' => (string)input('status', 'ACTIVE'),
            ], (int)admin()['id']);

            flash('success', 'Targeted offer created and published successfully.');
        } elseif ($action === 'bulk_upload') {
            $file = $_FILES['offer_sheet'] ?? null;
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Please upload a valid CSV/Excel file.');
            }

            $fh = fopen((string)$file['tmp_name'], 'rb');
            if (!$fh) {
                throw new RuntimeException('Could not read the uploaded sheet.');
            }

            $expected = ['target_type', 'target_id', 'bonus_type', 'bonus_value', 'shop_id', 'min_cart', 'max_limit', 'start_date', 'end_date'];
            $rawHeader = fgetcsv($fh) ?: [];
            $headers = array_map(static fn($v) => strtolower(trim((string)$v)), $rawHeader);

            if ($headers !== $expected) {
                throw new InvalidArgumentException('File header format mismatch. Please use the official downloadable sample template.');
            }

            $rows = [];
            while (($r = fgetcsv($fh)) !== false) {
                if (count(array_filter($r, static fn($v) => trim((string)$v) !== '')) === 0) continue;
                if (count($r) !== count($expected)) continue;
                $rows[] = array_combine($expected, $r);
            }
            fclose($fh);

            if (!$rows) {
                throw new InvalidArgumentException('The uploaded sheet contains no valid offer data rows.');
            }

            $imported = bulk_import_targeted_offers($rows, (int)admin()['id']);
            flash('success', "{$imported} targeted offers successfully imported and published.");
        } elseif ($action === 'delete') {
            $id = (int)input('id');
            $pdo->prepare('DELETE FROM targeted_offers WHERE id = ?')->execute([$id]);
            audit_log((int)admin()['id'], 'TARGETED_OFFER_DELETED', 'targeted_offers', (string)$id);
            flash('success', 'Targeted offer removed.');
        } elseif ($action === 'toggle_status') {
            $id = (int)input('id');
            $current = (string)$pdo->query("SELECT status FROM targeted_offers WHERE id = {$id}")->fetchColumn();
            $newStatus = ($current === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
            $pdo->prepare('UPDATE targeted_offers SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
            audit_log((int)admin()['id'], 'TARGETED_OFFER_STATUS_CHANGED', 'targeted_offers', (string)$id, null, ['status' => $newStatus]);
            flash('success', "Offer status updated to {$newStatus}.");
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/rewards/targeted_offers.php');
}

// Fetch shops/vendors for eligible shop dropdown
$vendors = $pdo->query('SELECT vendor_code, shop_name FROM vendors ORDER BY shop_name')->fetchAll();

// Fetch existing offers
$offers = $pdo->query('SELECT * FROM targeted_offers ORDER BY id DESC LIMIT 100')->fetchAll();

$pageTitle = 'Targeted Bonus & Offers';
$pageSubtitle = 'Create and bulk manage precision bonus discounts with automated priority checkout execution';
require APP_ROOT . '/includes/header.php';
?>

<style>
.section-banner {
    grid-column: 1 / -1;
    background: #0f274a;
    color: #ffffff;
    padding: 12px 18px;
    margin-top: 10px;
    margin-bottom: 6px;
    border-radius: 8px;
    font-weight: 750;
    font-size: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.rule-box {
    background: #eef6ff;
    border-left: 4px solid var(--blue);
    padding: 12px 16px;
    border-radius: 6px;
    margin: 14px 0;
    font-size: 13px;
    color: #174070;
}
</style>

<!-- SECTION 1: CREATE / SET TARGETED OFFER (Manual Entry Screen) -->
<section class="panel">
    <div class="panel-head">
        <h2>1. Create / Set Targeted Offer (Manual Entry Screen)</h2>
    </div>

    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">

        <div class="field">
            <label>Offer Title / Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="offer_title" placeholder="e.g., Special 5% Bonus on Electronics" required>
        </div>

        <div class="field">
            <label>Target Type Selection <span style="color:var(--red)">*</span></label>
            <select name="target_type" id="target-type-select" required>
                <option value="PRODUCT">Product ID (Product-Specific Offer)</option>
                <option value="CATEGORY">Category (Category Rule)</option>
                <option value="VENDOR">Specific Vendor Shop (Vendor Default Rule)</option>
                <option value="USER">Particular User ID (User-Specific Offer - Highest Priority)</option>
            </select>
        </div>

        <div class="field">
            <label>Specific Target ID / Code <span style="color:var(--red)">*</span></label>
            <input type="text" name="target_id" id="target-id-input" placeholder="e.g., Product #1024, Category: Grocery, User #5821" required>
            <small class="help">Enter specific Product Code, Category Name, Vendor Code, or User ID</small>
        </div>

        <div class="field">
            <label>Target Display Name (Optional)</label>
            <input type="text" name="target_name" placeholder="e.g., Electronics, John Doe, etc.">
        </div>

        <div class="field">
            <label>Bonus Deduction Type &amp; Value <span style="color:var(--red)">*</span></label>
            <div style="display: flex; gap: 8px;">
                <select name="bonus_type" style="width: 160px;">
                    <option value="PERCENTAGE">Percentage (%)</option>
                    <option value="FIXED">Fixed Amount (₹)</option>
                </select>
                <input type="number" step="0.01" min="0.01" name="bonus_value" placeholder="e.g., 5 or 50" required style="flex: 1;">
            </div>
            <small class="help">Type: Percentage (%) or Fixed Amount (₹) | Value: e.g., 2%, 5%, or ₹50</small>
        </div>

        <div class="field">
            <label>Eligible Shop / Vendor Restriction</label>
            <select name="shop_id">
                <option value="ALL">All Shops &amp; Locations (Universal)</option>
                <?php foreach ($vendors as $v): ?>
                    <option value="<?= e($v['vendor_code']) ?>"><?= e($v['shop_name'] . ' (' . $v['vendor_code'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
            <small class="help">Restrict bonus redemption to all shops or one specific merchant location</small>
        </div>

        <div class="field">
            <label>Min &amp; Max Order Amount Limits</label>
            <div style="display: flex; gap: 8px;">
                <input type="number" step="0.01" min="0" name="min_cart" placeholder="Min Cart: e.g. 500" value="500.00" style="flex: 1;">
                <input type="number" step="0.01" min="0" name="max_limit" placeholder="Max Order Limit: e.g. 5000" value="5000.00" style="flex: 1;">
            </div>
            <small class="help">Min Cart: ₹500 | Max Order Limit: ₹5,000 (Auto eligibility check at checkout)</small>
        </div>

        <div class="field">
            <label>Offer Schedule (Start &amp; End Date-Time) <span style="color:var(--red)">*</span></label>
            <div style="display: flex; gap: 8px;">
                <input type="datetime-local" name="start_date" value="<?= date('Y-m-d\TH:i') ?>" required style="flex: 1;">
                <input type="datetime-local" name="end_date" value="<?= date('Y-m-d\TH:i', strtotime('+30 days')) ?>" required style="flex: 1;">
            </div>
            <small class="help">From: DD/MM/YYYY HH:MM • To: DD/MM/YYYY HH:MM</small>
        </div>

        <div class="field">
            <label>Status &amp; Publish</label>
            <select name="status">
                <option value="ACTIVE" selected>ACTIVE (Live in checkout engine)</option>
                <option value="INACTIVE">INACTIVE (Draft / Paused)</option>
            </select>
        </div>

        <div class="field full actions" style="margin-top: 10px;">
            <button class="btn" type="submit">Save &amp; Publish Offer</button>
        </div>
    </form>
</section>

<!-- SECTION 2: BULK UPLOAD TARGETED OFFERS (Excel / CSV Integration) -->
<section class="panel">
    <div class="panel-head">
        <h2>2. Bulk Upload Targeted Offers (Excel / CSV Integration)</h2>
        <div class="actions">
            <a class="btn secondary" href="?download_template=1">Download Sample Template</a>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="bulk_upload">

        <div class="field full">
            <label>Upload Filled Excel / CSV Sheet <span style="color:var(--red)">*</span></label>
            <input type="file" name="offer_sheet" accept=".csv,text/csv" required>
            <small class="help">
                Required Columns: <code>target_type, target_id, bonus_type, bonus_value, shop_id, min_cart, max_limit, start_date, end_date</code>
            </small>
        </div>

        <div class="field full">
            <div class="rule-box">
                <strong>Developer Implementation Rule:</strong>
                Priority engine checks: <code>User-specific offer > Product-specific rule > Category rule > Vendor default rule</code>,
                and applies automatic wallet auto-cut at checkout within defined limits.
            </div>
        </div>

        <div class="field full actions">
            <button class="btn" type="submit">Upload &amp; Process Sheet</button>
        </div>
    </form>
</section>

<!-- SECTION 3: OFFERS DIRECTORY & MONITORING -->
<section class="panel">
    <div class="panel-head">
        <h2>Active Targeted Offers &amp; Rules (<?= count($offers) ?>)</h2>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Priority &amp; Target</th>
                    <th>Offer Title</th>
                    <th>Bonus Rule</th>
                    <th>Eligible Shop</th>
                    <th>Cart Limits (Min / Max)</th>
                    <th>Schedule Window</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($offers as $o): ?>
                    <?php
                    $isExpired = (date('Y-m-d H:i:s') > $o['end_date']);
                    ?>
                    <tr>
                        <td>
                            <?php
                            $badgeStyle = match($o['target_type']) {
                                'USER' => 'background:#fee2e2; color:#991b1b;',
                                'PRODUCT' => 'background:#e0e7ff; color:#3730a3;',
                                'CATEGORY' => 'background:#fef3c7; color:#92400e;',
                                default => 'background:#ecfdf5; color:#065f46;',
                            };
                            ?>
                            <span class="badge" style="<?= $badgeStyle ?>">
                                <?= e($o['target_type']) ?>
                            </span><br>
                            <strong><?= e($o['target_id']) ?></strong>
                            <?php if (!empty($o['target_name'])): ?>
                                <br><small style="color:var(--muted)"><?= e($o['target_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($o['offer_title']) ?></strong>
                        </td>
                        <td>
                            <strong style="color:var(--green); font-size:15px;">
                                <?= e((string)$o['bonus_value']) ?><?= $o['bonus_type'] === 'PERCENTAGE' ? '%' : ' ₹' ?>
                            </strong><br>
                            <small class="badge"><?= e($o['bonus_type']) ?></small>
                        </td>
                        <td>
                            <?= (!empty($o['shop_id']) && $o['shop_id'] !== 'ALL') ? e($o['shop_id']) : '<span class="badge">All Shops</span>' ?>
                        </td>
                        <td>
                            Min: ₹<?= e((string)$o['min_cart']) ?><br>
                            Max: ₹<?= e((string)$o['max_limit']) ?>
                        </td>
                        <td>
                            <small style="color:var(--muted);">From: <?= e(date('M d, Y H:i', strtotime($o['start_date']))) ?></small><br>
                            <small style="color:<?= $isExpired ? 'var(--red)' : 'var(--navy)' ?>;">To: <?= e(date('M d, Y H:i', strtotime($o['end_date']))) ?></small>
                        </td>
                        <td>
                            <span class="badge <?= ($o['status'] === 'ACTIVE' && !$isExpired) ? 'active' : 'blocked' ?>">
                                <?= $isExpired ? 'EXPIRED' : e($o['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= e((string)$o['id']) ?>">
                                    <button class="btn secondary small" type="submit">
                                        <?= $o['status'] === 'ACTIVE' ? 'Pause' : 'Activate' ?>
                                    </button>
                                </form>
                                <form method="post" data-confirm="Remove this offer rule?" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e((string)$o['id']) ?>">
                                    <button class="btn danger small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$offers): ?>
                    <tr>
                        <td colspan="8" class="empty">No targeted offers created yet. Use the manual form or bulk sheet upload above.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require APP_ROOT . '/includes/footer.php'; ?>

