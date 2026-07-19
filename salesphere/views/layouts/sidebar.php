<?php
$currentFile = basename($_SERVER['SCRIPT_NAME']);

function isActive($file) {
    global $currentFile;
    return $currentFile === basename($file) ? 'active' : '';
}

function navIcon($name) {
    $icons = [
        'dashboard'    => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'pos'          => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'inventory'    => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
        'transfer'     => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        'product'      => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
        'customer'     => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'promo'        => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="10" x2="5" y2="10"/><polyline points="5 6 9 2 13 6"/><line x1="19" y1="15" x2="5" y2="15"/><polyline points="5 18 9 22 13 18"/></svg>',
        'order'        => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'bill'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'report'       => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'users'        => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6"/><path d="M23 11h-6"/></svg>',
        'branch'       => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'settings'     => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'ecommerce'    => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        'tag'          => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
        'revenue'      => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        'shift'        => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'log'          => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
    ];
    return $icons[$name] ?? '';
}

$roleId = (int)($user['RoleID'] ?? 0);

$menus = [
    ['label' => 'Dashboard',      'icon' => 'dashboard',    'file' => 'admin.php',              'roles' => [1,2,3,4,5,6,7]],
    ['label' => 'POS Terminal',   'icon' => 'pos',          'file' => 'cashier/index.php',      'roles' => [1,3,4]],
    ['label' => 'My Shift Report','icon' => 'shift',        'file' => 'cashier/report.php',     'roles' => [1,3,4]],
    ['label' => 'Inventory',      'icon' => 'inventory',    'file' => 'admin/inventory.php',    'roles' => [1]],
    ['label' => 'Inventory',      'icon' => 'inventory',    'file' => 'manager/inventory.php',  'roles' => [4]],
    ['label' => 'Transfers',      'icon' => 'transfer',     'file' => 'admin/transfers.php',    'roles' => [1]],
    ['label' => 'Transfers',      'icon' => 'transfer',     'file' => 'manager/transfers.php',  'roles' => [4]],
    ['label' => 'Customers',      'icon' => 'customer',     'file' => 'admin/customers.php',    'roles' => [1,2,4,5,6,7]],
    ['label' => 'Online Orders',  'icon' => 'order',        'file' => 'admin/online_orders.php','roles' => [1,4,7]],
    ['label' => 'Bills/Invoices', 'icon' => 'bill',         'file' => 'admin/bills.php',        'roles' => [1,3,4]],
    ['label' => 'Promotions',     'icon' => 'promo',        'file' => 'admin/promotions.php',   'roles' => [1,4,6]],
    ['label' => 'Returns',        'icon' => 'transfer',     'file' => 'admin/returns.php',      'roles' => [1,4]],
    ['label' => 'Feedback',       'icon' => 'tag',          'file' => 'admin/feedback.php',     'roles' => [1,4,5,6]],
    ['label' => 'Reports',        'icon' => 'report',       'file' => 'admin/reports.php',      'roles' => [1,2,4,5,6]],
    ['label' => 'Revenue',        'icon' => 'revenue',      'file' => 'admin/revenue.php',      'roles' => [1,2,4]],
    ['label' => '',               'icon' => '',             'file' => '---',                    'roles' => [1]],
    ['label' => 'Users',          'icon' => 'users',        'file' => 'admin/users.php',        'roles' => [1]],
    ['label' => 'Products',       'icon' => 'product',      'file' => 'admin/products.php',     'roles' => [1]],
    ['label' => 'Categories',     'icon' => 'tag',          'file' => 'admin/categories.php',   'roles' => [1]],
    ['label' => 'E-Commerce',     'icon' => 'ecommerce',    'file' => 'admin/ecommerce.php',    'roles' => [1]],
    ['label' => 'Branches',       'icon' => 'branch',       'file' => 'admin/branches.php',     'roles' => [1]],
    ['label' => 'Settings',       'icon' => 'settings',     'file' => 'admin/settings.php',     'roles' => [1]],
    ['label' => 'Login Log',      'icon' => 'log',          'file' => 'admin/login_log.php',   'roles' => [1]],
];
?>
<!-- loop menus and show only for user role; separator if file is --- -->
<nav class="sidebar-nav">
  <?php foreach ($menus as $m): ?>
    <?php if (in_array($roleId, $m['roles'], true)): ?>
      <?php if ($m['file'] === '---'): ?>
        <div style="height:1px;background:var(--border);margin:0.6rem 0.5rem;"></div>
      <?php elseif ($m['file'] === '#'): ?>
        <a href="#" class="nav-item">
          <?= navIcon($m['icon']) ?>
          <span><?= $m['label'] ?></span>
        </a>
      <?php else: ?>
        <?php $href = (strpos($m['file'], '/') !== false) ? ($basePath . $m['file']) : ($basePath . 'dashboards/' . $m['file']); ?>
        <a href="<?= $href ?>" class="nav-item <?= isActive($m['file']) ?>">
          <?= navIcon($m['icon']) ?>
          <span><?= $m['label'] ?></span>
        </a>
      <?php endif; ?>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
