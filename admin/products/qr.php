<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('products.qr.manage');
require_once APP_ROOT.'/includes/product_management.php';

$pageTitle = 'Product QR Codes';
$pageSubtitle = 'Centralized product QR code management';
$adm = admin();

$action = input('action');
$qrId = (int) (input('id') ?? 0);

if (is_post()) {
    verify_csrf();
    if ($action === 'regenerate' && $qrId) {
        if (product_qr_regenerate($qrId, $adm['id'])) {
            flash('success', 'QR code regenerated.');
        } else {
            flash('error', 'Failed to regenerate QR code.');
        }
        redirect('/admin/products/qr.php');
    } elseif ($action === 'archive' && $qrId) {
        db()->prepare('UPDATE product_qr_codes SET status="ARCHIVED" WHERE id=?')->execute([$qrId]);
        flash('success', 'QR code archived.');
        redirect('/admin/products/qr.php');
    }
}

$q = trim((string) input('q'));
$vendorId = input('vendor_id');
$status = input('status');
$page = max(1, (int) (input('page') ?? 1));
$perPage = 20;

$filters = ['q' => $q ?: null, 'vendor_id' => $vendorId ?: null, 'status' => $status ?: null];
$data = product_qr_list($filters, $page, $perPage);
$vendors = db()->query("SELECT id, vendor_code, shop_name FROM vendors WHERE status='ACTIVE' ORDER BY shop_name")->fetchAll();

require APP_ROOT.'/includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Product QR Codes</h2>
    </div>

    <form class="filters" method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <input type="search" name="q" value="<?=e($q)?>" placeholder="QR Code, SKU, Product" style="min-width:200px">
        <select name="vendor_id">
            <option value="">All Vendors</option>
            <?php foreach ($vendors as $v): ?>
            <option value="<?=$v['id']?>" <?=selected($vendorId,(string)$v['id'])?>><?=$v['shop_name']?> (<?=$v['vendor_code']?>)</option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <option value="ACTIVE" <?=selected($status,'ACTIVE')?>>Active</option>
            <option value="REGENERATED" <?=selected($status,'REGENERATED')?>>Regenerated</option>
            <option value="ARCHIVED" <?=selected($status,'ARCHIVED')?>>Archived</option>
        </select>
        <button class="btn" type="submit">Filter</button>
        <?php if ($q || $vendorId || $status): ?><a class="btn secondary" href="<?=e(app_url('/admin/products/qr.php'))?>">Clear</a><?php endif; ?>
    </form>

    <?php foreach (take_flashes() as $f): ?><div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div><?php endforeach; ?>

    <div class="table-wrap"><table>
        <thead>
            <tr><th>QR Code</th><th>Product</th><th>SKU</th><th>Vendor</th><th>Status</th><th>Generated</th><th>Regenerated</th><th>By</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><code><?=e($r['qr_code'])?></code></td>
                <td><?=e($r['title'])?></td>
                <td><strong><?=e($r['product_code'])?></strong></td>
                <td><?=e($r['shop_name'])?> (<?=e($r['vendor_code'])?>)</td>
                <td><span class="store-status status-<?=strtolower($r['status'])?>"><?=e($r['status'])?></span></td>
                <td><?=e($r['created_at'])?></td>
                <td><?=e($r['regenerated_at'] ?? '—')?></td>
                <td><?=e($r['generated_by'] ? '#'.$r['generated_by'] : '—')?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <a class="btn small secondary" href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?=urlencode('join://product?id='.$r['product_code'])?>" target="_blank">View</a>
                        <a class="btn small secondary" href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?=urlencode('join://product?id='.$r['product_code'])?>" download>Download</a>
                        <?php if ($r['status'] === 'ACTIVE'): ?>
                        <form method="post" style="display:inline" onsubmit="return confirm('Regenerate this QR code?')">
                            <?=csrf_field()?>
                            <input type="hidden" name="action" value="regenerate">
                            <input type="hidden" name="id" value="<?=$r['id']?>">
                            <button class="btn small" type="submit">Regenerate</button>
                        </form>
                        <form method="post" style="display:inline" onsubmit="return confirm('Archive this QR code?')">
                            <?=csrf_field()?>
                            <input type="hidden" name="action" value="archive">
                            <input type="hidden" name="id" value="<?=$r['id']?>">
                            <button class="btn small secondary" type="submit">Archive</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$data['rows']): ?><tr><td colspan="9" class="empty">No QR codes found.</td></tr><?php endif; ?>
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