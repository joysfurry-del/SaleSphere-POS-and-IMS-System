<?php
namespace App\Models;

use PDO;

class BillManager {
    // get bills with optional branch and search filter
    public static function getAll(?int $branchId = null, string $search = ''): array {
        $db = \Database::getConnection();
        $sql = "SELECT b.*, u.Username AS CashierName, br.BranchName, c.CustomerName
                FROM bill b
                LEFT JOIN user u ON b.CashierID = u.UserID
                LEFT JOIN branch br ON b.BranchID = br.BranchID
                LEFT JOIN customer c ON b.CustomerID = c.CustomerID";
        $where = [];
        $params = [];
        if ($branchId) {
            $where[] = "b.BranchID = ?";
            $params[] = $branchId;
        }
        if ($search) {
            $where[] = "(b.BillNumber LIKE ? OR c.CustomerName LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%"]);
        }
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY b.CreatedAt DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // get single bill
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT b.*, u.Username AS CashierName, br.BranchName, c.CustomerName
             FROM bill b
             LEFT JOIN user u ON b.CashierID = u.UserID
             LEFT JOIN branch br ON b.BranchID = br.BranchID
             LEFT JOIN customer c ON b.CustomerID = c.CustomerID
             WHERE b.BillID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // get items for a bill
    public static function getItems(int $billId): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM bill_item WHERE BillID = ? ORDER BY SortOrder ASC, BillItemID ASC");
        $stmt->execute([$billId]);
        return $stmt->fetchAll();
    }

    // get payments for a bill
    public static function getPayments(int $billId): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM bill_payment WHERE BillID = ? ORDER BY ReceivedAt DESC");
        $stmt->execute([$billId]);
        return $stmt->fetchAll();
    }

    // create unique bill number based on date and count
    public static function generateBillNumber(): string {
        $db = \Database::getConnection();
        $prefix = 'INV-' . date('Ymd') . '-';
        $stmt = $db->prepare("SELECT COUNT(*) FROM bill WHERE BillNumber LIKE ?");
        $stmt->execute([$prefix . '%']);
        $count = (int)$stmt->fetchColumn();
        return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    // create new bill
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO bill (BillNumber, BillType, ReferenceType, ReferenceID, CustomerID, BranchID, CashierID, Status, Subtotal, TaxRate, TaxAmount, DiscountAmount, Total, AmountPaid, Balance, PaymentMethod, PaymentReference, DueDate, Notes, TermsConditions)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['BillNumber'] ?? self::generateBillNumber(),
            $data['BillType'] ?? 'Invoice',
            $data['ReferenceType'] ?? 'Manual',
            $data['ReferenceID'] ?? 0,
            $data['CustomerID'] ?? null,
            $data['BranchID'],
            $data['CashierID'] ?? null,
            $data['Status'] ?? 'Draft',
            $data['Subtotal'] ?? 0,
            $data['TaxRate'] ?? 0,
            $data['TaxAmount'] ?? 0,
            $data['DiscountAmount'] ?? 0,
            $data['Total'] ?? 0,
            $data['AmountPaid'] ?? 0,
            $data['Balance'] ?? ($data['Total'] ?? 0),
            $data['PaymentMethod'] ?? null,
            $data['PaymentReference'] ?? null,
            $data['DueDate'] ?? null,
            $data['Notes'] ?? null,
            $data['TermsConditions'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // add item to bill
    public static function addItem(int $billId, array $item): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO bill_item (BillID, ProductID, Description, Quantity, UnitPrice, TaxRate, TaxAmount, DiscountPercent, DiscountAmount, LineTotal, SortOrder)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $billId,
            $item['ProductID'] ?? null,
            $item['Description'],
            $item['Quantity'] ?? 1,
            $item['UnitPrice'],
            $item['TaxRate'] ?? 0,
            $item['TaxAmount'] ?? 0,
            $item['DiscountPercent'] ?? 0,
            $item['DiscountAmount'] ?? 0,
            $item['LineTotal'],
            $item['SortOrder'] ?? 0,
        ]);
        return (int)$db->lastInsertId();
    }

    // record a payment for a bill
    public static function addPayment(int $billId, array $payment): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO bill_payment (BillID, PaymentMethod, Amount, Reference, Notes, ReceivedBy)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $billId,
            $payment['PaymentMethod'],
            $payment['Amount'],
            $payment['Reference'] ?? null,
            $payment['Notes'] ?? null,
            $payment['ReceivedBy'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // update bill status with timestamp
    public static function updateStatus(int $id, string $status): bool {
        $db = \Database::getConnection();
        $sql = "UPDATE bill SET Status = ?";
        $params = [$status];
        if ($status === 'Issued') {
            $sql .= ", IssuedAt = NOW()";
        }
        if ($status === 'Paid') {
            $sql .= ", PaidAt = NOW()";
        }
        $sql .= " WHERE BillID = ?";
        $params[] = $id;
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    // delete bill with items and payments
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $db->prepare("DELETE FROM bill_payment WHERE BillID = ?")->execute([$id]);
        $db->prepare("DELETE FROM bill_item WHERE BillID = ?")->execute([$id]);
        $stmt = $db->prepare("DELETE FROM bill WHERE BillID = ?");
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

    // get orders for reference selection
    public static function getOrders(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT OrderID AS ReferenceID, 'Order' AS ReferenceType, CashierID, CustomerName, Subtotal, TaxAmount, Total, PaymentMethod, AmountPaid, `Change`, CreatedAt FROM `order` ORDER BY CreatedAt DESC")->fetchAll();
    }

    // get online orders for reference selection
    public static function getOnlineOrders(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT OnlineOrderID AS ReferenceID, 'OnlineOrder' AS ReferenceType, CustomerID, OrderNumber, Subtotal, TaxAmount, Total, AmountPaid, PaymentMethod, CreatedAt FROM online_order ORDER BY CreatedAt DESC")->fetchAll();
    }

    // get products for dropdown
    public static function getProducts(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT ProductID, ProductName, Price FROM product ORDER BY ProductName")->fetchAll();
    }
}
