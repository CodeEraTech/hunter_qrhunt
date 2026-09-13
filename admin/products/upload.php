<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('products.bulk_upload');
require_once APP_ROOT.'/includes/product_management.php';

$pageTitle = 'Bulk Product Upload';
$pageSubtitle = 'Upload products via Excel and generate batch QR codes';
$adm = admin();

$message = null;
$error = null;
$batchId = null;
$batchDetails = null;

if (is_post()) {
    verify_csrf();
    $action = input('action');

    if ($action === 'upload') {
        if (empty($_FILES['excel']['tmp_name'])) {
            $error = 'Please select an Excel file.';
        } elseif (!in_array($_FILES['excel']['type'], ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'], true)) {
            $error = 'Only .xlsx files are supported.';
        } else {
            $batchId = product_bulk_upload_batch(1, $adm['id'], $_FILES['excel']['name']);
            product_bulk_upload_update_batch($batchId, 0, 0, 0, 'PROCESSING');

            try {
                require_once APP_ROOT.'/vendor/autoload.php';
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel']['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, true);
                $header = array_shift($rows);

                $required = ['product_code', 'title', 'price'];
                $total = count($rows);
                $success = 0;
                $failed = 0;
                $errors = [];

                foreach ($rows as $i => $row) {
                    $rowNum = $i + 2;
                    $data = [];
                    foreach ($header as $col => $h) {
                        $data[strtolower(trim($h))] = trim((string)($row[$col] ?? ''));
                    }

                    $productCode = $data['product_code'] ?? '';
                    $title = $data['title'] ?? '';
                    $price = $data['price'] ?? '';

                    if (!$productCode || !$title || $price === '') {
                        product_bulk_upload_record($batchId, $rowNum, $productCode, $title, 'FAILED', 'Missing required fields (product_code, title, price)');
                        $failed++;
                        continue;
                    }
                    if (!is_numeric($price) || (float)$price < 0) {
                        product_bulk_upload_record($batchId, $rowNum, $productCode, $title, 'FAILED', 'Invalid price');
                        $failed++;
                        continue;
                    }

                    $check = db()->prepare('SELECT id FROM vendor_products WHERE product_code = ? LIMIT 1');
                    $check->execute([$productCode]);
                    if ($check->fetch()) {
                        product_bulk_upload_record($batchId, $rowNum, $productCode, $title, 'SKIPPED', 'SKU already exists');
                        $failed++;
                        continue;
                    }

                    $metadata = [
                        'category' => $data['category'] ?? null,
                        'mrp' => $data['mrp'] !== '' ? (float)$data['mrp'] : null,
                        'stock' => $data['stock'] !== '' ? (int)$data['stock'] : null,
                        'min_quantity' => $data['min_quantity'] !== '' ? (int)$data['min_quantity'] : null,
                        'qoin_enabled' => isset($data['qoin_enabled']) && strtolower($data['qoin_enabled']) === 'yes',
                        'qoin_value' => $data['qoin_value'] !== '' ? (float)$data['qoin_value'] : null,
                    ];
                    $metadata = array_filter($metadata, static fn($v) => $v !== null);

                    $defaultVendor = db()->query("SELECT id FROM vendors WHERE status='ACTIVE' ORDER BY id LIMIT 1")->fetch();
                    if (!$defaultVendor) throw new RuntimeException('No active vendor found');

                    try {
                        $prodId = product_create([
                            'product_code' => $productCode,
                            'title' => $title,
                            'price' => (float)$price,
                            'metadata' => safe_json($metadata),
                            'description' => $data['description'] ?? '',
                            'category_id' => null,
                        ], (int)$defaultVendor['id'], $adm['id']);

                        if (($data['generate_qr'] ?? '') === 'yes') {
                            product_qr_create($prodId, $productCode, (int)$defaultVendor['id'], $adm['id']);
                        }

                        product_bulk_upload_record($batchId, $rowNum, $productCode, $title, 'SUCCESS', null, $prodId);
                        $success++;
                    } catch (Throwable $e) {
                        product_bulk_upload_record($batchId, $rowNum, $productCode, $title, 'FAILED', $e->getMessage());
                        $failed++;
                    }
                }

                product_bulk_upload_update_batch($batchId, $total, $success, $failed, 'COMPLETED');
                $message = "Upload completed: {$success} success, {$failed} failed.";
            } catch (Throwable $e) {
                product_bulk_upload_update_batch($batchId, 0, 0, 0, 'FAILED', ['error' => $e->getMessage()]);
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'view_batch') {
        $batchId = (int) input('batch_id');
        $batchDetails = product_upload_batch_details($batchId);
    }
}

$batches = product_upload_batches(1, 20);
require APP_ROOT.'/includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Bulk Product Upload</h2>
    </div>

    <?php if ($message): ?><div class="alert success"><?=e($message)?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
    <?php foreach (take_flashes() as $f): ?><div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div><?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" class="stack" style="max-width:600px">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="upload">
        <div class="field">
            <label>Excel File (.xlsx)</label>
            <input type="file" name="excel" accept=".xlsx" required style="width:100%;padding:10px;border:1px solid var(--line);border-radius:10px">
        </div>
        <div class="field">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="checkbox" name="generate_qr" value="yes" style="width:auto">
                <span>Generate QR codes for uploaded products</span>
            </label>
        </div>
        <p class="help">Required columns: <strong>product_code</strong>, <strong>title</strong>, <strong>price</strong>. Optional: category, mrp, stock, min_quantity, description, qoin_enabled (yes/no), qoin_value, generate_qr (yes/no).</p>
        <button class="btn primary" type="submit">Upload & Process</button>
    </form>

    <hr style="margin:24px 0">

    <div class="panel-head"><h2>Upload History</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Batch Code</th><th>Vendor</th><th>File</th><th>Total</th><th>Success</th><th>Failed</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($batches['rows'] as $b): ?>
            <tr>
                <td><code><?=e($b['batch_code'])?></code></td>
                <td><?=e($b['shop_name'])?> (<?=e($b['vendor_code'])?>)</td>
                <td><?=e($b['filename'])?></td>
                <td><?=e($b['total_rows'])?></td>
                <td style="color:#256845"><?=e($b['success_rows'])?></td>
                <td style="color:#a13442"><?=e($b['failed_rows'])?></td>
                <td><span class="store-status status-<?=strtolower($b['status'])?>"><?=e($b['status'])?></span></td>
                <td><?=e($b['created_at'])?></td>
                <td>
                    <form method="post" style="display:inline">
                        <?=csrf_field()?>
                        <input type="hidden" name="action" value="view_batch">
                        <input type="hidden" name="batch_id" value="<?=$b['id']?>">
                        <button class="btn small secondary" type="submit">View Details</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$batches['rows']): ?><tr><td colspan="9" class="empty">No uploads yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>

    <?php if ($batchDetails): ?>
    <hr style="margin:24px 0">
    <div class="panel-head"><h2>Batch Details: <?=e($batchDetails['batch']['batch_code'] ?? '')?></h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Row</th><th>SKU</th><th>Title</th><th>Status</th><th>Error</th><th>Product ID</th></tr></thead>
        <tbody>
        <?php foreach ($batchDetails['rows'] as $r): ?>
            <tr>
                <td><?=e($r['row_number'])?></td>
                <td><?=e($r['product_code'] ?? '—')?></td>
                <td><?=e($r['title'] ?? '—')?></td>
                <td><span class="store-status status-<?=strtolower($r['status'])?>"><?=e($r['status'])?></span></td>
                <td><?=e($r['error_message'] ?? '—')?></td>
                <td><?=e($r['product_id'] ?? '—')?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>

<?php require APP_ROOT.'/includes/footer.php'; ?>