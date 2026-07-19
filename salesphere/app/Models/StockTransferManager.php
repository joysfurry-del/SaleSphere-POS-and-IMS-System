<?php
namespace App\Models;

use PDO;

class StockTransferManager {
    // get all transfers with branch and product info
    public static function getAll(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT s.*, b.BranchName, p.ProductName
             FROM stock_transfer s
             JOIN branch b ON s.BranchID = b.BranchID
             JOIN product p ON s.ProductID = p.ProductID
             ORDER BY s.RequestDate DESC"
        )->fetchAll();
    }

    // get transfers waiting for approval
    public static function getPending(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT s.*, b.BranchName, p.ProductName
             FROM stock_transfer s
             JOIN branch b ON s.BranchID = b.BranchID
             JOIN product p ON s.ProductID = p.ProductID
             WHERE s.Status = 'Pending'
             ORDER BY s.RequestDate ASC"
        )->fetchAll();
    }

    // get transfers for a specific branch
    public static function getByBranch(int $branchId): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT s.*, b.BranchName, p.ProductName
             FROM stock_transfer s
             JOIN branch b ON s.BranchID = b.BranchID
             JOIN product p ON s.ProductID = p.ProductID
             WHERE s.BranchID = ?
             ORDER BY s.RequestDate DESC"
        );
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    // get single transfer
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT s.*, b.BranchName, p.ProductName
             FROM stock_transfer s
             JOIN branch b ON s.BranchID = b.BranchID
             JOIN product p ON s.ProductID = p.ProductID
             WHERE s.TransferID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // request new stock transfer
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO stock_transfer (BranchID, ProductID, RequestedQty)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $data['BranchID'],
            $data['ProductID'],
            $data['RequestedQty'],
        ]);
        return (int)$db->lastInsertId();
    }

    // approve transfer and update inventory stock
    public static function approve(int $id): bool {
        $db = \Database::getConnection();
        $db->beginTransaction();
        try {
            $transfer = self::getById($id);
            if (!$transfer || $transfer['Status'] !== 'Pending') {
                return false;
            }

            $stmt = $db->prepare("UPDATE stock_transfer SET Status = 'Approved' WHERE TransferID = ?");
            $stmt->execute([$id]);

            $existing = InventoryManager::getByBranchAndProduct(
                (int)$transfer['BranchID'],
                (int)$transfer['ProductID']
            );

            // if stock record exist, add qty. else create new
            if ($existing) {
                $stmt = $db->prepare(
                    "UPDATE inventory SET AvailableQty = AvailableQty + ?, LastUpdated = NOW() WHERE InventoryID = ?"
                );
                $stmt->execute([(int)$transfer['RequestedQty'], $existing['InventoryID']]);
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO inventory (BranchID, ProductID, AvailableQty, LastUpdated)
                     VALUES (?, ?, ?, NOW())"
                );
                $stmt->execute([(int)$transfer['BranchID'], (int)$transfer['ProductID'], (int)$transfer['RequestedQty']]);
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // reject a pending transfer
    public static function reject(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE stock_transfer SET Status = 'Rejected' WHERE TransferID = ? AND Status = 'Pending'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // get all products for dropdown
    public static function getProducts(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT p.*, c.CategoryName
             FROM product p
             JOIN category c ON p.CategoryID = c.CategoryID
             ORDER BY p.ProductName"
        )->fetchAll();
    }
}
