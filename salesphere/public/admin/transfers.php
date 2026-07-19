<?php
// stock transfer page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\StockTransferController;

AuthMiddleware::requireRole([1]);

$pending = StockTransferController::pending();
$all     = StockTransferController::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') { StockTransferController::approve(); exit; }
    if ($action === 'reject')  { StockTransferController::reject();  exit; }
}

$pageTitle = 'Stock Transfers';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/transfers/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
