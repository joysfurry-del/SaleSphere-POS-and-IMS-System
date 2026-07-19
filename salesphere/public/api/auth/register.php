<?php
@ini_set('display_errors', '0');
// customer registration
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

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';

    // Validate required fields
    $errors = [];
    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    $db = \Database::getConnection();

    // Check duplicate email
    $stmt = $db->prepare("SELECT CustomerID FROM customer WHERE Email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
        exit;
    }

    // Hash password and generate token
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32));

    // Default BranchID = 1 (main branch)
    $stmt = $db->prepare(
        "INSERT INTO customer (BranchID, CustomerName, Email, Phone, Password, ApiToken, CustomerType, IsActive)
         VALUES (1, ?, ?, ?, ?, ?, 'Regular', 1)"
    );
    $stmt->execute([$name, $email, $phone ?: null, $hashed, $token]);
    $customerId = (int)$db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful.',
        'data' => [
            'customer_id' => $customerId,
            'name' => $name,
            'email' => $email,
            'token' => $token,
        ],
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
