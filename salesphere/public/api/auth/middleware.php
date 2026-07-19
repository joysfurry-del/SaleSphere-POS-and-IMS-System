<?php
@ini_set('display_errors', '0');

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}

function requireApiAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Missing or malformed Authorization header. Use: Bearer <token>']);
        exit;
    }

    $token = trim($matches[1]);

    $db = \Database::getConnection();
    $stmt = $db->prepare("SELECT CustomerID, CustomerName AS Name, Email, Phone, Address, City, Postcode, Country, CustomerType, IsActive, CreatedAt FROM customer WHERE ApiToken = ? AND IsActive = 1 LIMIT 1");
    $stmt->execute([$token]);
    $customer = $stmt->fetch();

    if (!$customer) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token.']);
        exit;
    }

    return $customer;
}
