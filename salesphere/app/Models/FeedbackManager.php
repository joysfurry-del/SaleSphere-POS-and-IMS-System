<?php
namespace App\Models;

use PDO;

class FeedbackManager {
    // get feedback with optional type and search filter
    public static function getAll(?string $refType = null, string $search = ''): array {
        $db = \Database::getConnection();
        $sql = "SELECT f.*, c.CustomerName, u.Username AS UserName, r.Username AS RespondedByName
                FROM feedback f
                LEFT JOIN customer c ON f.CustomerID = c.CustomerID
                LEFT JOIN user u ON f.UserID = u.UserID
                LEFT JOIN user r ON f.RespondedBy = r.UserID";
        $where = [];
        $params = [];
        if ($refType) {
            $where[] = "f.ReferenceType = ?";
            $params[] = $refType;
        }
        if ($search) {
            $where[] = "(f.Title LIKE ? OR f.Comment LIKE ? OR c.CustomerName LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY f.CreatedAt DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // get single feedback
    public static function getById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT f.*, c.CustomerName, u.Username AS UserName, r.Username AS RespondedByName
             FROM feedback f
             LEFT JOIN customer c ON f.CustomerID = c.CustomerID
             LEFT JOIN user u ON f.UserID = u.UserID
             LEFT JOIN user r ON f.RespondedBy = r.UserID
             WHERE f.FeedbackID = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // submit new feedback
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO feedback (ReferenceType, ReferenceID, CustomerID, UserID, Rating, Title, Comment, Pros, Cons)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['ReferenceType'],
            $data['ReferenceID'],
            $data['CustomerID'] ?? null,
            $data['UserID'] ?? null,
            $data['Rating'],
            $data['Title'] ?? null,
            $data['Comment'] ?? null,
            $data['Pros'] ?? null,
            $data['Cons'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // update feedback fields
    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $id;
        $sql = "UPDATE feedback SET " . implode(', ', $fields) . " WHERE FeedbackID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    // admin reply to feedback
    public static function respond(int $id, string $response, int $respondedBy): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE feedback SET AdminResponse = ?, RespondedBy = ?, RespondedAt = NOW() WHERE FeedbackID = ?");
        return $stmt->execute([$response, $respondedBy, $id]);
    }

    // toggle approval status
    public static function toggleApproved(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE feedback SET IsApproved = NOT IsApproved WHERE FeedbackID = ?");
        return $stmt->execute([$id]);
    }

    // toggle featured status
    public static function toggleFeatured(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE feedback SET IsFeatured = NOT IsFeatured WHERE FeedbackID = ?");
        return $stmt->execute([$id]);
    }

    // delete feedback with media
    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $db->prepare("DELETE FROM feedback_media WHERE FeedbackID = ?")->execute([$id]);
        $stmt = $db->prepare("DELETE FROM feedback WHERE FeedbackID = ?");
        return $stmt->execute([$id]);
    }
}
