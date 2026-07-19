<?php
// product management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\ProductController;

AuthMiddleware::requireRole([1]);

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    ProductController::create();
    exit;
}
if ($action === 'update') {
    ProductController::update();
    exit;
}
if ($action === 'delete') {
    ProductController::delete();
    exit;
}

$products   = ProductController::index();
$categories = ProductController::getCategories();

$pageTitle = 'Product Management';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/products/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
