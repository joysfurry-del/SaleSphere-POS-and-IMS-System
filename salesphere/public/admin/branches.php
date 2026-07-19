<?php
// branch management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\BranchController;

AuthMiddleware::requireRole([1]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') { BranchController::create(); exit; }
    if ($action === 'update') { BranchController::update(); exit; }
    BranchController::delete(); exit;
}

$branches = BranchController::index();
$pageTitle = 'Branch Management';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/branches/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
