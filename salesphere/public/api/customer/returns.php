<?php
@ini_set('display_errors', '0');
// get return requests for a customer — by email (guest) or by auth token (logged in)
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
        $stmt = $db->prepare("SELECT CustomerID, CustomerName FROM customer WHERE ApiToken = ? AND IsActive = 1 LIMIT 1");
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
        $stmt = $db->prepare("SELECT CustomerID, CustomerName FROM customer WHERE Email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();
        if (!$customer) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $customerId = (int)$customer['CustomerID'];
    }

    // get all return requests for this customer
    $stmt = $db->prepare(
        "SELECT pr.*, b.BranchName
         FROM product_return pr
         LEFT JOIN branch b ON pr.BranchID = b.BranchID
         WHERE pr.CustomerID = ?
         ORDER BY pr.RequestedAt DESC"
    );
    $stmt->execute([$customerId]);
    $returns = $stmt->fetchAll();

    // get return items for each return
    $itemStmt = $db->prepare("SELECT * FROM product_return_item WHERE ReturnID = ?");

    $result = [];
    foreach ($returns as $r) {
        $itemStmt->execute([$r['ReturnID']]);
        $items = $itemStmt->fetchAll();

        $result[] = [
            'return_id' => $r['ReturnID'],
            'return_number' => $r['ReturnNumber'],
            'status' => $r['Status'],
            'reason' => $r['Reason'],
            'reason_details' => $r['ReasonDetails'],
            'total_refund' => $r['TotalRefund'],
            'requested_at' => $r['RequestedAt'],
            'approved_at' => $r['ApprovedAt'],
            'received_at' => $r['ReceivedAt'],
            'refunded_at' => $r['RefundedAt'],
            'branch_name' => $r['BranchName'],
            'items' => $items,
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $result,
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
