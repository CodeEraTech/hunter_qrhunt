<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
require_vendor();
require_get();
header('Cache-Control: private, no-store');
require_once APP_ROOT.'/includes/vendor_dashboard.php';

$vendor = vendor_user();
$pageTitle = 'Dashboard';
$pageSubtitle = 'Store overview and financial metrics';
require APP_ROOT.'/includes/vendor_header.php';

$pages = [];
foreach (['withdrawals','products','payments'] as $section) {
    $pages[$section] = max(1, filter_var($_GET[$section.'_page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
}
$data = vendor_dashboard_data(db(), (int) $vendor['vendor_id'], $pages);
$store = $data['store'];
$w = $data['withdrawals']['summary'] ?? null;
$p = $data['products']['summary'] ?? null;
$c = $data['payments']['summary'] ?? null;

$cards = [
    ['Available Balance', $store['available_balance'] > 0 ? money($store['available_balance']) : '0.00', 'Withdrawable store balance', 'green'],
    ['Pending Withdrawals', $w ? money($w['pending']) : '—', $w ? $w['pending_count'].' requests awaiting review' : 'No data', 'yellow'],
    ['Approved Withdrawals', $w ? money($w['approved']) : '—', 'Approved, processing and paid', 'blue'],
    ['Total Customer Payments', $c ? money($c['amount']) : '—', $c ? $c['total'].' wallet checkouts' : 'No data', 'teal'],
    ['Settled Payments', $c ? money($c['approved']) : '—', 'Successful payments', 'purple'],
    ['Pending Customer Payments', $c ? money($c['pending']) : '—', 'Awaiting completion', 'red'],
    ['Approved Products', $p ? $p['approved'].' SKUs' : '—', 'Approved in catalog', 'cyan'],
    ['Pending Product Approvals', $p ? $p['pending'].' SKUs' : '—', 'Pending review', 'orange'],
];
echo '<section class="store-hero">';
echo '<p class="store-eyebrow">'.$store['vendor_code'].' · '.e($store['business_category'] ?? 'Store').' · Commission: '.e(number_format($store['commission_rate'], 2)).'%</p>';
echo '<h1>Store Portal <span>•</span> '.$store['shop_name'].'</h1>';
echo '<p>Real-time financial metrics, withdrawal tracking, product approvals and customer settlement ledger for your store.</p>';
echo '</section>';
echo '<section class="store-metrics" aria-label="Store financial and product summary">';
foreach ($cards as [$label, $value, $description, $color]) {
    printf(
        '<article class="card store-metric metric-%s"><h2>%s</h2><strong>%s</strong><p>%s</p></article>',
        e($color), e($label), e($value), e($description)
    );
}
echo '</section>';

echo '<nav class="store-jump" aria-label="Dashboard sections">';
echo '<a href="#recent-withdrawals">Recent Withdrawals</a>';
echo '<a href="#recent-orders">Recent Orders</a>';
echo '<a href="#recent-products">Recent Products</a>';
echo '<a href="#payments">Payments &amp; Settlements</a>';
echo '</nav>';

$hasData = function (?array $d, string $key = 'rows'): bool {
    return $d !== null && !empty($d[$key]);
};
$statusBadge = function (string $status): void {
    $map = [
        'PENDING'=>'pending', 'UNDER_REVIEW'=>'pending', 'APPROVED'=>'approved',
        'PROCESSING'=>'processing', 'PAID'=>'paid', 'SUCCESS'=>'success',
        'PENDING_REVIEW'=>'pending', 'RESUBMITTED'=>'pending',
        'APPROVED as admin'=>'approved', 'REJECTED'=>'rejected', 'REVERSED'=>'rejected',
        'INACTIVE'=>'inactive', 'DISABLED'=>'inactive',
    ];
    $cls = $map[$status] ?? strtolower($status);
    printf('<span class="store-status status-%s">%s</span>', e($cls), e(vendor_dashboard_status($status)));
};

if ($data['withdrawals'] !== null):
    $rows = array_slice($data['withdrawals']['rows'], 0, 5);
    echo '<section class="panel store-panel" id="recent-withdrawals" aria-labelledby="wd-heading">';
    echo '<div class="panel-head"><h2 id="wd-heading">Recent Withdrawal Activity</h2><a class="btn secondary" href="'.e(app_url('/vendor/payments/history.php')).'">View all</a></div>';
    echo '<div class="table-wrap"><table class="store-table"><thead><tr><th>Request ID</th><th>Date</th><th>Amount</th><th>Status</th><th>Processed</th></tr></thead><tbody>';
    foreach ($rows as $row):
        echo '<tr><td><strong>#WD-'.e($row['id']).'</strong></td><td>'.e($row['requested_at']).'</td><td class="store-amount">'.money($row['amount']).'</td><td>';
        $statusBadge($row['status']);
        echo '</td><td>'.($row['paid_at'] ? e($row['paid_at']) : '—').'</td></tr>';
    endforeach;
    if (!$rows) echo '<tr><td colspan="5" class="empty">No withdrawal requests yet.</td></tr>';
    echo '</tbody></table></div>';
    echo '</section>';
endif;

if ($data['recent_orders']):
    echo '<section class="panel store-panel" id="recent-orders" aria-labelledby="ro-heading">';
    echo '<div class="panel-head"><h2 id="ro-heading">Recent Customer Orders</h2><a class="btn secondary" href="'.e(app_url('/vendor/orders/payments.php')).'">View all payments</a></div>';
    echo '<div class="table-wrap"><table class="store-table"><thead><tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
    foreach ($data['recent_orders'] as $row):
        echo '<tr><td><strong>'.e($row['checkout_id']).'</strong></td><td>'.e($row['customer_name'] ?? '—').'</td><td>'.e($row['created_at']).'</td><td class="store-amount">'.money($row['bill_amount']).'</td><td>';
        $statusBadge($row['status']);
        echo '</td></tr>';
    endforeach;
    if (!$data['recent_orders']) echo '<tr><td colspan="5" class="empty">No recent orders.</td></tr>';
    echo '</tbody></table></div>';
    echo '</section>';
endif;

if ($data['recent_products']):
    echo '<section class="panel store-panel" id="recent-products" aria-labelledby="rp-heading">';
    echo '<div class="panel-head"><h2 id="rp-heading">Recent Product Submissions</h2><a class="btn secondary" href="'.e(app_url('/vendor/products/history.php')).'">View all</a></div>';
    echo '<div class="table-wrap"><table class="store-table"><thead><tr><th>SKU</th><th>Product</th><th>Date</th><th>Price</th><th>Status</th></tr></thead><tbody>';
    foreach ($data['recent_products'] as $row):
        echo '<tr><td><strong>'.e($row['product_code']).'</strong></td><td>'.e($row['title']).'</td><td>'.e($row['submitted_at']).'</td><td class="store-amount">'.money($row['price']).'</td><td>';
        $statusBadge($row['status']);
        echo '</td></tr>';
    endforeach;
    if (!$data['recent_products']) echo '<tr><td colspan="5" class="empty">No recent submissions.</td></tr>';
    echo '</tbody></table></div>';
    echo '</section>';
endif;

require APP_ROOT.'/includes/vendor_footer.php';
