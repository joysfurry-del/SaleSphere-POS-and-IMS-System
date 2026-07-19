<?php
@ini_set('display_errors', '0');
// submit product feedback
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
require_once __DIR__ . '/../auth/middleware.php';

try {
    $customer = requireApiAuth();
    $db = \Database::getConnection();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
        exit;
    }

    $productId = (int)($input['product_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 0);
    $title = trim($input['title'] ?? '');
    $comment = trim($input['comment'] ?? '');
    $pros = trim($input['pros'] ?? '');
    $cons = trim($input['cons'] ?? '');

    // Validate
    $errors = [];
    if ($productId <= 0) $errors[] = 'product_id is required.';
    if ($rating < 1 || $rating > 5) $errors[] = 'Rating must be between 1 and 5.';

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    // Check product exists
    $stmt = $db->prepare("SELECT ProductID, ProductName FROM product WHERE ProductID = ? LIMIT 1");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    // Verify customer has a Delivered order containing this product
    $stmt = $db->prepare(
        "SELECT 1 FROM online_order o
         JOIN online_order_item oi ON o.OnlineOrderID = oi.OnlineOrderID
         WHERE o.CustomerID = ? AND oi.ProductID = ? AND o.Status = 'Delivered'
         LIMIT 1"
    );
    $stmt->execute([$customer['CustomerID'], $productId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only review products from delivered orders.']);
        exit;
    }

    // Check if customer already reviewed this product
    $stmt = $db->prepare("SELECT FeedbackID FROM feedback WHERE ReferenceType = 'Product' AND ReferenceID = ? AND CustomerID = ? LIMIT 1");
    $stmt->execute([$productId, $customer['CustomerID']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You already submitted feedback for this product.']);
        exit;
    }

    // Insert feedback
    $stmt = $db->prepare(
        "INSERT INTO feedback (ReferenceType, ReferenceID, CustomerID, Rating, Title, Comment, Pros, Cons, IsVerifiedPurchase, IsApproved)
         VALUES ('Product', ?, ?, ?, ?, ?, ?, ?, ?, 0)"
    );

    // Check if customer actually purchased this product to mark verified
    $stmtCheck = $db->prepare(
        "SELECT 1 FROM online_order o
         JOIN online_order_item oi ON o.OnlineOrderID = oi.OnlineOrderID
         WHERE o.CustomerID = ? AND oi.ProductID = ? AND o.Status IN ('Delivered', 'Shipped')
         LIMIT 1"
    );
    $stmtCheck->execute([$customer['CustomerID'], $productId]);
    $isVerified = $stmtCheck->fetch() ? 1 : 0;

    $stmt->execute([
        $productId,
        $customer['CustomerID'],
        $rating,
        $title ?: null,
        $comment ?: null,
        $pros ?: null,
        $cons ?: null,
        $isVerified,
    ]);

    $feedbackId = (int)$db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Feedback submitted. Awaiting approval.',
        'data' => [
            'feedback_id' => $feedbackId,
            'product_id' => $productId,
            'rating' => $rating,
            'is_verified_purchase' => (bool)$isVerified,
            'is_approved' => false,
        ],
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
