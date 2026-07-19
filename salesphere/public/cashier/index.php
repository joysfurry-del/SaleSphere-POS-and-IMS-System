<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\CashierController;
use App\Models\OrderManager;

AuthMiddleware::requireRole([1, 3, 4]);

$action = $_GET['action'] ?? '';

if ($action === 'products') {
    CashierController::products();
    exit;
}

if ($action === 'tax_rate') {
    header('Content-Type: application/json');
    echo json_encode(['rate' => OrderManager::getTaxRate()]);
    exit;
}

if ($action === 'place_order') {
    CashierController::placeOrder();
    exit;
}

$branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
$pageTitle = 'POS Terminal';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/cashier/pos.php';
require __DIR__ . '/../../views/layouts/footer.php';
