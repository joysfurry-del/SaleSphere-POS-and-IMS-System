<?php
@ini_set('display_errors', '0');
// customer profile API - GET or PUT
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../includes/autoload.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../auth/middleware.php';

try {
    $customer = requireApiAuth();
    $db = \Database::getConnection();

    // GET — View profile
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'success' => true,
            'data' => [
                'customer_id' => (int)$customer['CustomerID'],
                'name' => $customer['Name'],
                'email' => $customer['Email'],
                'phone' => $customer['Phone'],
                'address' => $customer['Address'],
                'city' => $customer['City'],
                'postal_code' => $customer['Postcode'],
                'country' => $customer['Country'],
                'customer_type' => $customer['CustomerType'],
                'created_at' => $customer['CreatedAt'],
            ],
        ]);
        exit;
    }

    // PUT — Update profile
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
            exit;
        }

        $fields = [];
        $params = [];
        $allowed = ['name' => 'CustomerName', 'phone' => 'Phone', 'address' => 'Address', 'city' => 'City', 'postal_code' => 'Postcode', 'country' => 'Country'];

        foreach ($allowed as $inputKey => $dbCol) {
            if (isset($input[$inputKey])) {
                $val = trim($input[$inputKey]);
                $fields[] = "`$dbCol` = ?";
                $params[] = $val === '' ? null : $val;
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update.']);
            exit;
        }

        $params[] = $customer['CustomerID'];
        $sql = "UPDATE customer SET " . implode(', ', $fields) . " WHERE CustomerID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // Re-fetch updated customer
        $stmt = $db->prepare("SELECT CustomerID, CustomerName AS Name, Email, Phone, Address, City, Postcode, Country, CustomerType, CreatedAt FROM customer WHERE CustomerID = ?");
        $stmt->execute([$customer['CustomerID']]);
        $updated = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated.',
            'data' => [
                'customer_id' => (int)$updated['CustomerID'],
                'name' => $updated['Name'],
                'email' => $updated['Email'],
                'phone' => $updated['Phone'],
                'address' => $updated['Address'],
                'city' => $updated['City'],
                'postal_code' => $updated['Postcode'],
                'country' => $updated['Country'],
                'customer_type' => $updated['CustomerType'],
                'created_at' => $updated['CreatedAt'],
            ],
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use GET or PUT.']);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}


