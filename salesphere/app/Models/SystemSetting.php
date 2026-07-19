<?php
namespace App\Models;

use PDO;

class SystemSetting {
    public static function getAll(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT s.*, u.Username
             FROM system_settings s
             LEFT JOIN user u ON s.UserID = u.UserID
             ORDER BY s.SettingNumber"
        )->fetchAll();
    }

    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM system_settings WHERE SettingNumber = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO system_settings (UserID, SettingName, SettingValue)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $data['UserID'],
            $data['SettingName'],
            $data['SettingValue'],
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
        $stmt = $db->prepare("UPDATE system_settings SET " . implode(', ', $fields) . " WHERE SettingNumber = ?");
        return $stmt->execute($values);
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM system_settings WHERE SettingNumber = ?");
        return $stmt->execute([$id]);
    }

    public static function nameExists(string $name, ?int $excludeId = null): bool {
        $db = \Database::getConnection();
        $sql = "SELECT 1 FROM system_settings WHERE SettingName = ?";
        $params = [$name];
        if ($excludeId) {
            $sql .= " AND SettingNumber != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }
}
