<?php
namespace App\Models;

use PDO;

class User {
    // find user by username for login
    public static function findByUsername(string $username): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT u.*, r.RoleName, b.BranchName
             FROM user u
             JOIN role r ON u.RoleID = r.RoleID
             LEFT JOIN branch b ON u.BranchID = b.BranchID
             WHERE u.Username = ?"
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    // find user by id
    public static function findById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT u.*, r.RoleName, b.BranchName
             FROM user u
             JOIN role r ON u.RoleID = r.RoleID
             LEFT JOIN branch b ON u.BranchID = b.BranchID
             WHERE u.UserID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // update user fields dynamically
    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $id;
        $sql = "UPDATE user SET " . implode(', ', $fields) . " WHERE UserID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    // change user password
    public static function updatePassword(int $id, string $hash): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE user SET Password = ? WHERE UserID = ?");
        return $stmt->execute([$hash, $id]);
    }

    // get all users
    public static function getAll(): array {
        $db = \Database::getConnection();
        return $db->query(
            "SELECT u.*, r.RoleName, b.BranchName
             FROM user u
             JOIN role r ON u.RoleID = r.RoleID
             LEFT JOIN branch b ON u.BranchID = b.BranchID
             ORDER BY u.UserID"
        )->fetchAll();
    }

    // register new user
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $fullName = $data['FullName'] ?? $data['Username'];
        $stmt = $db->prepare(
            "INSERT INTO user (RoleID, BranchID, Username, FullName, Password, Email, Tele, Avatar)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['RoleID'],
            $data['BranchID'] ?? null,
            $data['Username'],
            $fullName,
            $data['Password'],
            $data['Email'],
            $data['Tele'] ?? null,
            $data['Avatar'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // delete user
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        // First, delete related transaction_log entries to avoid FK constraint violation
        $db->prepare("DELETE FROM transaction_log WHERE UserID = ?")->execute([$id]);
        // Then delete the user
        $stmt = $db->prepare("DELETE FROM user WHERE UserID = ?");
        return $stmt->execute([$id]);
    }

    // check if email already used
    public static function emailExists(string $email, ?int $excludeId = null): bool {
        $db = \Database::getConnection();
        $sql = "SELECT 1 FROM user WHERE Email = ?";
        $params = [$email];
        if ($excludeId) {
            $sql .= " AND UserID != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    // check if username already taken
    public static function usernameExists(string $username, ?int $excludeId = null): bool {
        $db = \Database::getConnection();
        $sql = "SELECT 1 FROM user WHERE Username = ?";
        $params = [$username];
        if ($excludeId) {
            $sql .= " AND UserID != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }
}
