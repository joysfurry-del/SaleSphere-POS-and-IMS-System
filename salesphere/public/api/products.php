<?php
@ini_set('display_errors', '0');
// product API - list all or get single
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/autoload.php';
require_once __DIR__ . '/../../includes/functions.php';

$imageBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/salesphere/public/';

try {
    $db = \Database::getConnection();

    // Single product detail mode
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare(
            "SELECT p.*, c.CategoryName,
                    COALESCE(SUM(i.AvailableQty), 0) AS Stock
             FROM product p
             JOIN category c ON p.CategoryID = c.CategoryID
             LEFT JOIN inventory i ON p.ProductID = i.ProductID
             WHERE p.ProductID = ? AND p.IsEcommerce = 1
             GROUP BY p.ProductID"
        );
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }
        if ($product['ProductImage']) {
            $product['ProductImage'] = $imageBase . $product['ProductImage'];
        }
        echo json_encode(['success' => true, 'data' => $product]);
        exit;
    }

    // List mode with filters
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $search = trim($_GET['search'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;

    $where = "WHERE p.IsEcommerce = 1";
    $params = [];

    if ($categoryId) {
        $where .= " AND p.CategoryID = ?";
        $params[] = $categoryId;
    }
    if ($search !== '') {
        $where .= " AND (p.ProductName LIKE ? OR p.Description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM product p $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = (int)ceil($total / $limit);

    // Fetch products
    $sql = "SELECT p.ProductID, p.ProductName, p.Description, p.Price, p.ProductImage, p.ProductSKU,
                   c.CategoryName,
                   COALESCE(SUM(i.AvailableQty), 0) AS Stock
            FROM product p
            JOIN category c ON p.CategoryID = c.CategoryID
            LEFT JOIN inventory i ON p.ProductID = i.ProductID
            $where
            GROUP BY p.ProductID
            ORDER BY p.ProductName
            LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    foreach ($products as &$prod) {
        if ($prod['ProductImage']) {
            $prod['ProductImage'] = $imageBase . $prod['ProductImage'];
        }
    }
    unset($prod);

    echo json_encode([
        'success' => true,
        'data' => $products,
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'limit' => $limit,
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
