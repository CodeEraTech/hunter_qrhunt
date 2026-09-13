<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('products.view');
require_once APP_ROOT.'/includes/product_management.php';

$pageTitle = 'Product Dashboard';
$pageSubtitle = 'Product management overview and statistics';

$stats = product_stats();
$recent = product_recent(10);
$catSummary = product_categories_summary();

require APP_ROOT.'/includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Product Overview</h2></div>
    <div class="grid stats">
        <div class="card"><span class="label">Total Products</span><span class="value"><?=e((int)($stats['total'] ?? 0))?></span></div>
        <div class="card"><span class="label">Active (Approved)</span><span class="value" style="color:#256845"><?=e((int)($stats['active'] ?? 0))?></span></div>
        <div class="card"><span class="label">Pending Approval</span><span class="value" style="color:#825115"><?=e((int)($stats['pending'] ?? 0))?></span></div>
        <div class="card"><span class="label">Rejected</span><span class="value" style="color:#a13442"><?=e((int)($stats['rejected'] ?? 0))?></span></div>
        <div class="card"><span class="label">Disabled</span><span class="value" style="color:#6c7b8a"><?=e((int)($stats['disabled'] ?? 0))?></span></div>
        <div class="card"><span class="label">Out of Stock</span><span class="value" style="color:#d78909"><?=e((int)($stats['out_of_stock'] ?? 0))?></span></div>
        <div class="card"><span class="label">Vendors with Products</span><span class="value"><?=e((int)($stats['vendors_with_products'] ?? 0))?></span></div>
        <div class="card"><span class="label">Product Categories</span><span class="value"><?=e(count(product_categories(['status'=>'ACTIVE'])))?></span></div>
    </div>
</section>

<section class="grid" style="grid-template-columns:2fr 1fr;gap:24px">
    <section class="panel">
        <div class="panel-head"><h2>Recently Added Products</h2></div>
        <div class="table-wrap"><table>
            <thead><tr><th>SKU</th><th>Product</th><th>Vendor</th><th>Category</th><th>Price</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r):
                $meta = $r['metadata'] ? json_decode((string) $r['metadata'], true) : [];
                $statusClass = match($r['status']) {
                    'APPROVED' => 'status-approved',
                    'PENDING_REVIEW','RESUBMITTED' => 'status-pending',
                    'REJECTED' => 'status-rejected',
                    'DISABLED' => 'status-inactive',
                    default => ''
                };
            ?>
            <tr>
                <td><strong><?=e($r['product_code'])?></strong></td>
                <td><?=e($r['title'])?></td>
                <td><?=e($r['shop_name'])?> (<?=e($r['vendor_code'])?>)</td>
                <td><?=e($r['category_name'] ?? $meta['category'] ?? '—')?></td>
                <td>₹<?=e(number_format((float)$r['price'],2))?></td>
                <td><span class="store-status <?=e($statusClass)?>"><?=e($r['status'])?></span></td>
                <td><?=e($r['submitted_at'])?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?><tr><td colspan="7" class="empty">No products yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
        <a class="btn secondary" href="<?=e(app_url('/admin/products/index.php'))?>">View All Products</a>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Top Categories</h2></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Category</th><th>Code</th><th>Products</th></tr></thead>
            <tbody>
            <?php foreach ($catSummary as $c): ?>
            <tr><td><?=e($c['name'])?></td><td><code><?=e($c['code'])?></code></td><td><?=e((int)$c['product_count'])?></td></tr>
            <?php endforeach; ?>
            <?php if (!$catSummary): ?><tr><td colspan="3" class="empty">No categories yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
        <a class="btn secondary" href="<?=e(app_url('/admin/products/categories.php'))?>">Manage Categories</a>
    </section>
</section>

<section class="panel">
    <div class="panel-head"><h2>Quick Actions</h2></div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        <?php if (can('products.create')): ?><a class="btn primary" href="<?=e(app_url('/admin/products/add.php'))?>">Add Product</a><?php endif; ?>
        <?php if (can('products.bulk_upload')): ?><a class="btn secondary" href="<?=e(app_url('/admin/products/upload.php'))?>">Bulk Upload</a><?php endif; ?>
        <?php if (can('products.approve')): ?><a class="btn success" href="<?=e(app_url('/admin/products/approval.php'))?>">Approval Queue</a><?php endif; ?>
        <?php if (can('products.categories.manage')): ?><a class="btn" href="<?=e(app_url('/admin/products/categories.php'))?>">Categories</a><?php endif; ?>
        <?php if (can('products.qr.manage')): ?><a class="btn" href="<?=e(app_url('/admin/products/qr.php'))?>">QR Codes</a><?php endif; ?>
    </div>
</section>

<?php require APP_ROOT.'/includes/footer.php'; ?>