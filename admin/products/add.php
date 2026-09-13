<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_once APP_ROOT.'/includes/product_management.php';

$adm = admin();
$id = (int) (input('id') ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    require_permission('products.edit');
} else {
    require_permission('products.create');
}

$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
$pageSubtitle = $isEdit ? 'Update product details' : 'Create a new product';
$errors = [];
$old = [
    'product_code' => '',
    'title' => '',
    'category_id' => '',
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
    'status' => 'PENDING_REVIEW',
];

if ($isEdit) {
    $product = product_get($id);
    if (!$product) {
        flash('error', 'Product not found.');
        redirect('/admin/products/index.php');
    }
    $meta = $product['metadata'] ? json_decode((string) $product['metadata'], true) : [];
    $old = array_merge($old, [
        'product_code' => $product['product_code'],
        'title' => $product['title'],
        'category_id' => $product['category_id'] ?? '',
        'category' => $meta['category'] ?? '',
        'mrp' => $meta['mrp'] ?? '',
        'price' => $product['price'],
        'stock' => $meta['stock'] ?? '',
        'min_quantity' => $meta['min_quantity'] ?? '',
        'description' => $product['description'] ?? '',
        'qoin_enabled' => ($meta['qoin_enabled'] ?? false) ? '1' : '0',
        'qoin_value' => $meta['qoin_value'] ?? '',
        'image_url' => $product['image_url'] ?? '',
        'status' => $product['status'],
    ]);
}

if (is_post()) {
    verify_csrf();
    $data = [
        'product_code' => trim((string) input('product_code')),
        'title' => trim((string) input('title')),
        'category_id' => input('category_id') ? (int) input('category_id') : null,
        'category' => trim((string) input('category')),
        'mrp' => trim((string) input('mrp')),
        'price' => trim((string) input('price')),
        'stock' => trim((string) input('stock')),
        'min_quantity' => trim((string) input('min_quantity')),
        'description' => trim((string) input('description')),
        'qoin_enabled' => input('qoin_enabled') ? '1' : '0',
        'qoin_value' => trim((string) input('qoin_value')),
        'generate_qr' => input('generate_qr') ? '1' : '0',
        'status' => input('status') ?? 'PENDING_REVIEW',
    ];
    $old = array_merge($old, $data);

    if ($data['product_code'] === '') $errors[] = 'SKU is required.';
    if ($data['title'] === '') $errors[] = 'Product name is required.';
    if ($data['price'] === '' || !is_numeric($data['price']) || (float) $data['price'] < 0) $errors[] = 'Valid price is required.';
    if ($data['mrp'] !== '' && (!is_numeric($data['mrp']) || (float) $data['mrp'] < 0)) $errors[] = 'MRP must be a valid number.';
    if ($data['stock'] !== '' && (!ctype_digit($data['stock']) || (int) $data['stock'] < 0)) $errors[] = 'Stock must be a non-negative integer.';
    if ($data['min_quantity'] !== '' && (!ctype_digit($data['min_quantity']) || (int) $data['min_quantity'] < 1)) $errors[] = 'Minimum quantity must be a positive integer.';
    if ($data['qoin_enabled'] === '1' && ($data['qoin_value'] === '' || !is_numeric($data['qoin_value']) || (float) $data['qoin_value'] <= 0)) $errors[] = 'Qoin value is required and must be positive when Qoin is enabled.';

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
        $vendorId = (int) ($old['vendor_id'] ?? $adm['id']); // In admin, we need a vendor_id - for now default to 1 or require vendor selection
        // Get a default vendor for admin-created products
        $defaultVendor = db()->query("SELECT id FROM vendors WHERE status='ACTIVE' ORDER BY id LIMIT 1")->fetch();
        if (!$defaultVendor) {
            $errors[] = 'No active vendor found. Please create a vendor first.';
        } else {
            $vendorId = (int) $defaultVendor['id'];
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

        try {
            if ($isEdit) {
                $oldProduct = product_get($id);
                product_update($id, [
                    'title' => $data['title'],
                    'price' => $data['price'],
                    'metadata' => safe_json($metadata),
                    'image_url' => $imageUrl,
                    'description' => $data['description'],
                    'category_id' => $data['category_id'],
                ], $adm['id']);
                flash('success', 'Product updated and resubmitted for review.');
            } else {
                $newId = product_create([
                    'product_code' => $data['product_code'],
                    'title' => $data['title'],
                    'price' => $data['price'],
                    'metadata' => safe_json($metadata),
                    'image_url' => $imageUrl,
                    'description' => $data['description'],
                    'category_id' => $data['category_id'],
                ], $vendorId, $adm['id']);
                flash('success', 'Product created and submitted for approval.');
            }

            if ($data['generate_qr'] === '1') {
                $prod = $isEdit ? product_get($id) : product_get_by_code($data['product_code']);
                if ($prod && !product_qr_get($prod['id'])) {
                    product_qr_create($prod['id'], $prod['product_code'], $prod['vendor_id'], $adm['id']);
                }
            }

            redirect('/admin/products/index.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$categories = product_categories(['status' => 'ACTIVE']);

require APP_ROOT.'/includes/header.php';
?>
<section class="panel" style="max-width:900px">
    <div class="panel-head">
        <h2><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></h2>
    </div>

    <?php foreach (take_flashes() as $f): ?>
        <div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div>
    <?php endforeach; ?>

    <?php if ($errors): ?>
        <div class="alert error">
            <ul style="margin:0;padding-left:20px">
                <?php foreach ($errors as $e): ?><li><?=e($e)?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="campaign-sections" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
        <?=csrf_field()?>

        <div class="field" style="grid-column:1/-1">
            <label class="field-label-row"><span>Product Image</span></label>
            <input type="file" name="image" accept="image/*" style="width:100%;padding:10px;border:1px solid var(--line);border-radius:10px">
            <?php if ($old['image_url']): ?>
                <p style="margin:8px 0 0">
                    <img src="<?=e($old['image_url'])?>" alt="Current" style="max-width:120px;max-height:120px;border-radius:8px;border:1px solid var(--line)">
                    <br><small style="color:var(--muted)">Current image. Upload new to replace.</small>
                </p>
            <?php endif; ?>
        </div>

        <div class="field" style="grid-column:1/-1">
            <label class="field-label-row"><span>Product Name <span style="color:red">*</span></span></label>
            <input type="text" name="title" value="<?=e($old['title'])?>" required placeholder="Enter product name" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>

        <div class="field">
            <label class="field-label-row"><span>SKU (Product Code) <span style="color:red">*</span></span></label>
            <input type="text" name="product_code" value="<?=e($old['product_code'])?>" <?= $isEdit ? 'readonly' : '' ?> required placeholder="Unique SKU" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px<?= $isEdit ? ';background:#f5f5f5' : '' ?>">
            <?php if ($isEdit): ?><small style="color:var(--muted)">SKU cannot be changed after creation.</small><?php endif; ?>
        </div>

        <div class="field">
            <label class="field-label-row"><span>Category</span></label>
            <select name="category_id" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
                <option value="">— Select Category —</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?=$c['id']?>" <?=selected($old['category_id'] ?? '', (string)$c['id'])?>><?=$c['name']?> (<?=$c['code']?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field-label-row"><span>Category (Free Text)</span></label>
            <input type="text" name="category" value="<?=e($old['category'])?>" placeholder="e.g., Electronics, Clothing" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>

        <div class="field">
            <label class="field-label-row"><span>MRP <span style="color:red">*</span></span></label>
            <input type="number" step="0.01" min="0" name="mrp" value="<?=e($old['mrp'])?>" placeholder="Maximum Retail Price" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>

        <div class="field">
            <label class="field-label-row"><span>Store Selling Price <span style="color:red">*</span></span></label>
            <input type="number" step="0.01" min="0" name="price" value="<?=e($old['price'])?>" required placeholder="Your selling price" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>

        <div class="field">
            <label class="field-label-row"><span>Stock Quantity</span></label>
            <input type="number" min="0" name="stock" value="<?=e($old['stock'])?>" placeholder="Available units" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>

        <div class="field">
            <label class="field-label-row"><span>Minimum Order Quantity</span></label>
            <input type="number" min="1" name="min_quantity" value="<?=e($old['min_quantity'])?>" placeholder="Min units per order" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>

        <div class="field" style="grid-column:1/-1">
            <label class="field-label-row"><span>Description</span></label>
            <textarea name="description" rows="4" placeholder="Product description…" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;font-family:inherit"><?=e($old['description'] ?? '')?></textarea>
        </div>

        <div class="field" style="grid-column:1/-1">
            <div class="form-section-title">Qoin / Reward Configuration</div>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="checkbox" name="qoin_enabled" value="1" <?=checked($old['qoin_enabled'],'1')?> style="width:auto">
                <span>Enable Qoin rewards for this product</span>
            </label>
        </div>

        <div class="field" id="qoin-value-field" style="display:<?= $old['qoin_enabled'] === '1' ? 'block' : 'none' ?>">
            <label class="field-label-row"><span>Qoin Value (per unit)</span></label>
            <input type="number" step="0.01" min="0" name="qoin_value" value="<?=e($old['qoin_value'])?>" placeholder="Qoin amount customer earns" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>

        <div class="field" style="grid-column:1/-1">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="checkbox" name="generate_qr" value="1" <?=checked($old['generate_qr'],'1')?> style="width:auto">
                <span>Generate QR code for this product after submission</span>
            </label>
        </div>

        <div class="field" style="grid-column:1/-1;display:flex;gap:12px;margin-top:8px">
            <button type="submit" class="btn primary"><?= $isEdit ? 'Update Product' : 'Submit for Approval' ?></button>
            <a class="btn secondary" href="<?=e(app_url('/admin/products/index.php'))?>">Cancel</a>
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

<?php require APP_ROOT.'/includes/footer.php'; ?>