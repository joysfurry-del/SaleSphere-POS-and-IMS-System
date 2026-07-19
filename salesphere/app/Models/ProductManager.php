<?php
namespace App\Models;

use PDO;

class ProductManager {
    public static function getAll(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT p.*, c.CategoryName
             FROM product p
             JOIN category c ON p.CategoryID = c.CategoryID
             ORDER BY p.ProductName"
        )->fetchAll();
    }

    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT p.*, c.CategoryName
             FROM product p
             JOIN category c ON p.CategoryID = c.CategoryID
             WHERE p.ProductID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO product (CategoryID, ProductName, Description, Price, ProductImage)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['CategoryID'],
            $data['ProductName'],
            $data['Description'] ?? null,
            $data['Price'],
            $data['ProductImage'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $id;
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE product SET " . implode(', ', $fields) . " WHERE ProductID = ?");
        return $stmt->execute($values);
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM product WHERE ProductID = ?");
        return $stmt->execute([$id]);
    }

    public static function toggleEcommerce(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE product SET IsEcommerce = NOT IsEcommerce WHERE ProductID = ?");
        return $stmt->execute([$id]);
    }

    public static function getEcommerceCatalog(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT p.*, c.CategoryName
             FROM product p
             JOIN category c ON p.CategoryID = c.CategoryID
             ORDER BY p.ProductName"
        )->fetchAll();
    }
}
