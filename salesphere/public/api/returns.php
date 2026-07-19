<?php
@ini_set('display_errors', '0');
// return request API endpoint
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
    // parse and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
        exit;
    }

    $email = trim($input['email'] ?? '');
    $orderNumber = trim($input['order_number'] ?? '');
    $reason = trim($input['reason'] ?? '');
    $reasonDetails = trim($input['reason_details'] ?? '');
    $items = $input['items'] ?? []; // Array of {product_id, qty}

    if ($email === '' || $orderNumber === '' || $reason === '' || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Email, order number, reason, and items are required.']);
        exit;
    }

    $validReasons = ['Defective', 'Wrong Item', 'Not as Described', 'Changed Mind', 'Damaged', 'Other'];
    if (!in_array($reason, $validReasons)) {
        echo json_encode(['success' => false, 'message' => 'Invalid reason. Valid: ' . implode(', ', $validReasons)]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    $db = \Database::getConnection();

    // Find customer
    $stmt = $db->prepare("SELECT CustomerID, CustomerName FROM customer WHERE Email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found.']);
        exit;
    }

    $customerId = (int)$customer['CustomerID'];

    // Find order
    $stmt = $db->prepare(
        "SELECT o.* FROM online_order o WHERE o.OrderNumber = ? AND o.CustomerID = ?"
    );
    $stmt->execute([$orderNumber, $customerId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    $orderId = (int)$order['OnlineOrderID'];
    $branchId = (int)$order['BranchID'];

    // Check if order is eligible for return
    $returnableStatuses = ['Shipped', 'Delivered'];
    if (!in_array($order['Status'], $returnableStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Order status "' . $order['Status'] . '" is not eligible for return.']);
        exit;
    }

    // Get order items for validation
    $stmt = $db->prepare("SELECT * FROM online_order_item WHERE OnlineOrderID = ?");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll();

    // Calculate return totals
    $subtotal = 0;
    $returnItems = [];
    $productIdsInOrder = array_column($orderItems, 'ProductID');

    foreach ($items as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $qty = (int)($item['qty'] ?? 0);

        if ($productId === 0 || $qty <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item in return request.']);
            exit;
        }

        // Find matching order item
        $matchedItem = null;
        foreach ($orderItems as $oi) {
            if ((int)$oi['ProductID'] === $productId) {
                $matchedItem = $oi;
                break;
            }
        }

        if (!$matchedItem) {
            echo json_encode(['success' => false, 'message' => "Product ID $productId is not in this order."]);
            exit;
        }

        if ($qty > (int)$matchedItem['Quantity']) {
            echo json_encode(['success' => false, 'message' => "Return quantity for '{$matchedItem['ProductName']}' exceeds ordered quantity."]);
            exit;
        }

        $lineTotal = round((float)$matchedItem['Price'] * $qty, 2);
        $subtotal += $lineTotal;

        $returnItems[] = [
            'OnlineOrderItemID' => $matchedItem['OnlineOrderItemID'],
            'ProductID' => $productId,
            'ProductName' => $matchedItem['ProductName'],
            'Quantity' => $qty,
            'UnitPrice' => $matchedItem['Price'],
            'LineTotal' => $lineTotal,
        ];
    }

    // Generate return number
    $returnNumber = 'RMA-' . date('Ymd') . '-' . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    // save return with transaction
    $db->beginTransaction();
    try {
        // Create product_return
        $stmt = $db->prepare(
            "INSERT INTO product_return (ReturnNumber, ReferenceType, ReferenceID, CustomerID, BranchID, Status, Reason, ReasonDetails, RefundMethod, Subtotal, TotalRefund)
             VALUES (?, 'OnlineOrder', ?, ?, ?, 'Requested', ?, ?, 'Original Payment', ?, ?)"
        );
        $stmt->execute([
            $returnNumber,
            $orderId,
            $customerId,
            $branchId,
            $reason,
            $reasonDetails,
            $subtotal,
            $subtotal,
        ]);
        $returnId = (int)$db->lastInsertId();

        // Create product_return_item records
        $stmtItem = $db->prepare(
            "INSERT INTO product_return_item (ReturnID, OnlineOrderItemID, ProductID, ProductName, Quantity, UnitPrice, LineTotal, `Condition`)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'New')"
        );
        foreach ($returnItems as $ri) {
            $stmtItem->execute([
                $returnId,
                $ri['OnlineOrderItemID'],
                $ri['ProductID'],
                $ri['ProductName'],
                $ri['Quantity'],
                $ri['UnitPrice'],
                $ri['LineTotal'],
            ]);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Return request submitted successfully.',
            'data' => [
                'return_number' => $returnNumber,
                'return_id' => $returnId,
                'total_refund' => $subtotal,
                'status' => 'Requested',
            ]
        ]);

    } catch (\Throwable $e) {
        $db->rollBack();
        throw $e;
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.', 'debug' => $e->getMessage()]);
}
