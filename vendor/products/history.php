<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_vendor();
require_once APP_ROOT . '/includes/vendor_dashboard.php';
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];

$pageTitle = 'Product Approval History';
$pageSubtitle = 'Track all product submissions and their approval status';
require APP_ROOT . '/includes/vendor_header.php';

$statusFilter = trim((string) ($_GET['status'] ?? ''));

$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['vendor_id = ?'];
$params = [$vendorId];

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

$whereSql = implode(' AND ', $where);

$totalStmt = db()->prepare("SELECT COUNT(*) FROM vendor_products WHERE {$whereSql}");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$lastPage = max(1, (int) ceil($total / $perPage));
$page = min($page, $lastPage);

$stmt = db()->prepare("
    SELECT product_code, title, price, metadata, status, admin_note, submitted_at, moderated_at
    FROM vendor_products
    WHERE {$whereSql}
    ORDER BY submitted_at DESC, id DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$statusOptions = [
    'PENDING_REVIEW' => 'Pending',
    'APPROVED' => 'Approved',
    'REJECTED' => 'Rejected',
    'DISABLED' => 'Disabled',
    'RESUBMITTED' => 'Resubmitted',
];

function get_meta(array $product, string $key): ?string
{
    $meta = $product['metadata'] ?? null;
    if (!$meta) return null;
    $arr = json_decode((string) $meta, true);
    return $arr[$key] ?? null;
}

function has_qr(int $vendorId, string $productCode): bool
{
    $stmt = db()->prepare('SELECT 1 FROM product_qr_codes WHERE vendor_id = ? AND product_code = ? AND status = "ACTIVE" LIMIT 1');
    $stmt->execute([$vendorId, $productCode]);
    return (bool) $stmt->fetchColumn();
}

function history_status_badge(string $status): void
{
    $map = [
        'PENDING_REVIEW' => 'pending',
        'RESUBMITTED' => 'pending',
        'APPROVED' => 'approved',
        'REJECTED' => 'rejected',
        'DISABLED' => 'inactive',
    ];
    $cls = $map[$status] ?? strtolower($status);
    printf('<span class="store-status status-%s">%s</span>', e($cls), e(vendor_dashboard_status($status)));
}
?>

<section class="store-panel" aria-labelledby="history-heading">
    <div class="panel-head">
        <h2 id="history-heading">Product Approval History</h2>
    </div>

    <form method="get" class="bulk-tools" style="margin-bottom:16px; gap:10px; flex-wrap:wrap; align-items:end;">
        <div>
            <label class="field-label-row"><span>Filter by Status</span></label>
            <select name="status" style="padding:10px 14px; border:1px solid var(--line); border-radius:10px;">
                <option value="">All Statuses</option>
                <?php foreach ($statusOptions as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= selected($statusFilter, $val) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn secondary">Apply</button>
        <?php if ($statusFilter !== ''): ?>
            <a class="btn" href="<?= e(app_url('/vendor/products/history.php')) ?>">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table class="store-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Qoin/Reward</th>
                    <th>Approval Status</th>
                    <th>Admin Remarks</th>
                    <th>QR Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                            $qoinEnabled = (bool) (get_meta($product, 'qoin_enabled') ?? false);
                            $qoinValue = get_meta($product, 'qoin_value');
                            $qrExists = has_qr($vendorId, $product['product_code']);
                        ?>
                        <tr>
                            <td><strong><?= e($product['product_code']) ?></strong></td>
                            <td><?= e($product['title']) ?></td>
                            <td><?= e(get_meta($product, 'category') ?? '—') ?></td>
                            <td class="store-amount"><?= money($product['price']) ?></td>
                            <td>
                                <?php if ($qoinEnabled): ?>
                                    <span style="color:var(--green); font-weight:600;">Yes</span>
                                    <?php if ($qoinValue !== null): ?><br><small><?= e($qoinValue) ?> Qoins</small><?php endif; ?>
                                <?php else: ?>
                                    No
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php history_status_badge($product['status']); ?>
                            </td>
                            <td class="store-remarks">
                                <?= $product['admin_note'] ? e($product['admin_note']) : '—' ?>
                            </td>
                            <td>
                                <?php if ($qrExists): ?>
                                    <span style="color:var(--green); font-weight:600;">✓ Generated</span>
                                <?php else: ?>
                                    <span style="color:var(--muted);">Not generated</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($product['submitted_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="empty">No product submissions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
        <nav class="store-pagination" aria-label="Pagination">
            <span>Page <?= $page ?> of <?= $lastPage ?> (<?= $total ?> records)</span>
            <div style="display:flex; gap:6px;">
                <?php if ($page > 1): ?>
                    <a class="btn secondary" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                <?php endif; ?>
                <?php if ($page < $lastPage): ?>
                    <a class="btn secondary" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</section>

<?php require APP_ROOT . '/includes/vendor_footer.php'; ?>