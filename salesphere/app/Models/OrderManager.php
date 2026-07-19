<?php
namespace App\Models;

class OrderManager {
    public static function createOrder(array $data, array $items): int {
        $db = \Database::getConnection();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }
        try {
            $stmt = $db->prepare(
                "INSERT INTO `order` (BranchID, CashierID, CustomerName, Subtotal, TaxRate, TaxAmount, Total, PaymentMethod, AmountPaid, `Change`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                (int)$data['BranchID'],
                (int)$data['CashierID'],
                $data['CustomerName'] ?? null,
                (float)$data['Subtotal'],
                (float)$data['TaxRate'],
                (float)$data['TaxAmount'],
                (float)$data['Total'],
                $data['PaymentMethod'],
                (float)$data['AmountPaid'],
                (float)$data['Change'],
            ]);
            $orderId = (int)$db->lastInsertId();

            $stmtItem = $db->prepare(
                "INSERT INTO order_item (OrderID, ProductID, ProductName, Price, Quantity, LineTotal)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmtInv = $db->prepare(
                "UPDATE inventory SET AvailableQty = AvailableQty - ? WHERE BranchID = ? AND ProductID = ? AND AvailableQty >= ?"
            );
            foreach ($items as $item) {
                $qty = (int)($item['Quantity'] ?? 0);
                if ($qty <= 0) continue;

                $stmtItem->execute([
                    $orderId,
                    (int)$item['ProductID'],
                    $item['ProductName'],
                    (float)$item['Price'],
                    $qty,
                    (float)($item['LineTotal'] ?? 0),
                ]);
                $stmtInv->execute([
                    $qty,
                    (int)$data['BranchID'],
                    (int)$item['ProductID'],
                    $qty,
                ]);
                if ($stmtInv->rowCount() === 0) {
                    throw new \RuntimeException("Insufficient stock for product: {$item['ProductName']}");
                }
            }

            if (!$inTransaction) {
                $db->commit();
            }
            return $orderId;
        } catch (\Throwable $e) {
            if (!$inTransaction) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT o.*, u.Username AS CashierName, b.BranchName
             FROM `order` o
             JOIN user u ON o.CashierID = u.UserID
             JOIN branch b ON o.BranchID = b.BranchID
             WHERE o.OrderID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getItems(int $orderId): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM order_item WHERE OrderID = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public static function getRecentByBranch(int $branchId, int $limit = 20): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT o.*, u.Username AS CashierName
             FROM `order` o
             JOIN user u ON o.CashierID = u.UserID
             WHERE o.BranchID = ?
             ORDER BY o.CreatedAt DESC
             LIMIT ?"
        );
        $stmt->execute([$branchId, $limit]);
        return $stmt->fetchAll();
    }

    public static function getTaxRate(): float {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT SettingValue FROM system_settings WHERE SettingName = 'tax_rate'");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (float)$row['SettingValue'] : 0.0;
    }
}
