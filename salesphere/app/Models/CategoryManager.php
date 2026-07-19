<?php
namespace App\Models;

use PDO;

class CategoryManager {
    // get all categories sorted by name
    public static function getAll(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT * FROM category ORDER BY CategoryName")->fetchAll();
    }

    // get single category by id
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM category WHERE CategoryID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // add new category
    public static function create(string $name): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare("INSERT INTO category (CategoryName) VALUES (?)");
        $stmt->execute([$name]);
        return (int)$db->lastInsertId();
    }

    // update category name
    public static function update(int $id, string $name): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE category SET CategoryName = ? WHERE CategoryID = ?");
        return $stmt->execute([$name, $id]);
    }

    // delete category
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM category WHERE CategoryID = ?");
        return $stmt->execute([$id]);
    }

    // check if category name already exist
    public static function nameExists(string $name, ?int $excludeId = null): bool {
        $db = \Database::getConnection();
        $sql = "SELECT 1 FROM category WHERE CategoryName = ?";
        $params = [$name];
        if ($excludeId) {
            $sql .= " AND CategoryID != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }
}
