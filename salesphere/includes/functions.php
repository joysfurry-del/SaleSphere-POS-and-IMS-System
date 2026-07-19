<?php
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function escape(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function old(string $key, string $default = ''): string {
    return $_POST[$key] ?? $default;
}

function getInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach ($parts as $p) {
        if ($p !== '') $initials .= strtoupper($p[0]);
    }
    return substr($initials, 0, 2) ?: 'U';
}

function avatarUrl(?string $avatar, int $userId): string {
    if ($avatar && file_exists(dirname(__DIR__) . '/public/assets/uploads/' . $avatar)) {
        return BASE_URL . '/public/assets/uploads/' . $avatar;
    }
    return '';
}

function currentUrl(): string {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return basename($path);
}

function requireAuth(): void {
    if (empty($_SESSION['user']) || empty($_SESSION['user']['UserID'])) {
        redirect(\BASE_URL . '/public/login.php');
    }
}

function requireRole(array $allowedIds): void {
    requireAuth();
    if (!in_array($_SESSION['user']['RoleID'], $allowedIds, true)) {
        http_response_code(403);
        echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:4rem;color:#A1A1AA;">Access Denied</h1>';
        echo '<p style="font-family:sans-serif;text-align:center;color:#71717A;">You do not have permission to view this page.</p>';
        exit;
    }
}

function requireAdmin(): void {
    requireRole([1]);
}

function getCurrentUserId(): ?int {
    return $_SESSION['user']['UserID'] ?? null;
}

function getCurrentBranchId(): ?int {
    return $_SESSION['user']['BranchID'] ?? null;
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validateRequired(array $fields, array $source, string $prefix = ''): ?string {
    foreach ($fields as $field) {
        $key = $prefix ? $prefix . '_' . $field : $field;
        if (empty($source[$key]) && $source[$key] !== '0' && $source[$key] !== 0) {
            return "Field '$field' is required.";
        }
    }
    return null;
}

function validateNumeric(mixed $value, string $label, bool $positive = true): ?string {
    if (!is_numeric($value)) return "$label must be a number.";
    if ($positive && (float)$value <= 0) return "$label must be greater than zero.";
    return null;
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
