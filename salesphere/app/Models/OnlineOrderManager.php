<?php
namespace App\Models;

use PDO;

class OnlineOrderManager {
    // get online orders with filters for branch, status, customer
    public static function getAll(?int $branchId = null, ?string $status = null, ?int $customerId = null): array {
        $db = \Database::getConnection();
        $sql = "SELECT o.*, c.CustomerName, b.BranchName
                FROM online_order o
                JOIN customer c ON o.CustomerID = c.CustomerID
                JOIN branch b ON o.BranchID = b.BranchID
                WHERE 1=1";
        $params = [];
        if ($branchId) {
            $sql .= " AND o.BranchID = ?";
            $params[] = $branchId;
        }
        if ($status) {
            $sql .= " AND o.Status = ?";
            $params[] = $status;
        }
        if ($customerId) {
            $sql .= " AND o.CustomerID = ?";
            $params[] = $customerId;
        }
        $sql .= " ORDER BY o.CreatedAt DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // get single order with full customer info
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT o.*, c.CustomerName, c.Email, c.Phone, c.Address, c.City, c.State, c.Postcode, c.Country, b.BranchName
             FROM online_order o
             JOIN customer c ON o.CustomerID = c.CustomerID
             JOIN branch b ON o.BranchID = b.BranchID
             WHERE o.OnlineOrderID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // get items for an online order
    public static function getItems(int $onlineOrderId): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM online_order_item WHERE OnlineOrderID = ? ORDER BY OnlineOrderItemID");
        $stmt->execute([$onlineOrderId]);
        return $stmt->fetchAll();
    }

    // create order with items in transaction
    public static function create(array $data, array $items): int {
        $db = \Database::getConnection();
        $db->beginTransaction();
        try {
            // Generate unique order number
            $orderNumber = 'OO-' . date('Ymd') . '-' . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            // Check uniqueness
            $stmt = $db->prepare("SELECT 1 FROM online_order WHERE OrderNumber = ?");
            $stmt->execute([$orderNumber]);
            if ($stmt->fetch()) {
                $orderNumber = 'OO-' . date('Ymd') . '-' . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            }

            $stmt = $db->prepare(
                "INSERT INTO online_order (OrderNumber, CustomerID, BranchID, Status, PaymentStatus, PaymentMethod,
                 Subtotal, TaxRate, TaxAmount, DiscountAmount, ShippingAmount, Total, AmountPaid, PromotionCode, Notes)
                 VALUES (?, ?, ?, 'Pending', 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $orderNumber,
                $data['CustomerID'],
                $data['BranchID'],
                $data['PaymentMethod'],
                $data['Subtotal'],
                $data['TaxRate'],
                $data['TaxAmount'],
                $data['DiscountAmount'] ?? 0,
                $data['ShippingAmount'] ?? 0,
                $data['Total'],
                $data['AmountPaid'] ?? 0,
                $data['PromotionCode'] ?? null,
                $data['Notes'] ?? null,
            ]);
            $orderId = (int)$db->lastInsertId();

            // insert each item into order
            $stmtItem = $db->prepare(
                "INSERT INTO online_order_item (OnlineOrderID, ProductID, ProductName, ProductSKU, Price, Quantity, LineTotal, TaxRate, TaxAmount)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $product = \App\Models\ProductManager::getById($item['ProductID']);
                $stmtItem->execute([
                    $orderId,
                    $item['ProductID'],
                    $item['ProductName'],
                    $product['ProductSKU'] ?? '',
                    $item['Price'],
                    $item['Quantity'],
                    $item['LineTotal'],
                    $data['TaxRate'],
                    $item['TaxAmount'] ?? 0,
                ]);
            }

            $db->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // update order status with timestamp
    public static function updateStatus(int $id, string $status, ?string $adminNotes = null): bool {
        $db = \Database::getConnection();
        $fields = ['Status = ?'];
        $params = [$status];
        $timestampField = '';
        switch ($status) {
            case 'Confirmed': $timestampField = 'ConfirmedAt'; break;
            case 'Shipped': $timestampField = 'ShippedAt'; break;
            case 'Delivered': $timestampField = 'DeliveredAt'; break;
            case 'Cancelled': $timestampField = 'CancelledAt'; break;
        }
        if ($timestampField) {
            $fields[] = "`$timestampField` = NOW()";
        }
        if ($adminNotes !== null) {
            $fields[] = "AdminNotes = ?";
            $params[] = $adminNotes;
        }
        $params[] = $id;
        $stmt = $db->prepare("UPDATE online_order SET " . implode(', ', $fields) . " WHERE OnlineOrderID = ?");
        return $stmt->execute($params);
    }

    // update payment info for order
    public static function updatePaymentStatus(int $id, string $paymentStatus, float $amountPaid): bool {
        $db = \Database::getConnection();
        $order = self::getById($id);
        if (!$order) return false;

        $fields = ['PaymentStatus = ?'];
        $params = [$paymentStatus];

        if ($amountPaid > 0) {
            $fields[] = "AmountPaid = AmountPaid + ?";
            $params[] = $amountPaid;
        }
        if ($paymentStatus === 'Paid') {
            $fields[] = "PaidAt = NOW()";
        }
        $params[] = $id;
        $stmt = $db->prepare("UPDATE online_order SET " . implode(', ', $fields) . " WHERE OnlineOrderID = ?");
        return $stmt->execute($params);
    }

    // get order statistics for dashboard
    public static function getStats(int $branchId): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN Status = 'Processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN Status = 'Shipped' THEN 1 ELSE 0 END) as shipped,
                SUM(CASE WHEN Status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                COALESCE(SUM(CASE WHEN PaymentStatus = 'Paid' THEN Total ELSE 0 END), 0) as revenue
             FROM online_order WHERE BranchID = ?"
        );
        $stmt->execute([$branchId]);
        return $stmt->fetch() ?: [
            'total' => 0, 'pending' => 0, 'confirmed' => 0, 'processing' => 0,
            'shipped' => 0, 'delivered' => 0, 'cancelled' => 0, 'revenue' => 0
        ];
    }

    // delete order with items and unlink returns
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $db->beginTransaction();
        try {
            // delete items and unlink returns before deleting order
            $db->prepare("DELETE FROM online_order_item WHERE OnlineOrderID = ?")->execute([$id]);
            // Delete related returns
            $db->prepare("UPDATE product_return SET ReferenceID = NULL WHERE ReferenceType = 'OnlineOrder' AND ReferenceID = ?")->execute([$id]);
            // Delete order
            $db->prepare("DELETE FROM online_order WHERE OnlineOrderID = ?")->execute([$id]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    // get recent orders for a branch
    public static function getRecentByBranch(int $branchId, int $limit = 20): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT o.*, c.CustomerName
             FROM online_order o
             JOIN customer c ON o.CustomerID = c.CustomerID
             WHERE o.BranchID = ?
             ORDER BY o.CreatedAt DESC
             LIMIT ?"
        );
        $stmt->execute([$branchId, $limit]);
        return $stmt->fetchAll();
    }
}