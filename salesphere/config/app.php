<?php
define('SITE_NAME', 'Salesphere');
define('BASE_URL', '/salesphere');

// role ID to name mapping
define('ROLE_MAP', [
    1 => 'admin',
    2 => 'accountant',
    3 => 'cashier',
    4 => 'branch_manager',
    5 => 'sales_exec',
    6 => 'digital_marketing',
    7 => 'call_center',
]);

define('ROLE_NAMES', [
    1 => 'Admin',
    2 => 'Accountant',
    3 => 'Cashier',
    4 => 'Branch Manager',
    5 => 'Sales Exec',
    6 => 'Digital Marketing',
    7 => 'Call Center',
]);

define('UPLOAD_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('MAX_AVATAR_SIZE', 2 * 1024 * 1024);

// start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
