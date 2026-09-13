<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_vendor();

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];

$pageTitle = 'Bulk Excel Upload';
$pageSubtitle = 'Upload multiple products using an Excel template';
require APP_ROOT . '/includes/vendor_header.php';

$result = null;
$templateUrl = app_url('/assets/templates/product-upload-template.xlsx');

if (is_post() && isset($_FILES['bulk_file'])) {
    if (!csrf_validate()) {
        $result = ['success' => false, 'error' => 'Invalid CSRF token.'];
    } else {
        $file = $_FILES['bulk_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result = ['success' => false, 'error' => 'File upload failed. Please try again.'];
        } else {
            $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'xlsx') {
                $result = ['success' => false, 'error' => 'Only .xlsx files are supported.'];
            } else {
                try {
                    if (!class_exists('SimpleXLSX')) {
                        throw new RuntimeException('Excel parser not available.');
                    }
                    $xlsx = new SimpleXLSX((string) $file['tmp_name']);
                    $sheet = $xlsx->getSheet(1) ?: $xlsx->getSheet(0);
                    $rows = $sheet ?: [];
                    $success = [];
                    $errors = [];

                    foreach ($rows as $index => $row) {
                        if ($index === 0) continue;
                        $product_code = trim((string) ($row[0] ?? ''));
                        $title = trim((string) ($row[1] ?? ''));
                        $category = trim((string) ($row[2] ?? ''));
                        $priceRaw = trim((string) ($row[3] ?? ''));
                        $mrpRaw = trim((string) ($row[4] ?? ''));
                        $stockRaw = trim((string) ($row[5] ?? ''));
                        $min_qtyRaw = trim((string) ($row[6] ?? ''));
                        $description = trim((string) ($row[7] ?? ''));
                        $qoin_enabled = trim((string) ($row[8] ?? '')) === 'Yes' ? '1' : '0';
                        $qoin_value = trim((string) ($row[9] ?? ''));

                        if ($product_code === '') { $errors[] = "Row {$index}: SKU missing."; continue; }
                        if ($title === '') { $errors[] = "Row {$index}: Title missing."; continue; }
                        if ($priceRaw === '' || !is_numeric($priceRaw)) { $errors[] = "Row {$index}: Invalid price."; continue; }

                        $check = db()->prepare('SELECT 1 FROM vendor_products WHERE product_code = ? LIMIT 1');
                        $check->execute([$product_code]);
                        if ($check->fetch()) { $errors[] = "Row {$index}: SKU {$product_code} already exists."; continue; }

                        $metadata = [
                            'category' => $category ?: null,
                            'mrp' => $mrpRaw !== '' ? (float) $mrpRaw : null,
                            'stock' => $stockRaw !== '' && ctype_digit($stockRaw) ? (int) $stockRaw : null,
                            'min_quantity' => $min_qtyRaw !== '' && ctype_digit($min_qtyRaw) ? (int) $min_qtyRaw : null,
                            'qoin_enabled' => $qoin_enabled === '1',
                            'qoin_value' => $qoin_value !== '' ? (float) $qoin_value : null,
                        ];
                        $metadata = array_filter($metadata, static fn($v) => $v !== null);

                        $batchId = 'BULK-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

                        $stmt = db()->prepare('INSERT INTO vendor_products (vendor_id, product_code, title, price, metadata, description, status, submitted_at) VALUES (?,?,?,?,?,?,"PENDING_REVIEW",NOW())');
                        $stmt->execute([$vendorId, $product_code, $title, (float) $priceRaw, safe_json($metadata), $description]);
                        $success[] = ['sku' => $product_code, 'title' => $title, 'price' => (float) $priceRaw];
                    }

                    $result = ['success' => true, 'success_count' => count($success), 'error_count' => count($errors), 'success' => $success, 'errors' => $errors];
                } catch (Throwable $e) {
                    $result = ['success' => false, 'error' => 'Failed to parse file: ' . $e->getMessage()];
                }
            }
        }
    }
}
?>

<section class="store-panel" aria-labelledby="upload-heading">
    <div class="panel-head">
        <h2 id="upload-heading">Bulk Excel Upload</h2>
    </div>

    <?php foreach (take_flashes() as $flash): ?>
        <div class="toast toast-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <div class="store-panel">
        <p style="margin-top:0; color:var(--muted);">Upload products in bulk using a standard Excel template. Ensure required fields are filled correctly.</p>
        <a class="btn primary" href="<?= e($templateUrl) ?>" download>Download Standard Template</a>
    </div>

    <div class="store-panel" style="margin-top:22px;">
        <form method="post" enctype="multipart/form-data" class="campaign-sections" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:end;">
            <?= csrf_field() ?>
            <div class="field" style="grid-column:1/-1;">
                <label class="field-label-row"><span>Select .xlsx File</span></label>
                <input type="file" name="bulk_file" accept=".xlsx" required style="width:100%; padding:10px; border:1px solid var(--line); border-radius:10px;">
            </div>
            <div style="grid-column:1/-1;">
                <button type="submit" class="btn primary">Upload and Process</button>
            </div>
        </form>
    </div>

    <?php if ($result !== null): ?>
        <div class="store-panel" style="margin-top:22px;">
            <h3 style="margin-top:0;">Upload Results</h3>
            <?php if (!$result['success']): ?>
                <div class="toast toast-error" role="alert"><?= e($result['error']) ?></div>
            <?php else: ?>
                <p><strong>Successfully uploaded:</strong> <?= $result['success_count'] ?> products &nbsp;|&nbsp; <strong style="color:var(--red);">Errors:</strong> <?= $result['error_count'] ?></p>

                <?php if ($result['success']): ?>
                    <h4 style="margin:14px 0 8px;">Successful Records</h4>
                    <div class="table-wrap">
                        <table class="store-table">
                            <thead>
                                <tr><th>SKU</th><th>Title</th><th>Price</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['success'] as $row): ?>
                                    <tr>
                                        <td><strong><?= e($row['sku']) ?></strong></td>
                                        <td><?= e($row['title']) ?></td>
                                        <td class="store-amount"><?= money($row['price']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($result['errors']): ?>
                    <h4 style="margin:14px 0 8px; color:var(--red);">Error Records</h4>
                    <ul style="padding-left:20px; color:var(--red);">
                        <?php foreach ($result['errors'] as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div style="margin-top:16px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" id="qr-sheet-check" style="width:auto;">
                        <span>Generate QR sheet for uploaded products</span>
                    </label>
                    <button class="btn secondary" id="qr-sheet-btn" style="margin-top:10px;" disabled onclick="alert('QR generation would be triggered here.')">Generate QR Sheet</button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var check = document.getElementById('qr-sheet-check');
    var btn = document.getElementById('qr-sheet-btn');
    if (check && btn) {
        check.addEventListener('change', function() { btn.disabled = !this.checked; });
    }
});
</script>

<?php require APP_ROOT . '/includes/vendor_footer.php'; ?>