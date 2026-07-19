<?php
// promotion management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\PromotionController;

AuthMiddleware::requireRole([1, 4, 6]);

$action = $_POST['action'] ?? '';
if ($action === 'create') { PromotionController::create(); exit; }
if ($action === 'update') { PromotionController::update(); exit; }
if ($action === 'delete') { PromotionController::delete(); exit; }

$promotions = PromotionController::index();

$pageTitle = 'Promotion Management';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/promotions/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
