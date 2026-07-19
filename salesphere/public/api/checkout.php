<?php
@ini_set('display_errors', '0');
// checkout - convert cart to order
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

    $cartToken = $input['cart_token'] ?? '';
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $city = trim($input['city'] ?? '');
    $postcode = trim($input['postcode'] ?? '');
    $shippingAddress = trim($input['shipping_address'] ?? $address);
    $notes = trim($input['notes'] ?? '');

    // Validate required fields
    if ($cartToken === '' || $name === '' || $email === '' || $address === '' || $city === '' || $postcode === '') {
        echo json_encode(['success' => false, 'message' => 'Name, email, address, city, and postcode are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    $db = \Database::getConnection();

    // Find cart
    $stmt = $db->prepare("SELECT * FROM cart WHERE SessionID = ? AND Status = 'Active' ORDER BY CreatedAt DESC LIMIT 1");
    $stmt->execute([$cartToken]);
    $cart = $stmt->fetch();

    if (!$cart) {
        echo json_encode(['success' => false, 'message' => 'Cart not found or already converted.']);
        exit;
    }

    $cartId = (int)$cart['CartID'];

    // Get cart items
    $stmt = $db->prepare("SELECT ci.*, p.ProductSKU FROM cart_item ci LEFT JOIN product p ON ci.ProductID = p.ProductID WHERE ci.CartID = ?");
    $stmt->execute([$cartId]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
        exit;
    }

    // Find or create customer
    $stmt = $db->prepare("SELECT CustomerID FROM customer WHERE Email = ?");
    $stmt->execute([$email]);
    $existingCustomer = $stmt->fetch();

    if ($existingCustomer) {
        $customerId = (int)$existingCustomer['CustomerID'];
        // Update customer info
        $stmt = $db->prepare("UPDATE customer SET CustomerName = ?, Phone = ?, Address = ?, City = ?, Postcode = ? WHERE CustomerID = ?");
        $stmt->execute([$name, $phone, $address, $city, $postcode, $customerId]);
    } else {
        $stmt = $db->prepare("INSERT INTO customer (BranchID, CustomerName, Email, Phone, Address, City, Postcode, Country, CustomerType) VALUES (?, ?, ?, ?, ?, ?, ?, 'Malaysia', 'Online')");
        $stmt->execute([1, $name, $email, $phone, $address, $city, $postcode]);
        $customerId = (int)$db->lastInsertId();
    }

    $branchId = (int)($cart['BranchID'] ?? 1);
    $subtotal = (float)($cart['Subtotal'] ?? 0);
    $taxRate = \App\Models\OrderManager::getTaxRate();
    $taxAmount = (float)($cart['TaxAmount'] ?? 0);
    $discountAmount = (float)($cart['DiscountAmount'] ?? 0);
    $total = (float)($cart['Total'] ?? 0);
    $promotionCode = $cart['PromotionCode'] ?? null;

    // Generate order number
    $orderNumber = 'OO-' . date('Ymd') . '-' . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    $db->beginTransaction();
    try {
        // Create online_order
        $stmt = $db->prepare(
            "INSERT INTO online_order (OrderNumber, CustomerID, BranchID, Status, PaymentStatus, PaymentMethod,
             Subtotal, TaxRate, TaxAmount, DiscountAmount, ShippingAmount, Total, AmountPaid, PromotionCode,
             ShippingName, ShippingPhone, ShippingAddress, ShippingCity, ShippingPostalCode,
             BillingName, BillingPhone, BillingAddress, BillingCity, BillingPostalCode, Notes)
             VALUES (?, ?, ?, 'Pending', 'Pending', 'Online',
             ?, ?, ?, ?, 0, ?, 0, ?,
             ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $orderNumber, $customerId, $branchId,
            $subtotal, $taxRate, $taxAmount, $discountAmount, $total, $promotionCode,
            $name, $phone, $shippingAddress, $city, $postcode,
            $name, $phone, $address, $city, $postcode,
            $notes,
        ]);
        $orderId = (int)$db->lastInsertId();

        // Insert order items
        $stmtItem = $db->prepare(
            "INSERT INTO online_order_item (OnlineOrderID, ProductID, ProductName, ProductSKU, Price, Quantity, LineTotal, TaxRate, TaxAmount)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $stmtItem->execute([
                $orderId,
                $item['ProductID'],
                $item['ProductName'],
                $item['ProductSKU'] ?? '',
                $item['Price'],
                $item['Quantity'],
                $item['LineTotal'],
                $taxRate,
                0,
            ]);

            // Deduct inventory
            $stmtInv = $db->prepare("UPDATE inventory SET AvailableQty = AvailableQty - ? WHERE BranchID = ? AND ProductID = ? AND AvailableQty >= ?");
            $stmtInv->execute([$item['Quantity'], $branchId, $item['ProductID'], $item['Quantity']]);
        }

        // Mark cart as converted
        $stmt = $db->prepare("UPDATE cart SET Status = 'Converted' WHERE CartID = ?");
        $stmt->execute([$cartId]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!',
            'data' => [
                'order_number' => $orderNumber,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'total' => $total,
            ]
        ]);

    } catch (\Throwable $e) {
        $db->rollBack();
        throw $e;
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
