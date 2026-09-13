<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');

$vendor = vendor_user();
$vendorId = (int) $vendor['vendor_id'];

$pageTitle = 'Product Catalog';
$pageSubtitle = 'Manage and view all your store products';
require APP_ROOT . '/includes/vendor_header.php';

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));

$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['vendor_id = ?'];
$params = [$vendorId];

if ($search !== '') {
    $where[] = '(product_code LIKE ? OR title LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

if ($categoryFilter !== '') {
    $where[] = 'JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.category")) = ?';
    $params[] = $categoryFilter;
}

$whereSql = implode(' AND ', $where);

$totalStmt = db()->prepare("SELECT COUNT(*) FROM vendor_products WHERE {$whereSql}");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$lastPage = max(1, (int) ceil($total / $perPage));
$page = min($page, $lastPage);

$stmt = db()->prepare("
    SELECT product_code, title, price, metadata, status, admin_note, submitted_at
    FROM vendor_products
    WHERE {$whereSql}
    ORDER BY submitted_at DESC, id DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categoriesStmt = db()->prepare('
    SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.category")) as category
    FROM vendor_products
    WHERE vendor_id = ? AND JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.category")) IS NOT NULL
    ORDER BY category
');
$categoriesStmt->execute([$vendorId]);
$categories = array_column($categoriesStmt->fetchAll(), 'category');

$statusOptions = ['PENDING_REVIEW' => 'Pending Review', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'DISABLED' => 'Disabled', 'RESUBMITTED' => 'Resubmitted'];

function get_meta(array $product, string $key): ?string
{
    $meta = $product['metadata'] ?? null;
    if (!$meta) return null;
    $arr = json_decode((string) $meta, true);
    return $arr[$key] ?? null;
}

function status_badge(string $status): void
{
    $map = [
        'PENDING_REVIEW' => 'pending',
        'RESUBMITTED' => 'pending',
        'APPROVED' => 'approved',
        'REJECTED' => 'rejected',
        'DISABLED' => 'inactive',
    ];
    $cls = $map[$status] ?? strtolower($status);
    printf('<span class="store-status status-%s">%s</span>', e($cls), e($status));
}
?>

<section class="store-panel" aria-labelledby="catalog-heading">
    <div class="panel-head">
        <h2 id="catalog-heading">Product Catalog</h2>
        <a class="btn primary" href="<?= e(app_url('/vendor/products/add.php')) ?>">Add Product</a>
    </div>

    <form method="get" class="bulk-tools" style="margin-bottom:16px; gap:10px; flex-wrap:wrap;">
        <input type="search" name="search" placeholder="Search SKU or name…" value="<?= e($search) ?>" style="min-width:220px; padding:10px 14px; border:1px solid var(--line); border-radius:10px;">
        <select name="status" style="padding:10px 14px; border:1px solid var(--line); border-radius:10px;">
            <option value="">All Statuses</option>
            <?php foreach ($statusOptions as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= selected($statusFilter, $val) ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="category" style="padding:10px 14px; border:1px solid var(--line); border-radius:10px;">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= selected($categoryFilter, $cat) ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn secondary">Filter</button>
        <?php if ($search || $statusFilter || $categoryFilter): ?>
            <a class="btn" href="<?= e(app_url('/vendor/products/index.php')) ?>">Clear</a>
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
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><strong><?= e($product['product_code']) ?></strong></td>
                            <td><?= e($product['title']) ?></td>
                            <td><?= e(get_meta($product, 'category') ?? '—') ?></td>
                            <td class="store-amount"><?= money($product['price']) ?></td>
                            <td><?= e(get_meta($product, 'stock') ?? '—') ?></td>
                            <td>
                                <?php status_badge($product['status']); ?>
                            </td>
                            <td><?= e($product['submitted_at']) ?></td>
                            <td>
                                <a class="btn secondary" href="<?= e(app_url('/vendor/products/add.php?sku=' . rawurlencode($product['product_code']))) ?>" style="font-size:12px; padding:6px 10px;">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
        <nav class="store-pagination" aria-label="Pagination">
            <span>Page <?= $page ?> of <?= $lastPage ?> (<?= $total ?> products)</span>
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