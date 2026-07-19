<?php
namespace App\Models;

use PDO;

class ProductReturnManager {
    // get returns with filters for branch and search
    public static function getAll(?int $branchId = null, string $search = ''): array {
        $db = \Database::getConnection();
        $sql = "SELECT r.*, u.Username AS ProcessedByName, br.BranchName, c.CustomerName
                FROM product_return r
                LEFT JOIN user u ON r.ProcessedBy = u.UserID
                LEFT JOIN branch br ON r.BranchID = br.BranchID
                LEFT JOIN customer c ON r.CustomerID = c.CustomerID";
        $where = [];
        $params = [];
        if ($branchId) {
            $where[] = "r.BranchID = ?";
            $params[] = $branchId;
        }
        if ($search) {
            $where[] = "(r.ReturnNumber LIKE ? OR c.CustomerName LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%"]);
        }
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY r.RequestedAt DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // get single return
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT r.*, u.Username AS ProcessedByName, br.BranchName, c.CustomerName
             FROM product_return r
             LEFT JOIN user u ON r.ProcessedBy = u.UserID
             LEFT JOIN branch br ON r.BranchID = br.BranchID
             LEFT JOIN customer c ON r.CustomerID = c.CustomerID
             WHERE r.ReturnID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // get items for a return
    public static function getItems(int $returnId): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM product_return_item WHERE ReturnID = ?");
        $stmt->execute([$returnId]);
        return $stmt->fetchAll();
    }

    // create unique return number
    public static function generateReturnNumber(): string {
        $db = \Database::getConnection();
        $prefix = 'RMA-' . date('Ymd') . '-';
        $stmt = $db->prepare("SELECT COUNT(*) FROM product_return WHERE ReturnNumber LIKE ?");
        $stmt->execute([$prefix . '%']);
        $count = (int)$stmt->fetchColumn();
        return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    // create new return request
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO product_return (ReturnNumber, ReferenceType, ReferenceID, CustomerID, BranchID, ProcessedBy, Status, Reason, ReasonDetails, RefundMethod, Subtotal, TaxAmount, RestockFee, ShippingFee, TotalRefund, Notes, AdminNotes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['ReturnNumber'] ?? self::generateReturnNumber(),
            $data['ReferenceType'],
            $data['ReferenceID'],
            $data['CustomerID'] ?? null,
            $data['BranchID'],
            $data['ProcessedBy'] ?? null,
            $data['Status'] ?? 'Requested',
            $data['Reason'],
            $data['ReasonDetails'] ?? null,
            $data['RefundMethod'] ?? 'Original Payment',
            $data['Subtotal'] ?? 0,
            $data['TaxAmount'] ?? 0,
            $data['RestockFee'] ?? 0,
            $data['ShippingFee'] ?? 0,
            $data['TotalRefund'] ?? 0,
            $data['Notes'] ?? null,
            $data['AdminNotes'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // add item to return request
    public static function addItem(int $returnId, array $item): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO product_return_item (ReturnID, OrderItemID, OnlineOrderItemID, ProductID, ProductName, Quantity, UnitPrice, TaxRate, TaxAmount, LineTotal, RestockQuantity, `Condition`, Notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $returnId,
            $item['OrderItemID'] ?? null,
            $item['OnlineOrderItemID'] ?? null,
            $item['ProductID'],
            $item['ProductName'],
            $item['Quantity'] ?? 1,
            $item['UnitPrice'],
            $item['TaxRate'] ?? 0,
            $item['TaxAmount'] ?? 0,
            $item['LineTotal'],
            $item['RestockQuantity'] ?? 0,
            $item['Condition'] ?? 'New',
            $item['Notes'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // update return status with timestamp
    public static function updateStatus(int $id, string $status): bool {
        $db = \Database::getConnection();
        $sql = "UPDATE product_return SET Status = ?";
        $params = [$status];
        if ($status === 'Approved') $sql .= ", ApprovedAt = NOW()";
        elseif ($status === 'Received') $sql .= ", ReceivedAt = NOW()";
        elseif ($status === 'Processed') $sql .= ", ProcessedAt = NOW()";
        elseif ($status === 'Refunded') $sql .= ", RefundedAt = NOW()";
        $sql .= " WHERE ReturnID = ?";
        $params[] = $id;
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    // delete return with items
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $db->prepare("DELETE FROM product_return_item WHERE ReturnID = ?")->execute([$id]);
        $stmt = $db->prepare("DELETE FROM product_return WHERE ReturnID = ?");
        return $stmt->execute([$id]);
    }

    // get all branches for dropdown
    public static function getBranches(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT * FROM branch ORDER BY BranchName")->fetchAll();
    }

    // get customers for dropdown
    public static function getCustomers(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT CustomerID, CustomerName, Email, Phone FROM customer WHERE IsActive = 1 ORDER BY CustomerName")->fetchAll();
    }
}
