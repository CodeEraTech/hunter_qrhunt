<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('products.approve');
require_once APP_ROOT.'/includes/product_management.php';

$pageTitle = 'Product Approval Queue';
$pageSubtitle = 'Review and moderate vendor product submissions';
$adm = admin();

if (is_post()) {
    verify_csrf();
    $action = strtoupper((string) input('action'));
    $ids = array_values(array_filter(array_map('intval', (array) (input('ids') ?? [input('product_id', 0)]))));
    $note = trim((string) input('admin_note'));

    if (!$ids) {
        flash('error', 'Select at least one product.');
    } elseif ($action === 'REJECT' && $note === '') {
        flash('error', 'A rejection reason is required.');
    } elseif (!in_array($action, ['APPROVE', 'REJECT', 'DISABLE'], true)) {
        flash('error', 'Invalid moderation action.');
    } else {
        $count = product_moderate($ids, $action, $note ?: null, $adm['id']);
        flash('success', $count . ' eligible product(s) updated.');
    }
    redirect('/admin/products/approval.php');
}

$q = trim((string) input('q'));
$status = input('status');
$page = max(1, (int) (input('page') ?? 1));
$perPage = 20;

$filters = ['q' => $q ?: null, 'status' => $status ?: null];
$data = products_list($filters, $page, $perPage);

require APP_ROOT.'/includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <div><h2>Product Approval Queue</h2><p class="help">Approve eligible submissions, require correction notes, or disable published products.</p></div>
        <?php if (can('products.export')): ?>
        <a class="btn secondary" href="<?=e(app_url('/admin/products/index.php?export=1'.($q?'&q='.$q:'').($status?'&status='.$status:'')))?>">Export CSV</a>
        <?php endif; ?>
    </div>

    <form class="filters" method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <input type="search" name="q" value="<?=e($q)?>" placeholder="Product ID, SKU, title or vendor" style="min-width:260px">
        <select name="status">
            <option value="">All Statuses</option>
            <?php foreach (['PENDING_REVIEW','RESUBMITTED','APPROVED','REJECTED','DISABLED'] as $st): ?>
            <option value="<?=$st?>" <?=selected($status,$st)?>><?=$st?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Filter</button>
        <?php if ($q || $status): ?><a class="btn secondary" href="<?=e(app_url('/admin/products/approval.php'))?>">Clear</a><?php endif; ?>
    </form>

    <?php foreach (take_flashes() as $f): ?><div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div><?php endforeach; ?>

    <form method="post" id="moderation-form">
        <?=csrf_field()?>
        <div class="actions" style="margin-bottom:12px">
            <button class="btn success" name="action" value="APPROVE">Approve Selected</button>
            <button class="btn danger" name="action" value="REJECT" onclick="return requireModerationNote()">Reject Selected</button>
            <input name="admin_note" id="bulk-note" placeholder="Required rejection/admin note" style="width:200px;padding:8px;border-radius:6px;border:1px solid var(--line)">
            <button class="btn secondary" name="action" value="DISABLE">Disable Selected</button>
        </div>

        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" onclick="document.querySelectorAll('.product-check').forEach(x=>x.checked=this.checked)"></th>
                    <th>SKU</th><th>Product</th><th>Vendor</th><th>Category</th>
                    <th>MRP</th><th>Price</th><th>Stock</th><th>Qoin</th>
                    <th>Status</th><th>Submitted</th><th>Admin Note</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data['rows'] as $r):
                $meta = $r['metadata'] ? json_decode((string) $r['metadata'], true) : [];
                $stock = $meta['stock'] ?? '—';
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
                <td><input type="checkbox" name="ids[]" value="<?=$r['id']?>" class="product-check"></td>
                <td><strong><?=e($r['product_code'])?></strong></td>
                <td><?=e($r['title'])?><br><small style="color:var(--muted)"><?=e($r['shop_name'])?> (<?=e($r['vendor_code'])?>)</small></td>
                <td><?=e($r['category_name'] ?? $meta['category'] ?? '—')?></td>
                <td>₹<?=e(number_format((float)$mrp,2))?></td>
                <td>₹<?=e(number_format((float)$r['price'],2))?></td>
                <td><?=e($stock)?></td>
                <td><?=e($qoin)?></td>
                <td><span class="store-status <?=e($statusClass)?>"><?=e($r['status'])?></span></td>
                <td><?=e($r['submitted_at'])?></td>
                <td><?=e($r['admin_note'] ?? '—')?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$data['rows']): ?><tr><td colspan="12" class="empty">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </form>

    <?php if ($data['lastPage'] > 1): ?>
    <nav class="pagination" style="display:flex;justify-content:center;gap:8px;margin-top:16px">
        <?php for ($p = max(1,$data['page']-2); $p <= min($data['lastPage'],$data['page']+2); $p++): ?>
            <?php $params = array_merge($filters, ['page'=>$p]); ?>
            <a class="btn <?= $p === $data['page'] ? 'primary' : 'secondary' ?>" href="?<?=http_build_query($params)?>"><?=$p?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</section>

<script>
function requireModerationNote() {
    const note = document.getElementById('bulk-note').value.trim();
    const action = document.querySelector('[name="action"]:focus')?.value || '';
    if (action === 'REJECT' && !note) {
        alert('Please enter a rejection reason.');
        return false;
    }
    return true;
}
</script>

<?php require APP_ROOT.'/includes/footer.php'; ?>