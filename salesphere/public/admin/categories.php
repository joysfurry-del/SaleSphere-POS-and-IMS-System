<?php
// category management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\CategoryController;

AuthMiddleware::requireRole([1]);

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    CategoryController::create();
    exit;
}
if ($action === 'update') {
    CategoryController::update();
    exit;
}
if ($action === 'delete') {
    CategoryController::delete();
    exit;
}

$categories = CategoryController::index();

$pageTitle = 'Category Management';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/categories/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
