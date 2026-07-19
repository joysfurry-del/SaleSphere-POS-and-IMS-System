<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\StockTransferController;

AuthMiddleware::requireRole([4]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    StockTransferController::request();
    exit;
}

$branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
$pending  = StockTransferController::byBranch($branchId);
$products = StockTransferController::getProducts();

$pageTitle = 'Transfer Requests';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/manager/transfers/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
