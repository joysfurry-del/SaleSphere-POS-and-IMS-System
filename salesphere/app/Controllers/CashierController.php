<?php
namespace App\Controllers;

use App\Models\OrderManager;
use App\Helpers\AuditHelper;

class CashierController {
    public static function products(): void {
        header('Content-Type: application/json');
        requireAuth();
        $branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
        if ($branchId === 0) {
            echo json_encode([]);
            exit;
        }
        $search = trim($_GET['search'] ?? '');
        $db = \Database::getConnection();
        // get in-stock products for this branch with search filter
        $sql = "SELECT p.ProductID, p.ProductName, p.Price, i.AvailableQty, c.CategoryName
                FROM inventory i
                JOIN product p ON i.ProductID = p.ProductID AND p.IsActive = 1
                JOIN category c ON p.CategoryID = c.CategoryID
                WHERE i.BranchID = ? AND i.AvailableQty > 0";
        $params = [$branchId];
        if ($search !== '') {
            $sql .= " AND (p.ProductName LIKE ? OR c.CategoryName LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY p.ProductName LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    public static function placeOrder(): void {
        header('Content-Type: application/json');
        requireAuth();

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Invalid security token.']);
        }

        $itemsJson = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJson, true);
        if (empty($items)) {
            jsonResponse(['success' => false, 'message' => 'Cart is empty.']);
        }

        $branchId     = (int)($_SESSION['user']['BranchID'] ?? 0);
        $cashierId    = (int)($_SESSION['user']['UserID'] ?? 0);
        $customerName = trim($_POST['customer_name'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
        $amountPaid   = (float)($_POST['amount_paid'] ?? 0);
        $taxRate      = OrderManager::getTaxRate();

        if ($branchId === 0 || $cashierId === 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid session.']);
        }

        // validate payment method
        $validMethods = ['Cash', 'Card', 'QR Pay'];
        if (!in_array($paymentMethod, $validMethods, true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid payment method.']);
        }

        // Look up real prices from database to prevent price manipulation
        $db = \Database::getConnection();
        $productIds = array_column($items, 'ProductID');
        if (empty($productIds)) {
            jsonResponse(['success' => false, 'message' => 'No valid items.']);
        }
        // fetch real prices from db to prevent manipulation
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $db->prepare("SELECT ProductID, Price, ProductName FROM product WHERE ProductID IN ($placeholders) AND IsActive = 1");
        $stmt->execute(array_map('intval', $productIds));
        $realPrices = [];
        while ($row = $stmt->fetch()) {
            $realPrices[(int)$row['ProductID']] = ['Price' => (float)$row['Price'], 'ProductName' => $row['ProductName']];
        }

        // Validate each item
        $validatedItems = [];
        $subtotal = 0;
        foreach ($items as $item) {
            $pid = (int)($item['ProductID'] ?? 0);
            $qty = (int)($item['Quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) continue;
            if (!isset($realPrices[$pid])) {
                jsonResponse(['success' => false, 'message' => "Product #$pid not found or inactive."]);
            }
            $price = $realPrices[$pid]['Price'];
            $lineTotal = round($price * $qty, 2);
            $subtotal += $lineTotal;
            $validatedItems[] = [
                'ProductID'   => $pid,
                'ProductName' => $realPrices[$pid]['ProductName'],
                'Price'       => $price,
                'Quantity'    => $qty,
                'LineTotal'   => $lineTotal,
            ];
        }

        if (empty($validatedItems)) {
            jsonResponse(['success' => false, 'message' => 'No valid items to order.']);
        }

        if ($amountPaid < 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid payment amount.']);
        }

        // calculate totals and change
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total     = round($subtotal + $taxAmount, 2);
        $change    = round($amountPaid - $total, 2);

        // check if cash payment is enough
        if ($change < 0 && $paymentMethod === 'Cash') {
            jsonResponse(['success' => false, 'message' => 'Insufficient payment amount.']);
        }

        try {
            // create order in database
            $orderId = OrderManager::createOrder([
                'BranchID'      => $branchId,
                'CashierID'     => $cashierId,
                'CustomerName'  => $customerName ?: null,
                'Subtotal'      => $subtotal,
                'TaxRate'       => $taxRate,
                'TaxAmount'     => $taxAmount,
                'Total'         => $total,
                'PaymentMethod' => $paymentMethod,
                'AmountPaid'    => $amountPaid,
                'Change'        => $change,
            ], $validatedItems);
        } catch (\Throwable $e) {
            jsonResponse(['success' => false, 'message' => 'Order failed. Please try again.']);
        }

        AuditHelper::log('Created POS order', 'Order', $orderId, "Branch #$branchId, Total: Rs$total, Method: $paymentMethod");

        $order = OrderManager::getById($orderId);
        $orderItems = OrderManager::getItems($orderId);

        jsonResponse([
            'success' => true,
            'message' => "Order #$orderId completed.",
            'order'   => $order,
            'items'   => $orderItems,
        ]);
    }
}
