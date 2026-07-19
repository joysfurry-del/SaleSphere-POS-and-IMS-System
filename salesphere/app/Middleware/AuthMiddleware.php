<?php
namespace App\Middleware;

class AuthMiddleware {
    public static function requireAuth(): void {
        if (empty($_SESSION['user'])) {
            redirect(\BASE_URL . '/public/login.php');
        }
    }

    public static function requireRole(array $allowedIds): void {
        self::requireAuth();
        if (!in_array($_SESSION['user']['RoleID'], $allowedIds, true)) {
            http_response_code(403);
            echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:4rem;color:#A1A1AA;">Access Denied</h1>';
            echo '<p style="font-family:sans-serif;text-align:center;color:#71717A;">You do not have permission to view this page.</p>';
            exit;
        }
    }

    public static function guestOnly(): void {
        if (!empty($_SESSION['user'])) {
            $role = \ROLE_MAP[$_SESSION['user']['RoleID']] ?? 'admin';
            redirect(\BASE_URL . "/public/dashboards/$role.php");
        }
    }
}
