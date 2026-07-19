<?php
namespace App\Models;

use PDO;

class PromotionManager {
    // get promotions with creator name
    public static function getAll(?int $branchId = null, string $search = ''): array {
        $db = \Database::getConnection();
        $sql = "SELECT p.*, u.Username AS CreatedByName
                FROM promotion p
                LEFT JOIN user u ON p.CreatedBy = u.UserID";
        $where = [];
        $params = [];
        // filter by search keyword
        if ($search) {
            $where[] = "(p.Code LIKE ? OR p.Name LIKE ? OR p.Description LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY p.Priority DESC, p.CreatedAt DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // get single promotion
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT p.*, u.Username AS CreatedByName FROM promotion p LEFT JOIN user u ON p.CreatedBy = u.UserID WHERE p.PromotionID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // find active promotion by code
    public static function getByCode(string $code): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM promotion WHERE Code = ? AND IsActive = 1");
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    // check if promotion can still be used
    public static function validate(array $promotion, float $orderTotal, int $qty = 0): ?string {
        if (!$promotion['IsActive']) return 'Promotion is inactive.';
        if ($promotion['UsageLimit'] && $promotion['UsageCount'] >= $promotion['UsageLimit']) return 'Promotion usage limit reached.';
        if ($orderTotal < $promotion['MinOrderAmount']) return 'Minimum order amount of Rs ' . number_format($promotion['MinOrderAmount'], 2) . ' not met.';
        return null;
    }

    // create new promotion
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO promotion (Code, Name, Description, Type, Scope, Value, MinOrderAmount, MaxDiscountAmount, BuyQuantity, GetQuantity, GetDiscountPercent, StartDate, EndDate, UsageLimit, PerCustomerLimit, IsActive, IsPublic, Priority, CreatedBy)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['Code'], $data['Name'], $data['Description'] ?? null,
            $data['Type'], $data['Scope'], $data['Value'],
            $data['MinOrderAmount'] ?? 0, $data['MaxDiscountAmount'] ?? null,
            $data['BuyQuantity'] ?? null, $data['GetQuantity'] ?? null, $data['GetDiscountPercent'] ?? null,
            $data['StartDate'], $data['EndDate'],
            $data['UsageLimit'] ?? null, $data['PerCustomerLimit'] ?? 1,
            $data['IsActive'] ?? 1, $data['IsPublic'] ?? 1, $data['Priority'] ?? 0,
            $data['CreatedBy'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // update promotion fields
    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $id;
        $sql = "UPDATE promotion SET " . implode(', ', $fields) . " WHERE PromotionID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    // delete promotion
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM promotion WHERE PromotionID = ?");
        return $stmt->execute([$id]);
    }
}
