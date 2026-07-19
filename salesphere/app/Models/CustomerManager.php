<?php
namespace App\Models;

use PDO;

class CustomerManager {
    // get all customers with order stats
    public static function getAll(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT c.*, b.BranchName,
                    COALESCE(o.OrderCount, 0) AS OrderCount,
                    COALESCE(o.TotalSpent, 0) AS TotalSpent
             FROM customer c
             JOIN branch b ON c.BranchID = b.BranchID
             LEFT JOIN (
                 SELECT CustomerID, COUNT(*) AS OrderCount, SUM(Total) AS TotalSpent
                 FROM `order`
                 GROUP BY CustomerID
             ) o ON c.CustomerID = o.CustomerID
             ORDER BY c.CustomerName"
        )->fetchAll();
    }

    // get single customer
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT c.*, b.BranchName
             FROM customer c
             JOIN branch b ON c.BranchID = b.BranchID
             WHERE c.CustomerID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // register new customer
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO customer (BranchID, CustomerName, Email, Phone, Address, City, State, Postcode, Country, Notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['BranchID'],
            $data['CustomerName'],
            $data['Email'] ?? null,
            $data['Phone'] ?? null,
            $data['Address'] ?? null,
            $data['City'] ?? null,
            $data['State'] ?? null,
            $data['Postcode'] ?? null,
            $data['Country'] ?? 'Malaysia',
            $data['Notes'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // update customer fields
    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $id;
        $sql = "UPDATE customer SET " . implode(', ', $fields) . " WHERE CustomerID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    // delete customer
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM customer WHERE CustomerID = ?");
        return $stmt->execute([$id]);
    }

    // get all branches for dropdown
    public static function getBranches(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT * FROM branch ORDER BY BranchName")->fetchAll();
    }

    // check if email already used
    public static function emailExists(string $email, ?int $excludeId = null): bool {
        if ($email === '') return false;
        $db = \Database::getConnection();
        $sql = "SELECT 1 FROM customer WHERE Email = ?";
        $params = [$email];
        if ($excludeId) {
            $sql .= " AND CustomerID != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    // check if phone already used
    public static function phoneExists(string $phone, ?int $excludeId = null): bool {
        if ($phone === '') return false;
        $db = \Database::getConnection();
        $sql = "SELECT 1 FROM customer WHERE Phone = ?";
        $params = [$phone];
        if ($excludeId) {
            $sql .= " AND CustomerID != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    // get active customers for dropdown
    public static function getForSelect(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT CustomerID, CustomerName, Email, Phone FROM customer WHERE IsActive = 1 ORDER BY CustomerName")->fetchAll();
    }
}