<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('products.categories.manage');
require_once APP_ROOT.'/includes/product_management.php';

$pageTitle = 'Product Categories';
$pageSubtitle = 'Manage product catalog categories';
$adm = admin();

$action = input('action');
$catId = (int) (input('id') ?? 0);
$isEdit = $action === 'edit' && $catId > 0;

if (is_post()) {
    verify_csrf();
    $act = input('act');
    $data = [
        'name' => trim((string) input('name')),
        'code' => strtoupper(trim((string) input('code'))),
        'icon_url' => trim((string) input('icon_url')),
        'description' => trim((string) input('description')),
        'status' => input('status') ?? 'ACTIVE',
        'sort_order' => (int) input('sort_order'),
    ];

    if ($data['name'] === '') {
        flash('error', 'Category name is required.');
    } elseif ($data['code'] === '') {
        flash('error', 'Category code is required.');
    } else {
        try {
            if ($act === 'create') {
                product_category_create($data, $adm['id']);
                flash('success', 'Category created.');
            } elseif ($act === 'update' && $catId) {
                product_category_update($catId, $data);
                flash('success', 'Category updated.');
            } elseif ($act === 'delete' && $catId) {
                product_category_delete($catId);
                flash('success', 'Category deleted.');
            }
            redirect('/admin/products/categories.php');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
    }
}

$categories = product_categories();
$editCat = null;
if ($isEdit) {
    $editCat = product_category_get($catId);
}

require APP_ROOT.'/includes/header.php';
?>
<section class="panel" style="max-width:900px">
    <div class="panel-head">
        <h2><?= $isEdit ? 'Edit Category' : 'Product Categories' ?></h2>
    </div>

    <?php foreach (take_flashes() as $f): ?>
        <div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div>
    <?php endforeach; ?>

    <?php if ($isEdit && $editCat): ?>
    <form method="post" class="campaign-sections" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;max-width:700px">
        <?=csrf_field()?>
        <input type="hidden" name="act" value="update">
        <input type="hidden" name="id" value="<?=$catId?>">

        <div class="field">
            <label class="field-label-row"><span>Name <span style="color:red">*</span></span></label>
            <input type="text" name="name" value="<?=e($editCat['name'])?>" required style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>
        <div class="field">
            <label class="field-label-row"><span>Code <span style="color:red">*</span></span></label>
            <input type="text" name="code" value="<?=e($editCat['code'])?>" required style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;text-transform:uppercase">
        </div>
        <div class="field">
            <label class="field-label-row"><span>Icon URL</span></label>
            <input type="url" name="icon_url" value="<?=e($editCat['icon_url'] ?? '')?>" placeholder="https://..." style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>
        <div class="field">
            <label class="field-label-row"><span>Sort Order</span></label>
            <input type="number" name="sort_order" min="0" value="<?=e($editCat['sort_order'] ?? 0)?>" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
        </div>
        <div class="field" style="grid-column:1/-1">
            <label class="field-label-row"><span>Description</span></label>
            <textarea name="description" rows="3" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;font-family:inherit"><?=e($editCat['description'] ?? '')?></textarea>
        </div>
        <div class="field">
            <label class="field-label-row"><span>Status</span></label>
            <select name="status" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
                <option value="ACTIVE" <?=selected($editCat['status'],'ACTIVE')?>>Active</option>
                <option value="INACTIVE" <?=selected($editCat['status'],'INACTIVE')?>>Inactive</option>
            </select>
        </div>

        <div class="field" style="grid-column:1/-1;display:flex;gap:12px;margin-top:8px">
            <button class="btn primary" type="submit">Update Category</button>
            <a class="btn secondary" href="<?=e(app_url('/admin/products/categories.php'))?>">Cancel</a>
        </div>
    </form>
    <?php else: ?>
    <form method="post" class="stack" style="max-width:700px;margin-bottom:24px">
        <?=csrf_field()?>
        <input type="hidden" name="act" value="create">
        <div class="campaign-sections" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
            <div class="field">
                <label class="field-label-row"><span>Name <span style="color:red">*</span></span></label>
                <input type="text" name="name" required placeholder="Category name" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
            </div>
            <div class="field">
                <label class="field-label-row"><span>Code <span style="color:red">*</span></span></label>
                <input type="text" name="code" required placeholder="Unique code (e.g., ELECTRONICS)" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;text-transform:uppercase">
            </div>
            <div class="field">
                <label class="field-label-row"><span>Icon URL</span></label>
                <input type="url" name="icon_url" placeholder="https://..." style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
            </div>
            <div class="field">
                <label class="field-label-row"><span>Sort Order</span></label>
                <input type="number" name="sort_order" min="0" value="0" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
            </div>
            <div class="field" style="grid-column:1/-1">
                <label class="field-label-row"><span>Description</span></label>
                <textarea name="description" rows="3" placeholder="Optional description" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;font-family:inherit"></textarea>
            </div>
            <div class="field">
                <label class="field-label-row"><span>Status</span></label>
                <select name="status" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:10px">
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive</option>
                </select>
            </div>
            <div class="field" style="grid-column:1/-1;display:flex;gap:12px">
                <button class="btn primary" type="submit">Create Category</button>
            </div>
        </div>
    </form>

    <hr style="margin:24px 0">

    <div class="panel-head"><h2>Existing Categories</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Name</th><th>Code</th><th>Icon</th><th>Products</th><th>Vendors</th><th>Status</th><th>Order</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td><code>#<?=e($c['id'])?></code></td>
                <td><?=e($c['name'])?></td>
                <td><code><?=e($c['code'])?></code></td>
                <td><?php if ($c['icon_url']): ?><img src="<?=e($c['icon_url'])?>" alt="" style="width:24px;height:24px;border-radius:4px"><?php else: ?>—<?php endif; ?></td>
                <td><?=e((int)$c['product_count'])?></td>
                <td><?=e((int)$c['vendor_count'])?></td>
                <td><span class="store-status status-<?=strtolower($c['status'])?>"><?=e($c['status'])?></span></td>
                <td><?=e($c['sort_order'])?></td>
                <td><?=e($c['created_at'])?></td>
                <td>
                    <a class="btn small secondary" href="<?=e(app_url('/admin/products/categories.php?action=edit&id='.$c['id']))?>">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this category?')">
                        <?=csrf_field()?>
                        <input type="hidden" name="act" value="delete">
                        <input type="hidden" name="id" value="<?=$c['id']?>">
                        <button class="btn small danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$categories): ?><tr><td colspan="10" class="empty">No categories yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>

<?php require APP_ROOT.'/includes/footer.php'; ?>