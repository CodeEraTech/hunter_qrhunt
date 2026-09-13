<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_vendor();

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];

$skuParam = trim((string) ($_GET['sku'] ?? ''));
$isEdit = $skuParam !== '';

$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
$pageSubtitle = $isEdit ? 'Update product details' : 'Submit a new product for approval';
require APP_ROOT . '/includes/vendor_header.php';

$errors = [];
$old = [
    'product_code' => '',
    'title' => '',
    'category' => '',
    'mrp' => '',
    'price' => '',
    'stock' => '',
    'min_quantity' => '',
    'description' => '',
    'qoin_enabled' => '0',
    'qoin_value' => '',
    'generate_qr' => '0',
    'image_url' => '',
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM vendor_products WHERE product_code = ? AND vendor_id = ? LIMIT 1');
    $stmt->execute([$skuParam, $vendorId]);
    $product = $stmt->fetch();
    if (!$product) {
        flash('error', 'Product not found or access denied.');
        redirect('/vendor/products/index.php');
    }
    $old['product_code'] = $product['product_code'];
    $old['title'] = $product['title'];
    $old['price'] = $product['price'];
    $old['description'] = $product['description'] ?? '';
    $old['image_url'] = $product['image_url'] ?? '';
    $meta = json_decode((string) $product['metadata'], true) ?? [];
    $old['category'] = $meta['category'] ?? '';
    $old['mrp'] = $meta['mrp'] ?? '';
    $old['stock'] = $meta['stock'] ?? '';
    $old['min_quantity'] = $meta['min_quantity'] ?? '';
    $old['qoin_enabled'] = ($meta['qoin_enabled'] ?? false) ? '1' : '0';
    $old['qoin_value'] = $meta['qoin_value'] ?? '';
}

if (is_post()) {
    if (!csrf_validate()) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $data = [
            'product_code' => trim((string) ($_POST['product_code'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? '')),
            'mrp' => trim((string) ($_POST['mrp'] ?? '')),
            'price' => trim((string) ($_POST['price'] ?? '')),
            'stock' => trim((string) ($_POST['stock'] ?? '')),
            'min_quantity' => trim((string) ($_POST['min_quantity'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'qoin_enabled' => isset($_POST['qoin_enabled']) ? '1' : '0',
            'qoin_value' => trim((string) ($_POST['qoin_value'] ?? '')),
            'generate_qr' => isset($_POST['generate_qr']) ? '1' : '0',
        ];
        $old = array_merge($old, $data);

        if ($data['product_code'] === '') $errors[] = 'SKU is required.';
        if ($data['title'] === '') $errors[] = 'Product name is required.';
        if ($data['price'] === '' || !is_numeric($data['price']) || (float)$data['price'] < 0) $errors[] = 'Valid price is required.';
        if ($data['mrp'] !== '' && (!is_numeric($data['mrp']) || (float)$data['mrp'] < 0)) $errors[] = 'MRP must be a valid number.';
        if ($data['stock'] !== '' && (!ctype_digit($data['stock']) || (int)$data['stock'] < 0)) $errors[] = 'Stock must be a non-negative integer.';
        if ($data['min_quantity'] !== '' && (!ctype_digit($data['min_quantity']) || (int)$data['min_quantity'] < 1)) $errors[] = 'Minimum quantity must be a positive integer.';
        if ($data['qoin_enabled'] === '1' && ($data['qoin_value'] === '' || !is_numeric($data['qoin_value']) || (float)$data['qoin_value'] <= 0)) $errors[] = 'Qoin value is required and must be positive when Qoin is enabled.';

        $imageUrl = $old['image_url'];
        if (!empty($_FILES['image']['name'])) {
            try {
                $imageUrl = store_image_upload('image', 'product');
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!$isEdit) {
            $check = db()->prepare('SELECT 1 FROM vendor_products WHERE product_code = ? LIMIT 1');
            $check->execute([$data['product_code']]);
            if ($check->fetch()) {
                $errors[] = 'SKU already exists.';
            }
        }

        if (!$errors) {
            $metadata = [
                'category' => $data['category'],
                'mrp' => $data['mrp'] !== '' ? (float) $data['mrp'] : null,
                'stock' => $data['stock'] !== '' ? (int) $data['stock'] : null,
                'min_quantity' => $data['min_quantity'] !== '' ? (int) $data['min_quantity'] : null,
                'qoin_enabled' => $data['qoin_enabled'] === '1',
                'qoin_value' => $data['qoin_value'] !== '' ? (float) $data['qoin_value'] : null,
            ];
            $metadata = array_filter($metadata, static fn($v) => $v !== null);

            if ($isEdit) {
                db()->prepare('UPDATE vendor_products SET title=?, price=?, metadata=?, image_url=?, description=?, status="PENDING_REVIEW", updated_at=NOW() WHERE product_code=? AND vendor_id=?')
                    ->execute([$data['title'], $data['price'], safe_json($metadata), $imageUrl, $data['description'], $data['product_code'], $vendorId]);
                flash('success', 'Product updated and resubmitted for review.');
            } else {
                db()->prepare('INSERT INTO vendor_products (vendor_id, product_code, title, price, metadata, image_url, description, status, submitted_at) VALUES (?,?,?,?,?,?,?,"PENDING_REVIEW",NOW())')
                    ->execute([$vendorId, $data['product_code'], $data['title'], $data['price'], safe_json($metadata), $imageUrl, $data['description']]);
                flash('success', 'Product submitted for approval.');
            }

            if ($data['generate_qr'] === '1') {
                $qrStmt = db()->prepare('SELECT id FROM vendor_qr_codes WHERE vendor_id=? AND reference_type="PRODUCT" AND reference_id=? LIMIT 1');
                $qrStmt->execute([$vendorId, $data['product_code']]);
                if (!$qrStmt->fetch()) {
                    $qrId = 'QR-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                    db()->prepare('INSERT INTO vendor_qr_codes (vendor_id, qr_code, reference_type, reference_id, payload, created_at) VALUES (?,?,?,?,?,NOW())')
                        ->execute([$vendorId, $qrId, 'PRODUCT', $data['product_code'], safe_json(['product_code' => $data['product_code']])]);
                }
            }

            redirect('/vendor/products/index.php');
        }
    }
}

function get_meta_input(array $old, string $key): string
{
    return e($old[$key] ?? '');
}
?>

<section class="store-panel" aria-labelledby="form-heading">
    <div class="panel-head">
        <h2 id="form-heading"><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></h2>
    </div>

    <?php foreach (take_flashes() as $flash): ?>
        <div class="toast toast-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <?php if ($errors): ?>
        <div class="toast toast-error" role="alert" style="margin-bottom:16px;">
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="campaign-sections" style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px;">
        <?= csrf_field() ?>

        <div class="field" style="grid-column:1/-1;">
            <label class="field-label-row"><span>Product Image</span></label>
            <input type="file" name="image" accept="image/*" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:10px;">
            <?php if ($old['image_url']): ?>
                <p style="margin:8px 0 0;">
                    <img src="<?= e($old['image_url']) ?>" alt="Current image" style="max-width:120px; max-height:120px; border-radius:8px; border:1px solid var(--line);">
                    <br><small>Current image. Upload a new one to replace.</small>
                </p>
            <?php endif; ?>
        </div>

        <div class="field" style="grid-column:1/-1;">
            <label class="field-label-row"><span>Product Name <span style="color:red">*</span></span></label>
            <input type="text" name="title" value="<?= get_meta_input($old, 'title') ?>" required placeholder="Enter product name" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px;">
        </div>

        <div class="field">
            <label class="field-label-row"><span>SKU (Product Code) <span style="color:red">*</span></span></label>
            <input type="text" name="product_code" value="<?= get_meta_input($old, 'product_code') ?>" <?= $isEdit ? 'readonly' : '' ?> required placeholder="Unique SKU" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; <?= $isEdit ? 'background:#f5f5f5;' : '' ?>">
            <?php if ($isEdit): ?>
                <small style="color:var(--muted);">SKU cannot be changed after creation.</small>
            <?php endif; ?>
        </div>

        <div class="field">
            <label class="field-label-row"><span>Category</span></label>
            <input type="text" name="category" value="<?= get_meta_input($old, 'category') ?>" placeholder="e.g., Electronics, Clothing" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px;">
        </div>

        <div class="field">
            <label class="field-label-row"><span>MRP <span style="color:red">*</span></span></label>
            <input type="number" step="0.01" min="0" name="mrp" value="<?= get_meta_input($old, 'mrp') ?>" placeholder="Maximum Retail Price" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px;">
        </div>

        <div class="field">
            <label class="field-label-row"><span>Store Selling Price <span style="color:red">*</span></span></label>
            <input type="number" step="0.01" min="0" name="price" value="<?= get_meta_input($old, 'price') ?>" required placeholder="Your selling price" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px;">
        </div>

        <div class="field">
            <label class="field-label-row"><span>Stock Quantity</span></label>
            <input type="number" min="0" name="stock" value="<?= get_meta_input($old, 'stock') ?>" placeholder="Available units" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px;">
        </div>

        <div class="field">
            <label class="field-label-row"><span>Minimum Order Quantity</span></label>
            <input type="number" min="1" name="min_quantity" value="<?= get_meta_input($old, 'min_quantity') ?>" placeholder="Min units per order" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px;">
        </div>

        <div class="field" style="grid-column:1/-1;">
            <label class="field-label-row"><span>Description</span></label>
            <textarea name="description" rows="4" placeholder="Product description…" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px; font-family:inherit;"><?= e($old['description'] ?? '') ?></textarea>
        </div>

        <div class="field" style="grid-column:1/-1;">
            <div class="form-section-title">Qoin / Reward Configuration</div>
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                <input type="checkbox" name="qoin_enabled" value="1" <?= checked($old['qoin_enabled'], '1') ?> style="width:auto;">
                <span>Enable Qoin rewards for this product</span>
            </label>
        </div>

        <div class="field" id="qoin-value-field" style="display:<?= $old['qoin_enabled'] === '1' ? 'block' : 'none' ?>;">
            <label class="field-label-row"><span>Qoin Value (per unit)</span></label>
            <input type="number" step="0.01" min="0" name="qoin_value" value="<?= get_meta_input($old, 'qoin_value') ?>" placeholder="Qoin amount customer earns" style="width:100%; padding:12px; border:1px solid var(--line); border-radius:10px;">
        </div>

        <div class="field" style="grid-column:1/-1;">
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                <input type="checkbox" name="generate_qr" value="1" <?= checked($old['generate_qr'], '1') ?> style="width:auto;">
                <span>Generate QR code for this product after submission</span>
            </label>
        </div>

        <div class="field" style="grid-column:1/-1; display:flex; gap:12px; margin-top:8px;">
            <button type="submit" class="btn primary"><?= $isEdit ? 'Update Product' : 'Submit for Approval' ?></button>
            <a class="btn secondary" href="<?= e(app_url('/vendor/products/index.php')) ?>">Cancel</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var checkbox = document.querySelector('input[name="qoin_enabled"]');
    var field = document.getElementById('qoin-value-field');
    if (checkbox && field) {
        checkbox.addEventListener('change', function() {
            field.style.display = this.checked ? 'block' : 'none';
        });
    }
});
</script>

<?php require APP_ROOT . '/includes/vendor_footer.php'; ?>