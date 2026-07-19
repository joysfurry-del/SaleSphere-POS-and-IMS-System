<?php
@ini_set('display_errors', '0');
// get active public promotions
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$db = \Database::getConnection();

try {
    $stmt = $db->query(
        "SELECT PromotionID, Code, Name, Description, Type, Value, MinOrderAmount, MaxDiscountAmount,
                StartDate, EndDate, Priority
         FROM promotion
         WHERE IsActive = 1 AND IsPublic = 1
         ORDER BY Priority DESC, Name ASC
         LIMIT 50"
    );
    $promotions = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $promotions]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load promotions.']);
}
