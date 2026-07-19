<?php
// online order management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\OnlineOrderController;

AuthMiddleware::requireRole([1, 4, 7]);

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    OnlineOrderController::create();
    exit;
}
if ($action === 'update_status') {
    OnlineOrderController::updateStatus();
    exit;
}
if ($action === 'update_payment') {
    OnlineOrderController::updatePayment();
    exit;
}
if ($action === 'delete') {
    $id = (int)($_POST['order_id'] ?? 0);
    if ($id > 0) {
        \App\Models\OnlineOrderManager::delete($id);
        echo json_encode(['success' => true, 'message' => 'Order deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
    }
    exit;
}

$orders = OnlineOrderController::index();
$branches = OnlineOrderController::getBranches();
$customers = OnlineOrderController::getCustomers();

$pageTitle = 'Online Orders';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/online_orders/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
