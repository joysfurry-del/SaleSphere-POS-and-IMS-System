<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/functions.php';

use App\Controllers\AuthController;

AuthController::logout();
