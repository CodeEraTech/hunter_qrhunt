<?php
declare(strict_types=1);
require_vendor();
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$brandName = setting_value('brand_name', 'Hunter Wallet');
$vendor = vendor_user();
$currentPage = trim((string)($_GET['page'] ?? basename($_SERVER['SCRIPT_NAME'] ?? '', '.php')));
$navSections = [
    [
        'label' => 'Dashboard',
        'icon' => '●',
        'items' => [
            ['label' => 'Dashboard / Overview', 'href' => '/vendor/dashboard.php'],
        ],
    ],
    [
        'label' => 'My Store',
        'icon' => '🏪',
        'items' => [
            ['label' => 'Store Profile', 'href' => '/vendor/store/profile.php'],
            ['label' => 'Business & KYC', 'href' => '/vendor/store/kyc.php'],
            ['label' => 'Bank & Settlement', 'href' => '/vendor/store/bank.php'],
            ['label' => 'Store Settings', 'href' => '/vendor/store/settings.php'],
        ],
    ],
    [
        'label' => 'Products',
        'icon' => '📦',
        'items' => [
            ['label' => 'Product Catalog', 'href' => '/vendor/products/index.php'],
            ['label' => 'Add Product', 'href' => '/vendor/products/add.php'],
            ['label' => 'Bulk Excel Upload', 'href' => '/vendor/products/upload.php'],
            ['label' => 'Product Approval History', 'href' => '/vendor/products/history.php'],
        ],
    ],
    [
        'label' => 'Orders & Customers',
        'icon' => '🛒',
        'items' => [
            ['label' => 'Orders', 'href' => '/vendor/orders/index.php'],
            ['label' => 'Order Details', 'href' => '/vendor/orders/view.php'],
            ['label' => 'Customer Payments', 'href' => '/vendor/orders/payments.php'],
            ['label' => 'Checkout / Staff Verification', 'href' => '/vendor/orders/checkout.php'],
        ],
    ],
    [
        'label' => 'QR Management',
        'icon' => '📱',
        'items' => [
            ['label' => 'Store QR', 'href' => '/vendor/qr/index.php'],
            ['label' => 'Product QR Codes', 'href' => '/vendor/qr/products.php'],
            ['label' => 'Bulk QR Generation', 'href' => '/vendor/qr/bulk.php'],
        ],
    ],
    [
        'label' => 'Payments & Withdrawals',
        'icon' => '💰',
        'items' => [
            ['label' => 'Wallet / Available Balance', 'href' => '/vendor/payments/wallet.php'],
            ['label' => 'Withdraw Money', 'href' => '/vendor/payments/withdraw.php'],
            ['label' => 'Withdrawal History', 'href' => '/vendor/payments/history.php'],
            ['label' => 'Settlement History', 'href' => '/vendor/payments/settlements.php'],
        ],
    ],
    [
        'label' => 'Rewards & Promotions',
        'icon' => '🎁',
        'items' => [
            ['label' => 'Qoin / Reward Offers', 'href' => '/vendor/rewards/index.php'],
            ['label' => 'Coupons', 'href' => '/vendor/rewards/coupons.php'],
            ['label' => 'Gift & Reward Codes', 'href' => '/vendor/rewards/gifts.php'],
        ],
    ],
    [
        'label' => 'Reports',
        'icon' => '📊',
        'items' => [
            ['label' => 'Sales Report', 'href' => '/vendor/reports/index.php'],
            ['label' => 'Orders Report', 'href' => '/vendor/reports/orders.php'],
            ['label' => 'Product Report', 'href' => '/vendor/reports/products.php'],
            ['label' => 'Settlement Report', 'href' => '/vendor/reports/settlements.php'],
        ],
    ],
    [
        'label' => 'Profile & Security',
        'icon' => '👤',
        'items' => [
            ['label' => 'My Profile', 'href' => '/vendor/profile/index.php'],
            ['label' => 'Login & Security', 'href' => '/vendor/profile/security.php'],
            ['label' => 'Bank Details', 'href' => '/vendor/profile/bank.php'],
            ['label' => 'Documents', 'href' => '/vendor/profile/documents.php'],
            ['label' => 'Devices / Sessions', 'href' => '/vendor/profile/devices.php'],
        ],
    ],
];

function vendor_is_current(string $href): bool
{
    $current = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = parse_url($href, PHP_URL_PATH) ?: $href;
    return $current === $path || str_ends_with($current, $path);
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=e($pageTitle)?> · <?=e($vendor['shop_name'])?> · Store Portal · <?=e($brandName)?></title>
    <link rel="stylesheet" href="<?=e(app_url('/assets/css/style.css'))?>?v=<?=filemtime(APP_ROOT.'/assets/css/style.css')?>">
</head>
<body class="store-portal">
<div class="app-shell">
<aside class="store-sidebar" id="store-sidebar">
    <a class="store-brand" href="<?=e(app_url('/vendor/dashboard.php'))?>">
        <span>🎯</span> <?=e($brandName)?>
    </a>
    <nav aria-label="Store portal navigation">
        <?php foreach ($navSections as $section): ?>
            <?php
            $isOpenDefault = false;
            foreach ($section['items'] as $item) {
                if (vendor_is_current($item['href'])) { $isOpenDefault = true; break; }
            }
            ?>
            <details <?= $isOpenDefault ? 'open' : '' ?>>
                <summary><?=e($section['icon'])?> <span><?=e($section['label'])?></span></summary>
                <?php foreach ($section['items'] as $item): ?>
                    <a class="nav-sub" href="<?=e(app_url($item['href']))?>" <?= vendor_is_current($item['href']) ? 'aria-current="page"' : '' ?>>
                        <?=e($item['label'])?>
                    </a>
                <?php endforeach ?>
            </details>
        <?php endforeach ?>
    </nav>
    <form class="logout-form" method="post" action="<?=e(app_url('/vendor/logout.php'))?>" style="margin:0">
        <?=csrf_field()?>
        <button type="submit" class="logout-link" data-confirm="Sign out of the store portal?">
            <span class="nav-icon">→</span> Logout
        </button>
    </form>
    <div class="store-sidebar-foot">
        <div class="avatar"><?=e(strtoupper($vendor['name'][0] ?? 'V'))?></div>
        <div>
            <strong><?=e($vendor['name'])?></strong>
            <small><?=e($vendor['shop_name'])?> · <?=e($vendor['vendor_code'])?></small>
        </div>
    </div>
</aside>
<main class="store-main">
<header class="store-top">
    <a class="store-brand" href="<?=e(app_url('/vendor/dashboard.php'))?>" style="background:none;padding:0 10px">
        <span>🎯</span>
    </a>
    <div style="flex:1">
        <h1><?=e($pageTitle)?></h1>
        <?php if($pageSubtitle): ?><small><?=e($pageSubtitle)?></small><?php endif ?>
    </div>
    <div class="store-top-tools">
        <span aria-label="Notifications" title="Notifications">🔔</span>
        <span aria-label="Messages" title="Messages">✉️</span>
    </div>
</header>
