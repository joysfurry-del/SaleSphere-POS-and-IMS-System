<?php
namespace App\Models;

use PDO;

class InventoryManager {
    // get inventory with product and branch details
    public static function getAll(?int $branchId = null): array {
        $db = \Database::getConnection();
        $sql = "SELECT i.*, p.ProductName, p.Price, p.Description, b.BranchName, c.CategoryName
                FROM inventory i
                JOIN product p ON i.ProductID = p.ProductID
                JOIN branch b ON i.BranchID = b.BranchID
                JOIN category c ON p.CategoryID = c.CategoryID";
        $params = [];
        // filter by branch if specified
        if ($branchId) {
            $sql .= " WHERE i.BranchID = ?";
            $params[] = $branchId;
        }
        $sql .= " ORDER BY b.BranchName, p.ProductName";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // get single inventory record
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT i.*, p.ProductName, b.BranchName
             FROM inventory i
             JOIN product p ON i.ProductID = p.ProductID
             JOIN branch b ON i.BranchID = b.BranchID
             WHERE i.InventoryID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByBranch(int $branchId): array {
        return self::getAll($branchId);
    }

    // check stock by branch and product
    public static function getByBranchAndProduct(int $branchId, int $productId): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM inventory WHERE BranchID = ? AND ProductID = ?"
        );
        $stmt->execute([$branchId, $productId]);
        return $stmt->fetch() ?: null;
    }

    // add new stock record
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO inventory (BranchID, ProductID, AvailableQty)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $data['BranchID'],
            $data['ProductID'],
            $data['AvailableQty'],
        ]);
        return (int)$db->lastInsertId();
    }

    // update stock fields
    public static function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $id;
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE inventory SET " . implode(', ', $fields) . " WHERE InventoryID = ?");
        return $stmt->execute($values);
    }

    // delete stock record
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM inventory WHERE InventoryID = ?");
        return $stmt->execute([$id]);
    }

    // get all branches for dropdown
    public static function getBranches(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT * FROM branch ORDER BY BranchName")->fetchAll();
    }

    // get all products with category for dropdown
    public static function getProducts(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT p.*, c.CategoryName
             FROM product p
             JOIN category c ON p.CategoryID = c.CategoryID
             ORDER BY p.ProductName"
        )->fetchAll();
    }

    // find products running low on stock
    public static function getLowStock(int $threshold = 10): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT i.*, p.ProductName, b.BranchName
             FROM inventory i
             JOIN product p ON i.ProductID = p.ProductID
             JOIN branch b ON i.BranchID = b.BranchID
             WHERE i.AvailableQty <= ?
             ORDER BY i.AvailableQty ASC"
        );
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }
}
