<?php
namespace App\Helpers;

class AuditHelper {
    public static function log(string $action, string $entityType, ?int $entityId = null, ?string $details = null): void {
        try {
            $db = \Database::getConnection();
            $userId = $_SESSION['user']['UserID'] ?? null;
            $desc = $action;
            if ($entityType) $desc .= " $entityType";
            if ($entityId) $desc .= " #$entityId";
            if ($details) $desc .= " - $details";
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription, CreatedAt) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $desc]);
        } catch (\Throwable $e) {
            // Silently fail - logging should never break the main operation
        }
    }

    public static function logLogin(int $userId, string $username, bool $success): void {
        try {
            $db = \Database::getConnection();
            $action = $success ? "Successful login" : "Failed login attempt";
            $desc = "$action for user '$username'";
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription, CreatedAt) VALUES (?, ?, NOW())");
            $stmt->execute([$success ? $userId : 0, $desc]);
        } catch (\Throwable $e) {}
    }
}
