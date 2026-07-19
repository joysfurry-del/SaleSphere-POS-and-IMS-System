<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireAuth();

$pageTitle = 'Profile';
$basePath = '../';

require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/profile/index.php';
require __DIR__ . '/../../views/layouts/footer.php';
