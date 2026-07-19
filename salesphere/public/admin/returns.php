<?php
// product returns management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\ProductReturnController;

AuthMiddleware::requireRole([1, 4]);

$action = $_POST['action'] ?? '';
if ($action === 'create') { ProductReturnController::create(); exit; }
if ($action === 'update_status') { ProductReturnController::updateStatus(); exit; }
if ($action === 'delete') { ProductReturnController::delete(); exit; }

$returns = ProductReturnController::index();
$branches = ProductReturnController::getBranches();
$customers = ProductReturnController::getCustomers();

$pageTitle = 'Product Returns';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/returns/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
