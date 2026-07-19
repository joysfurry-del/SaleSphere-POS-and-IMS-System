<?php
// feedback management page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;
use App\Controllers\FeedbackController;

AuthMiddleware::requireRole([1, 4, 5, 6]);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'create') { FeedbackController::create(); exit; }
if ($action === 'respond') { FeedbackController::respond(); exit; }
if ($action === 'toggle_approved') { FeedbackController::toggleApproved(); exit; }
if ($action === 'toggle_featured') { FeedbackController::toggleFeatured(); exit; }
if ($action === 'delete') { FeedbackController::delete(); exit; }
if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $f = \App\Models\FeedbackManager::getById($id);
    if (!$f) { echo json_encode(['error' => 'Not found']); exit; }
    echo json_encode($f);
    exit;
}

$feedback = FeedbackController::index();

$pageTitle = 'Customer Feedback';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/feedback/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
