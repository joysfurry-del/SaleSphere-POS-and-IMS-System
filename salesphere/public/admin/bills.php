<?php
// bills and invoices page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\BillController;

AuthMiddleware::requireRole([1, 3, 4]);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'create') { BillController::create(); exit; }
if ($action === 'update_status') { BillController::updateStatus(); exit; }
if ($action === 'delete') { BillController::delete(); exit; }
if ($action === 'download') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id === 0) { echo 'Invalid bill ID'; exit; }
    AuthMiddleware::requireRole([1, 3, 4]);
    require __DIR__ . '/../../views/admin/bills/print.php';
    exit;
}
if ($action === 'download_pdf') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id === 0) { die('Invalid bill ID'); }
    AuthMiddleware::requireRole([1, 3, 4]);
    require_once __DIR__ . '/../../includes/pdf_reports.php';
    $pdf = generateInvoicePDF($id);
    if ($pdf === false) { die('Invoice could not be generated.'); }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice_' . $id . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $pdf;
    exit;
}
if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $b = \App\Models\BillManager::getById($id);
    if (!$b) { http_response_code(404); echo 'Bill not found'; exit; }
    $items = \App\Models\BillManager::getItems($id);
    $payments = \App\Models\BillManager::getPayments($id);
    require __DIR__ . '/../../views/admin/bills/detail.php';
    exit;
}

$bills = BillController::index();
$branches = BillController::getBranches();
$customers = BillController::getCustomers();
$products = BillController::getProducts();
$orders = BillController::getOrders();
$onlineOrders = BillController::getOnlineOrders();

$pageTitle = 'Bills & Invoices';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/bills/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
