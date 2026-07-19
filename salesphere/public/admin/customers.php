<?php
// customer management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\CustomerController;

AuthMiddleware::requireRole([1, 2, 4, 5, 6, 7]);

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    CustomerController::create();
    exit;
}
if ($action === 'update') {
    CustomerController::update();
    exit;
}
if ($action === 'delete') {
    CustomerController::delete();
    exit;
}

$customers = CustomerController::index();
$branches  = CustomerController::getBranches();

$pageTitle = 'Customer Management';
$basePath  = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/customers/list.php';
require __DIR__ . '/../../views/layouts/footer.php';