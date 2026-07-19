<?php
@ini_set('display_errors', '0');
// cart API - view, add, update, remove, promo
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    $taxRate = \App\Models\OrderManager::getTaxRate();

    // GET: retrieve cart
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $cartToken = $_GET['cart_token'] ?? '';
        if ($cartToken === '') {
            echo json_encode(['success' => false, 'message' => 'cart_token is required.']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM cart WHERE SessionID = ? AND Status = 'Active' ORDER BY CreatedAt DESC LIMIT 1");
        $stmt->execute([$cartToken]);
        $cart = $stmt->fetch();

        if (!$cart) {
            echo json_encode(['success' => true, 'data' => null, 'message' => 'Cart is empty.']);
            exit;
        }

        $itemStmt = $db->prepare("SELECT ci.*, p.ProductImage FROM cart_item ci LEFT JOIN product p ON ci.ProductID = p.ProductID WHERE ci.CartID = ? ORDER BY ci.CreatedAt");
        $itemStmt->execute([$cart['CartID']]);
        $items = $itemStmt->fetchAll();

        foreach ($items as &$item) {
            // build full image URL so browser can load it from commerce domain
            if ($item['ProductImage']) {
                $item['ProductImage'] = $imageBase . $item['ProductImage'];
            }
        }
        unset($item);

        echo json_encode([
            'success' => true,
            'data' => [
                'cart' => $cart,
                'items' => $items,
                'discount' => (float)($cart['DiscountAmount'] ?? 0),
                'promo_code' => $cart['PromotionCode'] ?? null,
            ]
        ]);
        exit;
    }

    // POST: cart operations
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
        exit;
    }

    $action = $input['action'] ?? '';
    $cartToken = $input['cart_token'] ?? '';

    if ($cartToken === '') {
        echo json_encode(['success' => false, 'message' => 'cart_token is required.']);
        exit;
    }

    // Get or create active cart for this token
    $stmt = $db->prepare("SELECT * FROM cart WHERE SessionID = ? AND Status = 'Active' ORDER BY CreatedAt DESC LIMIT 1");
    $stmt->execute([$cartToken]);
    $cart = $stmt->fetch();

    $branchId = 1; // Default branch; can be customized

    if (!$cart) {
        $stmt = $db->prepare("INSERT INTO cart (SessionID, BranchID, Status) VALUES (?, ?, 'Active')");
        $stmt->execute([$cartToken, $branchId]);
        $cartId = (int)$db->lastInsertId();
    } else {
        $cartId = (int)$cart['CartID'];
    }

    if ($action === 'add') {
        $productId = (int)($input['product_id'] ?? 0);
        $quantity = max(1, (int)($input['quantity'] ?? 1));

        if ($productId === 0) {
            echo json_encode(['success' => false, 'message' => 'product_id is required.']); exit;
        }

        // Fetch product
        $stmt = $db->prepare("SELECT ProductID, ProductName, Price, ProductImage FROM product WHERE ProductID = ? AND IsEcommerce = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']); exit;
        }

        // Check if already in cart
        $stmt = $db->prepare("SELECT * FROM cart_item WHERE CartID = ? AND ProductID = ?");
        $stmt->execute([$cartId, $productId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $newQty = (int)$existing['Quantity'] + $quantity;
            $lineTotal = round((float)$product['Price'] * $newQty, 2);
            $stmt = $db->prepare("UPDATE cart_item SET Quantity = ?, LineTotal = ? WHERE CartItemID = ?");
            $stmt->execute([$newQty, $lineTotal, $existing['CartItemID']]);
        } else {
            $lineTotal = round((float)$product['Price'] * $quantity, 2);
            $stmt = $db->prepare("INSERT INTO cart_item (CartID, ProductID, ProductName, Price, Quantity, LineTotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cartId, $productId, $product['ProductName'], $product['Price'], $quantity, $lineTotal]);
        }

        // Recalculate totals
        recalculateCart($db, $cartId, $taxRate);

        echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
        exit;
    }

    if ($action === 'update') {
        $itemId = (int)($input['item_id'] ?? 0);
        $quantity = max(0, (int)($input['quantity'] ?? 0));

        if ($itemId === 0) {
            echo json_encode(['success' => false, 'message' => 'item_id is required.']); exit;
        }

        $stmt = $db->prepare("SELECT ci.*, p.Price FROM cart_item ci JOIN product p ON ci.ProductID = p.ProductID WHERE ci.CartItemID = ? AND ci.CartID = ?");
        $stmt->execute([$itemId, $cartId]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found.']); exit;
        }

        if ($quantity <= 0) {
            $stmt = $db->prepare("DELETE FROM cart_item WHERE CartItemID = ?");
            $stmt->execute([$itemId]);
        } else {
            $lineTotal = round((float)$item['Price'] * $quantity, 2);
            $stmt = $db->prepare("UPDATE cart_item SET Quantity = ?, LineTotal = ? WHERE CartItemID = ?");
            $stmt->execute([$quantity, $lineTotal, $itemId]);
        }

        recalculateCart($db, $cartId, $taxRate);
        echo json_encode(['success' => true, 'message' => 'Cart updated.']);
        exit;
    }

    if ($action === 'remove') {
        $itemId = (int)($input['item_id'] ?? 0);
        if ($itemId === 0) {
            echo json_encode(['success' => false, 'message' => 'item_id is required.']); exit;
        }
        $stmt = $db->prepare("DELETE FROM cart_item WHERE CartItemID = ? AND CartID = ?");
        $stmt->execute([$itemId, $cartId]);
        recalculateCart($db, $cartId, $taxRate);
        echo json_encode(['success' => true, 'message' => 'Item removed.']);
        exit;
    }

    if ($action === 'promo') {
        $code = trim($input['code'] ?? '');
        if ($code === '') {
            echo json_encode(['success' => false, 'message' => 'Promotion code is required.']); exit;
        }

        $promo = \App\Models\PromotionManager::getByCode($code);
        if (!$promo) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired promotion code.']); exit;
        }

        $cartData = $db->prepare("SELECT Total FROM cart WHERE CartID = ?");
        $cartData->execute([$cartId]);
        $cartRow = $cartData->fetch();
        $cartTotal = $cartRow ? (float)$cartRow['Total'] : 0;

        $error = \App\Models\PromotionManager::validate($promo, $cartTotal);
        if ($error) {
            echo json_encode(['success' => false, 'message' => $error]); exit;
        }

        // Apply promo to cart
        $discount = 0;
        if ($promo['Type'] === 'Percentage') {
            $discount = round($cartTotal * (float)$promo['Value'] / 100, 2);
            if ($promo['MaxDiscountAmount'] && $discount > (float)$promo['MaxDiscountAmount']) {
                $discount = (float)$promo['MaxDiscountAmount'];
            }
        } elseif ($promo['Type'] === 'FixedAmount') {
            $discount = (float)$promo['Value'];
        }

        $stmt = $db->prepare("UPDATE cart SET PromotionID = ?, PromotionCode = ?, DiscountAmount = ? WHERE CartID = ?");
        $stmt->execute([$promo['PromotionID'], $code, $discount, $cartId]);
        recalculateCart($db, $cartId, $taxRate);

        // Increment usage count
        $db->prepare("UPDATE promotion SET UsageCount = UsageCount + 1 WHERE PromotionID = ?")->execute([$promo['PromotionID']]);

        echo json_encode(['success' => true, 'message' => "Promotion '$code' applied!", 'discount' => $discount]);
        exit;
    }

    if ($action === 'remove_promo') {
        $stmt = $db->prepare("UPDATE cart SET PromotionID = NULL, PromotionCode = NULL, DiscountAmount = 0 WHERE CartID = ?");
        $stmt->execute([$cartId]);
        recalculateCart($db, $cartId, $taxRate);
        echo json_encode(['success' => true, 'message' => 'Promotion removed.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.', 'debug' => $e->getMessage()]);
}

// recalculate cart totals
function recalculateCart(PDO $db, int $cartId, float $taxRate): void {
    $stmt = $db->prepare("SELECT COALESCE(SUM(LineTotal), 0) AS subtotal FROM cart_item WHERE CartID = ?");
    $stmt->execute([$cartId]);
    $row = $stmt->fetch();
    $subtotal = (float)$row['subtotal'];

    $stmt = $db->prepare("SELECT DiscountAmount, PromotionID, PromotionCode FROM cart WHERE CartID = ?");
    $stmt->execute([$cartId]);
    $cart = $stmt->fetch();

    $discount = (float)($cart['DiscountAmount'] ?? 0);
    $taxAmount = round(($subtotal - $discount) * $taxRate / 100, 2);
    $total = round($subtotal - $discount + $taxAmount, 2);

    $stmt = $db->prepare("UPDATE cart SET Subtotal = ?, TaxAmount = ?, DiscountAmount = ?, Total = ? WHERE CartID = ?");
    $stmt->execute([$subtotal, $taxAmount, $discount, $total, $cartId]);
}
