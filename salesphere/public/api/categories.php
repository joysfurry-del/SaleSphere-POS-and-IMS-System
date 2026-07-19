<?php
@ini_set('display_errors', '0');
// get all categories
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

try {
    $db = \Database::getConnection();
    $stmt = $db->query("SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName");
    $categories = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $categories]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
