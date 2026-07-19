<?php
// ecommerce catalog page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\ProductController;

AuthMiddleware::requireRole([1]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ProductController::toggleEcommerce();
    exit;
}

$products = ProductController::index();

$pageTitle = 'E-Commerce Catalog';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/ecommerce/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
