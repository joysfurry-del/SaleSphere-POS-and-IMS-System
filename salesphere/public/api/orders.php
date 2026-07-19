<?php
@ini_set('display_errors', '0');
// look up order by email and order number
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
        exit;
    }

    $email = trim($input['email'] ?? '');
    $orderNumber = trim($input['order_number'] ?? '');

    if ($email === '' || $orderNumber === '') {
        echo json_encode(['success' => false, 'message' => 'Email and order number are required.']);
        exit;
    }

    $db = \Database::getConnection();

    // Find customer by email
    $stmt = $db->prepare("SELECT CustomerID, CustomerName, Email, Phone FROM customer WHERE Email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'No orders found for this email.']);
        exit;
    }

    // Find order
    $stmt = $db->prepare(
        "SELECT o.*, b.BranchName
         FROM online_order o
         JOIN branch b ON o.BranchID = b.BranchID
         WHERE o.OrderNumber = ? AND o.CustomerID = ?"
    );
    $stmt->execute([$orderNumber, $customer['CustomerID']]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Get items
    $stmt = $db->prepare("SELECT * FROM online_order_item WHERE OnlineOrderID = ? ORDER BY OnlineOrderItemID");
    $stmt->execute([$order['OnlineOrderID']]);
    $items = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'order' => $order,
            'items' => $items,
            'customer' => $customer,
        ]
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
