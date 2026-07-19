<?php
namespace App\Controllers;

use App\Models\OnlineOrderManager;
use App\Models\CustomerManager;
use App\Models\Branch;
use App\Helpers\AuditHelper;

class OnlineOrderController {
    public static function index(): array {
        $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
        $status = trim($_GET['status'] ?? '');
        $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

        // use session branch if not specified
        if ($branchId === 0 && isset($_SESSION['user']['BranchID'])) {
            $branchId = (int)$_SESSION['user']['BranchID'];
        }

        // get orders with filters
        return OnlineOrderManager::getAll($branchId ?: null, $status ?: null, $customerId ?: null);
    }

    public static function getBranches(): array {
        return Branch::getAll();
    }

    public static function getCustomers(): array {
        return CustomerManager::getForSelect();
    }

    public static function getById(int $id): ?array {
        return OnlineOrderManager::getById($id);
    }

    public static function getItems(int $onlineOrderId): array {
        return OnlineOrderManager::getItems($onlineOrderId);
    }

    public static function create(): void {
        header('Content-Type: application/json');
        requireAuth();

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Invalid security token.']);
        }

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $itemsJson = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJson, true);
        $paymentMethod = trim($_POST['payment_method'] ?? 'Online');
        $amountPaid = (float)($_POST['amount_paid'] ?? 0);
        $shippingAmount = max(0, (float)($_POST['shipping_amount'] ?? 0));
        $discountAmount = max(0, (float)($_POST['discount_amount'] ?? 0));
        $promoCode = trim($_POST['promo_code'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($customerId === 0 || $branchId === 0 || empty($items)) {
            jsonResponse(['success' => false, 'message' => 'Customer, branch, and items required.']);
        }

        // verify customer exists
        $customer = CustomerManager::getById($customerId);
        if (!$customer) {
            jsonResponse(['success' => false, 'message' => 'Customer not found.']);
        }

        // Server-side price lookup to prevent manipulation
        $db = \Database::getConnection();
        $productIds = array_column($items, 'ProductID');
        if (empty($productIds)) {
            jsonResponse(['success' => false, 'message' => 'No valid items.']);
        }
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $db->prepare("SELECT ProductID, Price, ProductName, IsEcommerce FROM product WHERE ProductID IN ($placeholders) AND IsActive = 1");
        $stmt->execute(array_map('intval', $productIds));
        $realProducts = [];
        while ($row = $stmt->fetch()) {
            $realProducts[(int)$row['ProductID']] = $row;
        }

        // validate items and calculate totals server-side
        $taxRate = \App\Models\OrderManager::getTaxRate();
        $validatedItems = [];
        $subtotal = 0;
        foreach ($items as $item) {
            $pid = (int)($item['ProductID'] ?? 0);
            $qty = (int)($item['Quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) continue;
            if (!isset($realProducts[$pid])) {
                jsonResponse(['success' => false, 'message' => "Product #$pid not found or inactive."]);
            }
            $price = (float)$realProducts[$pid]['Price'];
            $lineTotal = round($price * $qty, 2);
            $taxAmt = round($lineTotal * $taxRate / 100, 2);
            $subtotal += $lineTotal;
            $validatedItems[] = [
                'ProductID'    => $pid,
                'ProductName'  => $realProducts[$pid]['ProductName'],
                'Price'        => $price,
                'Quantity'     => $qty,
                'LineTotal'    => $lineTotal,
                'TaxRate'      => $taxRate,
                'TaxAmount'    => $taxAmt,
                'DiscountAmount' => 0,
                'Notes'        => null,
            ];
        }

        if (empty($validatedItems)) {
            jsonResponse(['success' => false, 'message' => 'No valid items to order.']);
        }

        // calculate order totals
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total = round($subtotal + $taxAmount + $shippingAmount - $discountAmount, 2);

        try {
            // create order in database
            $orderId = OnlineOrderManager::create([
                'CustomerID'    => $customerId,
                'BranchID'      => $branchId,
                'PaymentMethod' => $paymentMethod,
                'Subtotal'      => $subtotal,
                'TaxRate'       => $taxRate,
                'TaxAmount'     => $taxAmount,
                'DiscountAmount' => $discountAmount,
                'ShippingAmount' => $shippingAmount,
                'Total'         => $total,
                'AmountPaid'    => $amountPaid,
                'PromotionCode' => $promoCode,
                'Notes'         => $notes,
            ], $validatedItems);
        } catch (\Throwable $e) {
            jsonResponse(['success' => false, 'message' => 'Order creation failed. Please try again.']);
        }

        AuditHelper::log('Created online order', 'OnlineOrder', $orderId, "Customer: {$customer['CustomerName']}, Total: Rs$total");

        jsonResponse(['success' => true, 'message' => "Online order created.", 'order_id' => $orderId]);
    }

    public static function updateStatus(): void {
        header('Content-Type: application/json');
        requireAuth();

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Invalid security token.']);
        }

        $id = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $adminNotes = trim($_POST['admin_notes'] ?? '');

        if ($id === 0 || $status === '') {
            jsonResponse(['success' => false, 'message' => 'Order ID and status required.']);
        }

        // Valid status transitions
        $validTransitions = [
            'Pending'    => ['Confirmed', 'Cancelled'],
            'Confirmed'  => ['Processing', 'Cancelled'],
            'Processing' => ['Shipped', 'Cancelled'],
            'Shipped'    => ['Delivered', 'Cancelled'],
            'Delivered'  => ['Returned'],
            'Cancelled'  => [],
            'Returned'   => [],
        ];

        // check if order exists
        $order = OnlineOrderManager::getById($id);
        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order not found.']);
        }

        // validate status transition
        $currentStatus = $order['Status'];
        if (!isset($validTransitions[$currentStatus]) || !in_array($status, $validTransitions[$currentStatus], true)) {
            jsonResponse(['success' => false, 'message' => "Cannot transition from '$currentStatus' to '$status'."]);
        }

        // update status and log
        OnlineOrderManager::updateStatus($id, $status, $adminNotes ?: null);
        AuditHelper::log('Updated online order status', 'OnlineOrder', $id, "From $currentStatus to $status");

        jsonResponse(['success' => true, 'message' => "Order status updated to $status."]);
    }

    public static function updatePayment(): void {
        header('Content-Type: application/json');
        requireAuth();

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Invalid security token.']);
        }

        $id = (int)($_POST['order_id'] ?? 0);
        $paymentStatus = trim($_POST['payment_status'] ?? '');
        $amountPaid = (float)($_POST['amount_paid'] ?? 0);

        if ($id === 0 || $paymentStatus === '') {
            jsonResponse(['success' => false, 'message' => 'Order ID and payment status required.']);
        }

        // validate payment status value
        $validStatuses = ['Pending', 'Paid', 'Partial', 'Refunded', 'Failed'];
        if (!in_array($paymentStatus, $validStatuses, true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid payment status.']);
        }

        OnlineOrderManager::updatePaymentStatus($id, $paymentStatus, $amountPaid);
        AuditHelper::log('Updated payment status', 'OnlineOrder', $id, "Payment: $paymentStatus");

        jsonResponse(['success' => true, 'message' => "Payment status updated to $paymentStatus."]);
    }

    public static function getStats(): void {
        header('Content-Type: application/json');
        requireAuth();
        $branchId = (int)($_GET['branch_id'] ?? 0);
        if ($branchId === 0 && isset($_SESSION['user']['BranchID'])) {
            $branchId = (int)$_SESSION['user']['BranchID'];
        }
        if ($branchId === 0) {
            jsonResponse(['total' => 0, 'pending' => 0, 'confirmed' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0, 'revenue' => 0]);
        }
        // get order stats for branch
        $stats = OnlineOrderManager::getStats($branchId);
        jsonResponse($stats);
    }
}