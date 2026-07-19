<?php
// login log viewer page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireRole([1]);

$db = \Database::getConnection();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 30;
$offset = ($page - 1) * $limit;

$countStmt = $db->query("SELECT COUNT(*) FROM transaction_log WHERE ActionDescription LIKE '%login%' OR ActionDescription LIKE '%Login%'");
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));

$stmt = $db->prepare(
    "SELECT tl.*, u.Username, u.FullName
     FROM transaction_log tl
     LEFT JOIN user u ON tl.UserID = u.UserID
     WHERE tl.ActionDescription LIKE '%login%' OR tl.ActionDescription LIKE '%Login%'
     ORDER BY tl.CreatedAt DESC
     LIMIT $limit OFFSET $offset"
);
$stmt->execute();
$logs = $stmt->fetchAll();

$pageTitle = 'Login Log';
$basePath = '../';
require __DIR__ . '/../../views/layouts/header.php';
require __DIR__ . '/../../views/admin/login_log/list.php';
require __DIR__ . '/../../views/layouts/footer.php';
