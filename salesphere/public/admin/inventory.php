<?php
// inventory management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\InventoryController;

AuthMiddleware::requireRole([1]);

$items    = InventoryController::index((int)($_GET['branch_id'] ?? 0));
$branches = InventoryController::getBranches();
$products = InventoryController::getProducts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') { InventoryController::create(); exit; }
    if ($action === 'update') { InventoryController::update(); exit; }
    InventoryController::delete(); exit;
}

$pageTitle = 'Inventory Management';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/inventory/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
