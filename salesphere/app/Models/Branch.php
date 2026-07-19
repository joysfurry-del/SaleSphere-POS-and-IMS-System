<?php
namespace App\Models;

class Branch {
    // get single branch
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM branch WHERE BranchID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // get all branches sorted by name
    public static function getAll(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT * FROM branch ORDER BY BranchName")->fetchAll();
    }

    // add new branch
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare("INSERT INTO branch (BranchName, Location) VALUES (?, ?)");
        $stmt->execute([$data['BranchName'], $data['Location'] ?? '']);
        return (int)$db->lastInsertId();
    }

    // update branch fields
    public static function update(int $id, array $data): void {
        $db = \Database::getConnection();
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $id;
        $db->prepare("UPDATE branch SET " . implode(', ', $fields) . " WHERE BranchID = ?")->execute($values);
    }

    // delete branch
    public static function delete(int $id): void {
        $db = \Database::getConnection();
        $db->prepare("DELETE FROM branch WHERE BranchID = ?")->execute([$id]);
    }
}
