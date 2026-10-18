<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('products.audit.view');
require_once APP_ROOT.'/includes/product_management.php';

$pageTitle = 'Product History / Audit';
$pageSubtitle = 'Immutable product activity log';

$id = (int) (input('id') ?? 0);
$product = null;
if ($id > 0) {
    $product = product_get($id);
    if (!$product) {
        flash('error', 'Product not found.');
        redirect('/admin/products/index.php');
    }
}

$page = max(1, (int) (input('page') ?? 1));
$perPage = 50;

$history = product_audit_history($id, $page, $perPage);

require APP_ROOT.'/includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2><?= $product ? 'Product Audit History' : 'Global Product Audit History' ?></h2>
            <p class="help" style="margin:4px 0 0">
                <?php if ($product): ?>
                    <strong><?=e($product['product_code'])?></strong> — <?=e($product['title'])?>
                    <span style="margin-left:12px">Vendor: <?=e($product['shop_name'])?> (<?=e($product['vendor_code'])?>)</span>
                <?php else: ?>
                    Immutable product activity, review, and moderation audit logs
                <?php endif; ?>
            </p>
        </div>
        <a class="btn secondary" href="<?=e(app_url('/admin/products/index.php'))?>">Back to Directory</a>
    </div>

    <div class="table-wrap"><table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <?php if (!$product): ?><th>Product</th><?php endif; ?>
                <th>Action</th><th>Field</th>
                <th>Old Value</th><th>New Value</th>
                <th>Reason</th>
                <th>Admin / User</th><th>IP</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($history['rows'] as $r): ?>
            <tr>
                <td style="white-space:nowrap"><?=e($r['created_at'])?></td>
                <?php if (!$product): ?>
                    <td>
                        <strong><?=e($r['product_code'])?></strong>
                        <?php if (!empty($r['product_title'])): ?><br><small><?=e($r['product_title'])?></small><?php endif; ?>
                    </td>
                <?php endif; ?>
                <td><span class="store-status status-<?=strtolower(str_replace('_','-',$r['action']))?>"><?=e($r['action'])?></span></td>
                <td><?=e($r['field_name'] ?? '—')?></td>
                <td style="max-width:200px;overflow-wrap:anywhere;white-space:pre-wrap;font-family:monospace;font-size:12px"><?=e($r['old_value'] ?? '—')?></td>
                <td style="max-width:200px;overflow-wrap:anywhere;white-space:pre-wrap;font-family:monospace;font-size:12px"><?=e($r['new_value'] ?? '—')?></td>
                <td><?=e($r['reason'] ?? '—')?></td>
                <td>
                    <?php if ($r['admin_id']): ?>
                        <strong>Admin #<?=e($r['admin_id'])?></strong>
                        <?php if ($r['admin_name']): ?><br><small><?=e($r['admin_name'])?></small><?php endif; ?>
                    <?php elseif ($r['user_id']): ?>
                        <strong>User #<?=e($r['user_id'])?></strong>
                        <?php if ($r['user_name']): ?><br><small><?=e($r['user_name'])?></small><?php endif; ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?=e($r['ip_address'] ?? '—')?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$history['rows']): ?><tr><td colspan="<?= $product ? '8' : '9' ?>" class="empty">No audit history found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>

    <?php if ($history['lastPage'] > 1): ?>
    <nav class="pagination" style="display:flex;justify-content:center;gap:8px;margin-top:16px">
        <?php for ($p = max(1,$history['page']-2); $p <= min($history['lastPage'],$history['page']+2); $p++): ?>
            <a class="btn <?= $p === $history['page'] ? 'primary' : 'secondary' ?>" href="?id=<?=$id?>&page=<?=$p?>"><?=$p?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</section>

<?php require APP_ROOT.'/includes/footer.php'; ?>