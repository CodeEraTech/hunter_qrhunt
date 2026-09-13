<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('products.view');
require_once APP_ROOT.'/includes/product_management.php';

$pageTitle = 'Product Directory';
$pageSubtitle = 'Central product catalog management';
$adm = admin();

$q = trim((string) input('q'));
$status = input('status');
$categoryId = input('category_id');
$vendorId = input('vendor_id');
$qoinFilter = input('qoin_enabled');
$page = max(1, (int) (input('page') ?? 1));
$perPage = 20;

$filters = [
    'q' => $q ?: null,
    'status' => $status ?: null,
    'category_id' => $categoryId ?: null,
    'vendor_id' => $vendorId ?: null,
    'qoin_enabled' => $qoinFilter ?: null,
];

if (input('export') === '1') {
    require_permission('products.export');
    $all = products_list($filters, 1, 10000);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=products-'.date('Ymd-His').'.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Product ID','SKU','Product Name','Vendor','Category','MRP','Selling Price','Stock','Min Qty','Qoin Enabled','Status','Submitted']);
    foreach ($all['rows'] as $r) {
        $meta = $r['metadata'] ? json_decode((string) $r['metadata'], true) : [];
        fputcsv($out, [
            $r['id'], $r['product_code'], $r['title'],
            $r['shop_name'].' ('.$r['vendor_code'].')',
            $r['category_name'] ?? $meta['category'] ?? '—',
            $meta['mrp'] ?? '—',
            $r['price'],
            $meta['stock'] ?? '—',
            $meta['min_quantity'] ?? '—',
            ($meta['qoin_enabled'] ?? false) ? 'Yes' : 'No',
            $r['status'],
            $r['submitted_at']
        ]);
    }
    fclose($out);
    exit;
}

$data = products_list($filters, $page, $perPage);
$categories = product_categories(['status' => 'ACTIVE']);

require APP_ROOT.'/includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <div><h2>Product Directory</h2><p class="help">Search, filter, and manage all products across vendors.</p></div>
        <?php if (can('products.export')): ?>
        <a class="btn secondary" href="?<?=http_build_query(array_merge($filters, ['export'=>1]))?>">Export CSV</a>
        <?php endif; ?>
        <?php if (can('products.create')): ?>
        <a class="btn primary" href="<?=e(app_url('/admin/products/add.php'))?>">Add Product</a>
        <?php endif; ?>
    </div>

    <form class="filters" method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <input type="search" name="q" value="<?=e($q)?>" placeholder="Product ID, SKU, title or vendor" style="min-width:260px">
        <select name="status">
            <option value="">All Statuses</option>
            <?php foreach (['PENDING_REVIEW','APPROVED','REJECTED','DISABLED','RESUBMITTED'] as $st): ?>
            <option value="<?=$st?>" <?=selected($status,$st)?>><?=$st?></option>
            <?php endforeach; ?>
        </select>
        <select name="category_id">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?=$c['id']?>" <?=selected($categoryId,(string)$c['id'])?>><?=$c['name']?></option>
            <?php endforeach; ?>
        </select>
        <select name="vendor_id">
            <option value="">All Vendors</option>
            <?php
            $vendors = db()->query("SELECT id, vendor_code, shop_name FROM vendors WHERE status='ACTIVE' ORDER BY shop_name")->fetchAll();
            foreach ($vendors as $v): ?>
            <option value="<?=$v['id']?>" <?=selected($vendorId,(string)$v['id'])?>><?=$v['shop_name']?> (<?=$v['vendor_code']?>)</option>
            <?php endforeach; ?>
        </select>
        <label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="qoin_enabled" value="1" <?=checked($qoinFilter,'1')?>> Qoin Enabled</label>
        <button class="btn" type="submit">Filter</button>
        <?php if ($q || $status || $categoryId || $vendorId || $qoinFilter): ?>
        <a class="btn secondary" href="<?=e(app_url('/admin/products/index.php'))?>">Clear</a>
        <?php endif; ?>
    </form>

    <?php foreach (take_flashes() as $f): ?>
        <div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div>
    <?php endforeach; ?>

    <div class="table-wrap"><table>
        <thead>
            <tr>
                <th>ID</th><th>SKU</th><th>Product Name</th><th>Vendor</th><th>Category</th>
                <th>MRP</th><th>Selling Price</th><th>Stock</th><th>Min Qty</th>
                <th>Qoin</th><th>Status</th><th>Submitted</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($data['rows'] as $r):
            $meta = $r['metadata'] ? json_decode((string) $r['metadata'], true) : [];
            $stock = $meta['stock'] ?? '—';
            $minQty = $meta['min_quantity'] ?? '—';
            $mrp = $meta['mrp'] ?? '—';
            $qoin = ($meta['qoin_enabled'] ?? false) ? 'Yes' : 'No';
            $statusClass = match($r['status']) {
                'APPROVED' => 'status-approved',
                'PENDING_REVIEW','RESUBMITTED' => 'status-pending',
                'REJECTED' => 'status-rejected',
                'DISABLED' => 'status-inactive',
                default => ''
            };
        ?>
            <tr>
                <td><code>#<?=e($r['id'])?></code></td>
                <td><strong><?=e($r['product_code'])?></strong></td>
                <td><?=e($r['title'])?><br><small style="color:var(--muted)"><?=e($r['description'] ? mb_substr($r['description'],0,60).'…' : '—')?></small></td>
                <td><?=e($r['shop_name'])?><br><small style="color:var(--muted)"><?=e($r['vendor_code'])?></small></td>
                <td><?=e($r['category_name'] ?? $meta['category'] ?? '—')?></td>
                <td>₹<?=e(number_format((float)$mrp,2))?></td>
                <td>₹<?=e(number_format((float)$r['price'],2))?></td>
                <td><?=e($stock)?></td>
                <td><?=e($minQty)?></td>
                <td><?=e($qoin)?></td>
                <td><span class="store-status <?=e($statusClass)?>"><?=e($r['status'])?></span></td>
                <td><?=e($r['submitted_at'])?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn small secondary" href="<?=e(app_url('/admin/products/add.php?id='.$r['id']))?>">Edit</a>
                        <a class="btn small" href="<?=e(app_url('/admin/products/history.php?id='.$r['id']))?>">History</a>
                        <?php if (can('products.qr.manage')): ?>
                        <a class="btn small" href="<?=e(app_url('/admin/products/qr.php?id='.$r['id']))?>">QR</a>
                        <?php endif; ?>
                        <?php if (can('products.approve') && in_array($r['status'],['PENDING_REVIEW','RESUBMITTED'],true)): ?>
                        <form method="post" action="<?=e(app_url('/admin/products/approval.php'))?>" style="display:inline">
                            <?=csrf_field()?>
                            <input type="hidden" name="ids[]" value="<?=$r['id']?>">
                            <button class="btn small success" name="action" value="APPROVE">Approve</button>
                            <button class="btn small danger" name="action" value="REJECT" onclick="return requireModerationNote()">Reject</button>
                            <input name="admin_note" placeholder="Reason" style="width:100px;padding:6px;border-radius:6px;border:1px solid var(--line)">
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$data['rows']): ?>
            <tr><td colspan="13" class="empty">No products found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div>

    <?php if ($data['lastPage'] > 1): ?>
    <nav class="pagination" style="display:flex;justify-content:center;gap:8px;margin-top:16px">
        <?php for ($p = max(1,$data['page']-2); $p <= min($data['lastPage'],$data['page']+2); $p++): ?>
            <?php $params = array_merge($filters, ['page'=>$p]); ?>
            <a class="btn <?= $p === $data['page'] ? 'primary' : 'secondary' ?>" href="?<?=http_build_query($params)?>"><?=$p?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</section>
<?php require APP_ROOT.'/includes/footer.php'; ?>