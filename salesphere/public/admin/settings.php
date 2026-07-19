<?php
// system settings page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\SystemSettingsController;

AuthMiddleware::requireRole([1]);

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    SystemSettingsController::create();
    exit;
}
if ($action === 'update') {
    SystemSettingsController::update();
    exit;
}
if ($action === 'delete') {
    SystemSettingsController::delete();
    exit;
}

$settings = SystemSettingsController::index();

$pageTitle = 'System Settings';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/settings/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
