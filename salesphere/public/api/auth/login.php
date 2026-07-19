<?php
@ini_set('display_errors', '0');
// customer login
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../includes/autoload.php';
require_once __DIR__ . '/../../../includes/functions.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if ($email === '' || $password === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    $db = \Database::getConnection();

    $stmt = $db->prepare("SELECT CustomerID, CustomerName AS Name, Email, Password, IsActive FROM customer WHERE Email = ? LIMIT 1");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if (!$customer || !password_verify($password, $customer['Password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    if (!$customer['IsActive']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Account is deactivated.']);
        exit;
    }

    // Generate new token
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("UPDATE customer SET ApiToken = ? WHERE CustomerID = ?");
    $stmt->execute([$token, $customer['CustomerID']]);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'data' => [
            'customer_id' => (int)$customer['CustomerID'],
            'name' => $customer['Name'],
            'email' => $customer['Email'],
            'token' => $token,
        ],
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
