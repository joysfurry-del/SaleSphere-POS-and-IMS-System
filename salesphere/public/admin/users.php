<?php
// user management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\UserManagementController;

AuthMiddleware::requireRole([1]);

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    UserManagementController::create();
    exit;
}
if ($action === 'update') {
    UserManagementController::update();
    exit;
}
if ($action === 'delete') {
    UserManagementController::delete();
    exit;
}

$users    = UserManagementController::index();
$roles    = UserManagementController::getRoles();
$branches = UserManagementController::getBranches();

$pageTitle = 'User Management';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/users/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
