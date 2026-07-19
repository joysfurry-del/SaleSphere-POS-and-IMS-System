<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\InventoryController;
use App\Controllers\StockTransferController;

AuthMiddleware::requireRole([4]);

$branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
$items    = InventoryController::index($branchId);
$products = StockTransferController::getProducts();

$pageTitle = 'Branch Inventory';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/manager/inventory/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
