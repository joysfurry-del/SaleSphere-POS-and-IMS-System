<?php
@ini_set('display_errors', '0');
// list orders for a customer — by email (guest) or by auth token (logged in)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../includes/autoload.php';
require_once __DIR__ . '/../../../includes/functions.php';

try {
    $db = \Database::getConnection();

    // Resolve customer ID — by auth token first, then by email
    $customerId = null;
    $customer = null;

    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
        $stmt = $db->prepare("SELECT CustomerID, CustomerName, Email, Phone FROM customer WHERE ApiToken = ? AND IsActive = 1 LIMIT 1");
        $stmt->execute([$token]);
        $customer = $stmt->fetch();
        if ($customer) {
            $customerId = (int)$customer['CustomerID'];
        }
    }

    // Fallback to email from POST body
    if (!$customerId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'A valid email is required when not authenticated.']);
            exit;
        }
        $stmt = $db->prepare("SELECT CustomerID, CustomerName, Email, Phone FROM customer WHERE Email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();
        if (!$customer) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $customerId = (int)$customer['CustomerID'];
    }

    // Get all orders for this customer
    $stmt = $db->prepare(
        "SELECT o.*, b.BranchName
         FROM online_order o
         JOIN branch b ON o.BranchID = b.BranchID
         WHERE o.CustomerID = ?
         ORDER BY o.CreatedAt DESC"
    );
    $stmt->execute([$customerId]);
    $orders = $stmt->fetchAll();

    // Get items for each order
    $itemStmt = $db->prepare("SELECT * FROM online_order_item WHERE OnlineOrderID = ? ORDER BY OnlineOrderItemID");

    $result = [];
    foreach ($orders as $order) {
        $itemStmt->execute([$order['OnlineOrderID']]);
        $items = $itemStmt->fetchAll();

        $result[] = [
            'order_id' => $order['OnlineOrderID'],
            'order_number' => $order['OrderNumber'],
            'status' => $order['Status'],
            'payment_status' => $order['PaymentStatus'],
            'subtotal' => $order['Subtotal'],
            'tax_amount' => $order['TaxAmount'],
            'discount_amount' => $order['DiscountAmount'],
            'shipping_amount' => $order['ShippingAmount'],
            'total' => $order['Total'],
            'created_at' => $order['CreatedAt'],
            'branch_name' => $order['BranchName'],
            'shipping_name' => $order['ShippingName'],
            'shipping_address' => $order['ShippingAddress'],
            'shipping_city' => $order['ShippingCity'],
            'shipping_postcode' => $order['ShippingPostalCode'],
            'items' => $items,
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $result,
        'customer' => $customer,
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
