<?php
// AJAX - return detail
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../includes/autoload.php';
require_once __DIR__ . '/../../../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired.']);
    exit;
}

use App\Controllers\ProductReturnController;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid return ID.']);
    exit;
}

$return = ProductReturnController::getById($id);
$items = $return ? ProductReturnController::getItems($id) : [];

echo json_encode(['success' => !!$return, 'return' => $return, 'items' => $items]);
