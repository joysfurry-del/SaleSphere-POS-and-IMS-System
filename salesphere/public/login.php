<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\AuthController;

AuthMiddleware::guestOnly();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthController::login();
}

require __DIR__ . '/../views/auth/login.php';
